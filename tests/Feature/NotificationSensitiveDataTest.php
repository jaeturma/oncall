<?php

namespace Tests\Feature;

use App\Enums\EnforcementAction;
use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Enums\VerificationStatus;
use App\Models\EnforcementCase;
use App\Models\Job;
use App\Models\NotificationDeliveryLog;
use App\Models\ProviderDocument;
use App\Models\ProviderProfile;
use App\Models\User;
use App\Services\Notifications\NotificationDispatcher;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * Phase M Step 34 / Step 50 — a push/in-app notification may sit on a locked
 * screen, so it must never carry an OTP, a document path, an internal staff
 * note, or private message content. Every business call site only ever
 * passes the catalog's declared placeholders (see config/notifications.php)
 * into NotificationDispatcher::dispatch() — these tests drive the real
 * workflow with realistic sensitive values and assert none of them survive
 * into the persisted notification or the rendered push text.
 */
class NotificationSensitiveDataTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_verification_rejection_does_not_expose_the_document_path_or_admin_notes(): void
    {
        $admin = User::factory()->admin()->create();
        $applicant = User::factory()->serviceProvider()->create(['identity_verification_status' => VerificationStatus::Submitted]);
        $privatePath = 'verification-documents/gov-id-ssn-447-scan.pdf';
        $adminNote = 'Photo shows a mismatched signature — escalate to fraud review, informant tip #A-4471.';
        $document = ProviderDocument::factory()->for($applicant)->create(['status' => VerificationStatus::Submitted, 'private_path' => $privatePath]);
        $applicant->verificationRecords()->create(['type' => 'IDENTITY', 'status' => VerificationStatus::Submitted]);

        $this->actingAs($admin)->patch(route('admin.verifications.update', $document), ['status' => 'REJECTED', 'notes' => $adminNote])->assertRedirect();

        $notification = $applicant->notifications()->sole();
        $payload = json_encode($notification->data);
        $this->assertStringNotContainsString($privatePath, $payload);
        $this->assertStringNotContainsString('fraud review', $payload);
        $this->assertStringNotContainsString('A-4471', $payload);
        $this->assertStringNotContainsString($adminNote, $payload);
    }

    public function test_verification_approval_does_not_expose_the_document_path(): void
    {
        $admin = User::factory()->admin()->create();
        $applicant = User::factory()->serviceProvider()->create(['identity_verification_status' => VerificationStatus::Submitted]);
        $privatePath = 'verification-documents/passport-scan-secret.pdf';
        $document = ProviderDocument::factory()->for($applicant)->create(['status' => VerificationStatus::Submitted, 'private_path' => $privatePath]);
        $applicant->verificationRecords()->create(['type' => 'IDENTITY', 'status' => VerificationStatus::Submitted]);

        $this->actingAs($admin)->patch(route('admin.verifications.update', $document), ['status' => 'VERIFIED'])->assertRedirect();

        $payload = json_encode($applicant->notifications()->sole()->data);
        $this->assertStringNotContainsString($privatePath, $payload);
        $this->assertStringNotContainsString('passport', $payload);
    }

    public function test_mobile_number_verification_does_not_expose_the_otp_code(): void
    {
        $user = User::factory()->create(['role' => UserRole::ServiceFinder, 'status' => UserStatus::Active, 'phone' => null]);
        Sanctum::actingAs($user);

        $requested = $this->postJson('/api/v1/mobile-verification/request', ['mobile' => '09171234567'])->assertOk();
        $otp = $requested->json('demo_code');
        $this->assertNotNull($otp, 'The test environment should expose demo_code so this test can assert against the real code.');

        $this->postJson('/api/v1/mobile-verification/verify', ['code' => $otp])->assertOk();

        $notification = $user->notifications()->sole();
        $payload = json_encode($notification->data);
        $this->assertStringNotContainsString($otp, $payload);
        $this->assertSame('security_mobile_verified', $notification->data['key']);
    }

    public function test_account_suspension_does_not_expose_the_internal_resolution_or_violation_details(): void
    {
        $admin = User::factory()->admin()->create();
        $provider = User::factory()->serviceProvider()->create(['status' => UserStatus::Active]);
        $case = EnforcementCase::factory()->create(['user_id' => $provider->id]);
        $secretResolution = 'Confirmed member of a fraud ring — internal case #A-4471, do not disclose to the account holder.';

        $this->progressToSuspension($admin, $case, $secretResolution);

        // DatabaseNotification's id is a UUID, not sequential, so filter by
        // event key rather than relying on insertion order.
        $notification = $provider->notifications->sole(fn ($n): bool => $n->data['key'] === 'account_suspended');
        $this->assertSame('account_suspended', $notification->data['key']);
        $payload = json_encode($notification->data);
        $this->assertStringNotContainsString('fraud ring', $payload);
        $this->assertStringNotContainsString('A-4471', $payload);
        $this->assertStringNotContainsString($secretResolution, $payload);

        // The push notification is limited to the safe, generic app-open text (Step 18).
        $this->assertStringContainsString('Open the app for details', $notification->data['body']);
    }

    public function test_a_new_message_notification_does_not_expose_the_message_content(): void
    {
        [$finder, $provider, $job] = $this->finderProviderAndJob();
        $secretMessage = 'My SSS number is 12-3456789-0, please send the payment there.';

        Sanctum::actingAs($finder);
        $this->postJson("/api/v1/jobs/{$job->id}/messages", ['type' => 'MESSAGE', 'body' => $secretMessage])->assertCreated();

        $notification = $provider->notifications()->sole();
        $payload = json_encode($notification->data);
        $this->assertStringNotContainsString($secretMessage, $payload);
        $this->assertStringNotContainsString('12-3456789-0', $payload);
        $this->assertStringContainsString('new message', strtolower($notification->data['body']));
    }

    public function test_the_delivery_log_never_persists_the_rendered_title_or_body(): void
    {
        Queue::fake();
        $recipient = User::factory()->create();

        app(NotificationDispatcher::class)->dispatch($recipient, 'new_message', ['sender_name' => 'Ana Confidential-Lastname'], null);

        $log = NotificationDeliveryLog::sole();
        $this->assertArrayNotHasKey('body', $log->getAttributes());
        $this->assertArrayNotHasKey('title', $log->getAttributes());
        $this->assertArrayNotHasKey('push_body', $log->getAttributes());
    }

    public function test_the_mobile_notifications_api_never_exposes_the_raw_data_payload(): void
    {
        $user = User::factory()->create(['role' => UserRole::ServiceFinder, 'status' => UserStatus::Active]);
        Queue::fake();
        app(NotificationDispatcher::class)->dispatch($user, 'new_message', ['sender_name' => 'Ana'], ['screen' => 'conversation', 'id' => 5]);

        Sanctum::actingAs($user);
        $response = $this->getJson('/api/v1/notifications')->assertOk();

        $item = $response->json('data.0');
        $this->assertSame(['id', 'type', 'title', 'body', 'target', 'read_at', 'created_at'], array_keys($item));
    }

    /** @return array{0: User, 1: User, 2: Job} */
    private function finderProviderAndJob(): array
    {
        $finder = User::factory()->create(['role' => UserRole::ServiceFinder, 'status' => UserStatus::Active]);
        $provider = User::factory()->serviceProvider()->create(['status' => UserStatus::Active]);
        $profile = ProviderProfile::factory()->for($provider, 'user')->create();
        $job = Job::factory()->create(['service_finder_id' => $finder->id, 'provider_id' => $provider->id]);

        return [$finder, $provider, $job];
    }

    private function progressToSuspension(User $admin, EnforcementCase $case, string $finalResolution): void
    {
        $this->actingAs($admin)->patch(route('admin.enforcement.update', $case), $this->actionPayload(EnforcementAction::Warning));
        $this->actingAs($admin)->patch(route('admin.enforcement.update', $case), $this->actionPayload(EnforcementAction::AccountReview));
        $this->actingAs($admin)->patch(route('admin.enforcement.update', $case), $this->actionPayload(EnforcementAction::TemporaryRestriction, ['NEW_BOOKINGS'], 'Interim restriction while reviewed.'));
        $this->actingAs($admin)->patch(route('admin.enforcement.update', $case), $this->actionPayload(EnforcementAction::Suspension, [], $finalResolution))->assertRedirect();
    }

    /** @param list<string> $capabilities */
    private function actionPayload(EnforcementAction $action, array $capabilities = [], string $resolution = 'Admin reviewed the available evidence.'): array
    {
        return [
            'action' => $action->value,
            'severity' => 'MODERATE',
            'restricted_capabilities' => $capabilities,
            'duration_days' => $action === EnforcementAction::TemporaryRestriction ? 14 : null,
            'resolution' => $resolution,
        ];
    }
}
