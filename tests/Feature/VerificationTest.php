<?php

namespace Tests\Feature;

use App\Enums\DocumentType;
use App\Enums\UserRole;
use App\Enums\VerificationStatus;
use App\Models\ProviderDocument;
use App\Models\ProviderProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class VerificationTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_user_uploads_document_to_private_storage_and_enters_review(): void
    {
        Storage::fake('local');
        $user = User::factory()->create();
        $document = UploadedFile::fake()->create('national-id.pdf', 100, 'application/pdf');

        $this->actingAs($user)->post(route('verification.documents.store'), ['document_type' => DocumentType::NationalId->value, 'document' => $document])->assertRedirect(route('verification.index'));

        $providerDocument = ProviderDocument::whereBelongsTo($user)->sole();
        Storage::disk('local')->assertExists($providerDocument->private_path);
        $this->assertSame(VerificationStatus::Submitted, $providerDocument->status);
        $this->assertDatabaseHas('verification_records', ['user_id' => $user->id, 'type' => 'IDENTITY', 'status' => VerificationStatus::Submitted->value]);
        $this->assertSame(VerificationStatus::Submitted, $user->refresh()->identity_verification_status);
    }

    public function test_upload_rejects_executable_file(): void
    {
        Storage::fake('local');
        $user = User::factory()->create();

        $this->actingAs($user)->post(route('verification.documents.store'), ['document_type' => DocumentType::NationalId->value, 'document' => UploadedFile::fake()->create('identity.php', 10, 'application/x-php')])->assertSessionHasErrors('document');

        Storage::disk('local')->assertDirectoryEmpty('/');
        $this->assertDatabaseMissing('provider_documents', ['user_id' => $user->id]);
    }

    public function test_user_cannot_submit_second_document_while_review_is_pending(): void
    {
        Storage::fake('local');
        $user = User::factory()->create();
        ProviderDocument::factory()->for($user)->create();

        $this->actingAs($user)->post(route('verification.documents.store'), ['document_type' => DocumentType::Passport->value, 'document' => UploadedFile::fake()->create('passport.pdf', 100, 'application/pdf')])->assertForbidden();

        $this->assertSame(1, ProviderDocument::whereBelongsTo($user)->count());
        Storage::disk('local')->assertDirectoryEmpty('/');
    }

    /**
     * Phase K: Laravel's `local` disk defaults to `'serve' => true`, which
     * auto-registers a public, unauthenticated `GET /storage/{path}` route
     * (framework-level, outside routes/web.php) that would serve files
     * straight from storage/app/private — bypassing this controller's
     * ownership/admin check entirely. config/filesystems.php sets it back
     * to false; this locks that in.
     */
    public function test_the_local_disk_has_no_public_serving_route(): void
    {
        $this->assertFalse(Route::has('storage.local'));

        Storage::fake('local');
        $document = ProviderDocument::factory()->create(['private_path' => 'verification-documents/1/secret-id.pdf']);
        Storage::disk('local')->put($document->private_path, 'private identity document contents');

        $this->get('/storage/'.$document->private_path)->assertNotFound();
    }

    public function test_document_download_is_limited_to_owner_and_admin(): void
    {
        Storage::fake('local');
        $owner = User::factory()->create();
        $otherUser = User::factory()->create();
        $admin = User::factory()->admin()->create();
        $document = ProviderDocument::factory()->for($owner)->create(['private_path' => 'verification-documents/id.pdf']);
        Storage::disk('local')->put($document->private_path, 'private identity');

        $this->actingAs($otherUser)->get(route('verification.documents.download', $document))->assertNotFound();
        $this->actingAs($owner)->get(route('verification.documents.download', $document))->assertDownload();
        $this->actingAs($admin)->get(route('verification.documents.download', $document))->assertDownload();
    }

    public function test_admin_verifies_submission_and_substantiates_provider_badge(): void
    {
        $admin = User::factory()->admin()->create();
        $profile = ProviderProfile::factory()->create();
        $document = ProviderDocument::factory()->for($profile->user)->create();
        $profile->user->verificationRecords()->create(['type' => 'IDENTITY', 'status' => VerificationStatus::Submitted]);

        $this->actingAs($admin)->patch(route('admin.verifications.update', $document), ['status' => VerificationStatus::Verified->value, 'expires_at' => now()->addYear()->toDateString()])->assertRedirect();

        $this->assertSame(VerificationStatus::Verified, $document->refresh()->status);
        $this->assertSame($admin->id, $document->reviewed_by);
        $this->assertSame(VerificationStatus::Verified, $profile->user->refresh()->identity_verification_status);
        $this->assertSame(VerificationStatus::Verified, $profile->refresh()->verification_status);
        $this->assertDatabaseHas('verification_records', ['user_id' => $profile->user_id, 'status' => VerificationStatus::Verified->value, 'reviewed_by' => $admin->id]);
    }

    public function test_rejection_requires_review_notes(): void
    {
        $admin = User::factory()->admin()->create();
        $document = ProviderDocument::factory()->create();

        $this->actingAs($admin)->patch(route('admin.verifications.update', $document), ['status' => VerificationStatus::Rejected->value])->assertSessionHasErrors('notes');

        $this->assertSame(VerificationStatus::Submitted, $document->refresh()->status);
    }

    public function test_admin_can_expire_a_submission_with_reason(): void
    {
        $admin = User::factory()->admin()->create();
        $document = ProviderDocument::factory()->create();

        $this->actingAs($admin)->patch(route('admin.verifications.update', $document), ['status' => VerificationStatus::Expired->value, 'notes' => 'Document is no longer valid.'])->assertRedirect();

        $this->assertSame(VerificationStatus::Expired, $document->refresh()->status);
        $this->assertSame(VerificationStatus::Expired, $document->user->refresh()->identity_verification_status);
    }

    public function test_non_admin_cannot_review_submission(): void
    {
        $document = ProviderDocument::factory()->create();
        $serviceFinder = User::factory()->create();

        $this->actingAs($serviceFinder)->patch(route('admin.verifications.update', $document), ['status' => VerificationStatus::Verified->value])->assertForbidden();

        $this->assertSame(VerificationStatus::Submitted, $document->refresh()->status);
    }

    public function test_document_owner_cannot_approve_their_own_submission(): void
    {
        $owner = User::factory()->create();
        $document = ProviderDocument::factory()->for($owner)->create();

        $this->actingAs($owner)->patch(route('admin.verifications.update', $document), ['status' => VerificationStatus::Verified->value])->assertForbidden();

        $this->assertSame(VerificationStatus::Submitted, $document->refresh()->status);
    }

    /** Back-office staff outside the Admin role (which covers Verifier per ADR-001) may not review verifications either. */
    public function test_unrelated_back_office_role_cannot_review_submission(): void
    {
        $document = ProviderDocument::factory()->create();
        $accounting = User::factory()->create(['role' => UserRole::Accounting]);

        $this->actingAs($accounting)->patch(route('admin.verifications.update', $document), ['status' => VerificationStatus::Verified->value])->assertForbidden();

        $this->assertSame(VerificationStatus::Submitted, $document->refresh()->status);
    }

    public function test_reviewing_a_submission_writes_an_audit_log_entry(): void
    {
        $admin = User::factory()->admin()->create();
        $document = ProviderDocument::factory()->create();

        $this->actingAs($admin)->patch(route('admin.verifications.update', $document), ['status' => VerificationStatus::Rejected->value, 'notes' => 'Blurry photo.']);

        $this->assertDatabaseHas('audit_logs', [
            'actor_id' => $admin->id,
            'event' => 'verification.reviewed',
            'subject_type' => ProviderDocument::class,
            'subject_id' => $document->id,
        ]);
    }

    public function test_admin_can_revoke_a_previously_verified_document(): void
    {
        $admin = User::factory()->admin()->create();
        $profile = ProviderProfile::factory()->create(['verification_status' => VerificationStatus::Verified]);
        $document = ProviderDocument::factory()->for($profile->user)->create(['status' => VerificationStatus::Verified, 'reviewed_by' => $admin->id, 'reviewed_at' => now()]);
        $profile->user->update(['identity_verification_status' => VerificationStatus::Verified]);
        $profile->user->verificationRecords()->create(['type' => 'IDENTITY', 'status' => VerificationStatus::Verified]);

        $response = $this->actingAs($admin)->patch(route('admin.verifications.update', $document), ['status' => VerificationStatus::Expired->value, 'notes' => 'Government ID reported lost; revoking until resubmission.']);

        $response->assertRedirect();
        $this->assertSame(VerificationStatus::Expired, $document->refresh()->status);
        $this->assertSame(VerificationStatus::Expired, $profile->user->refresh()->identity_verification_status);
        $this->assertSame(VerificationStatus::Expired, $profile->refresh()->verification_status);
        $this->assertDatabaseHas('audit_logs', ['actor_id' => $admin->id, 'event' => 'verification.revoked', 'subject_type' => ProviderDocument::class, 'subject_id' => $document->id]);
    }

    public function test_a_rejected_document_cannot_be_reopened(): void
    {
        $admin = User::factory()->admin()->create();
        $document = ProviderDocument::factory()->create(['status' => VerificationStatus::Rejected]);

        $this->actingAs($admin)->patch(route('admin.verifications.update', $document), ['status' => VerificationStatus::Verified->value])->assertStatus(422);

        $this->assertSame(VerificationStatus::Rejected, $document->refresh()->status);
    }

    public function test_verification_queue_filters_by_status(): void
    {
        $admin = User::factory()->admin()->create();
        $submitted = ProviderDocument::factory()->create(['status' => VerificationStatus::Submitted]);
        $verified = ProviderDocument::factory()->create(['status' => VerificationStatus::Verified]);

        $pending = $this->actingAs($admin)->get(route('admin.verifications.index', ['status' => 'SUBMITTED']));
        $pending->assertSee($submitted->user->name)->assertDontSee($verified->user->name);

        $verifiedQueue = $this->actingAs($admin)->get(route('admin.verifications.index', ['status' => 'VERIFIED']));
        $verifiedQueue->assertSee($verified->user->name)->assertDontSee($submitted->user->name);
    }

    public function test_service_request_eligibility_requires_current_substantiated_verification(): void
    {
        $user = User::factory()->identityVerified()->create();
        ProviderDocument::factory()->for($user)->create(['status' => VerificationStatus::Verified, 'expires_at' => now()->subDay()]);

        $this->assertFalse($user->canRequestService());

        ProviderDocument::factory()->for($user)->create(['status' => VerificationStatus::Verified, 'expires_at' => now()->addYear()]);

        $this->assertTrue($user->canRequestService());
    }

    public function test_service_request_verification_requirement_can_be_disabled(): void
    {
        config(['oncall.service_requests.require_identity_verification' => false]);
        $user = User::factory()->create();

        $this->assertTrue($user->canRequestService());
    }
}
