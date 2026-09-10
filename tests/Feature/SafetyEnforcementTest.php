<?php

namespace Tests\Feature;

use App\Enums\EnforcementAction;
use App\Enums\RestrictedCapability;
use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\AuditLog;
use App\Models\EnforcementCase;
use App\Models\Job;
use App\Models\ServiceRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SafetyEnforcementTest extends TestCase
{
    use RefreshDatabase;

    public function test_participant_report_opens_review_case_without_automatic_guilt_or_penalty(): void
    {
        [$finder, $provider, $job] = $this->participantsAndJob();

        $this->actingAs($finder)->post(route('jobs.reports.store', $job), ['category' => 'OFF_PLATFORM_CONTACT', 'description' => 'The provider asked to move payment outside Oncall.'])->assertRedirect();

        $this->assertDatabaseHas('user_reports', ['reporter_id' => $finder->id, 'reported_user_id' => $provider->id, 'job_id' => $job->id, 'status' => 'SUBMITTED']);
        $case = EnforcementCase::sole();
        $this->assertNull($case->action);
        $this->assertSame(UserStatus::Active, $provider->fresh()->status);
        $this->assertDatabaseHas('audit_logs', ['event' => 'safety.report_submitted', 'subject_id' => $case->id]);
    }

    public function test_nonparticipant_cannot_report_user_through_unrelated_job(): void
    {
        [, , $job] = $this->participantsAndJob();
        $outsider = User::factory()->create(['role' => UserRole::ServiceFinder]);

        $this->actingAs($outsider)->post(route('jobs.reports.store', $job), ['category' => 'OTHER', 'description' => 'Unrelated report'])->assertForbidden();
        $this->assertDatabaseCount('user_reports', 0);
    }

    public function test_only_admin_can_manage_enforcement_cases(): void
    {
        [$finder, $provider] = $this->participantsAndJob();
        $case = EnforcementCase::factory()->create(['user_id' => $provider->id]);

        $this->actingAs($finder)->patch(route('admin.enforcement.update', $case), $this->actionPayload(EnforcementAction::Warning))->assertForbidden();
        $this->assertNull($case->fresh()->action);
    }

    public function test_admin_must_apply_full_enforcement_ladder_and_every_step_is_audited(): void
    {
        [, $provider] = $this->participantsAndJob();
        $admin = User::factory()->admin()->create()->refresh();
        $case = EnforcementCase::factory()->create(['user_id' => $provider->id]);

        $expectedStatuses = [
            EnforcementAction::Warning->value => UserStatus::Warning,
            EnforcementAction::AccountReview->value => UserStatus::UnderReview,
            EnforcementAction::TemporaryRestriction->value => UserStatus::Restricted,
            EnforcementAction::Suspension->value => UserStatus::Suspended,
        ];

        foreach ($expectedStatuses as $action => $expectedUserStatus) {
            $enforcementAction = EnforcementAction::from($action);
            $payload = $this->actionPayload($enforcementAction, $enforcementAction === EnforcementAction::TemporaryRestriction ? [RestrictedCapability::NewBookings->value] : []);
            $this->actingAs($admin)->patch(route('admin.enforcement.update', $case), $payload)->assertRedirect();
            $this->assertSame($expectedUserStatus, $provider->fresh()->status);
        }

        $this->assertSame(EnforcementAction::Suspension, $case->fresh()->action);
        $this->assertSame(4, AuditLog::where('subject_id', $case->id)->where('event', 'enforcement.action_applied')->count());
    }

    public function test_admin_cannot_skip_required_enforcement_step(): void
    {
        [, $provider] = $this->participantsAndJob();
        $admin = User::factory()->admin()->create()->refresh();
        $case = EnforcementCase::factory()->create(['user_id' => $provider->id]);

        $this->actingAs($admin)->patch(route('admin.enforcement.update', $case), $this->actionPayload(EnforcementAction::TemporaryRestriction))->assertConflict();

        $this->assertNull($case->fresh()->action);
        $this->assertSame(UserStatus::Active, $provider->fresh()->status);
    }

    public function test_targeted_restrictions_block_messaging_and_contact_reveal(): void
    {
        [$finder, $provider, $job] = $this->participantsAndJob();
        $admin = User::factory()->admin()->create()->refresh();
        $case = EnforcementCase::factory()->create(['user_id' => $finder->id]);
        $this->advanceToRestriction($admin, $case, [RestrictedCapability::Messaging->value, RestrictedCapability::ContactReveal->value]);

        $this->actingAs($finder)->post(route('jobs.messages.store', $job), ['type' => 'MESSAGE', 'body' => 'Blocked message'])->assertForbidden();
        $this->actingAs($finder)->get(route('jobs.show', $job))->assertOk()->assertDontSee($provider->email)->assertSee('Contact reveal is temporarily restricted');
    }

    public function test_affected_user_can_appeal_and_admin_can_resolve_case(): void
    {
        [, $provider] = $this->participantsAndJob();
        $admin = User::factory()->admin()->create()->refresh();
        $case = EnforcementCase::factory()->create(['user_id' => $provider->id]);
        $this->actingAs($admin)->patch(route('admin.enforcement.update', $case), $this->actionPayload(EnforcementAction::Warning))->assertRedirect();

        $this->actingAs($provider)->post(route('enforcement-cases.appeal', $case), ['appeal_reason' => 'Please review the booking evidence.'])->assertRedirect();
        $this->assertSame('REQUESTED', $case->fresh()->appeal_status->value);

        $this->actingAs($admin)->patch(route('admin.enforcement.appeal.update', $case), ['appeal_status' => 'APPROVED', 'resolution' => 'Appeal evidence accepted.'])->assertRedirect();
        $this->assertSame('APPROVED', $case->fresh()->appeal_status->value);
        $this->assertDatabaseHas('audit_logs', ['event' => 'enforcement.appeal_reviewed', 'subject_id' => $case->id]);

        $this->actingAs($admin)->patch(route('admin.enforcement.resolve', $case), ['resolution' => 'Evidence reviewed; case closed.'])->assertRedirect();
        $this->assertSame('RESOLVED', $case->fresh()->status->value);
        $this->assertSame(UserStatus::Active, $provider->fresh()->status);
        $this->assertDatabaseHas('audit_logs', ['event' => 'enforcement.case_resolved', 'subject_id' => $case->id]);
    }

    /** @return array{User, User, Job} */
    private function participantsAndJob(): array
    {
        $finder = User::factory()->create(['role' => UserRole::ServiceFinder]);
        $provider = User::factory()->serviceProvider()->create();
        $request = ServiceRequest::factory()->create(['service_finder_id' => $finder->id, 'requested_provider_id' => $provider->id]);
        $job = Job::factory()->create(['service_request_id' => $request->id, 'service_finder_id' => $finder->id, 'provider_id' => $provider->id]);

        return [$finder->refresh(), $provider->refresh(), $job];
    }

    /** @return array<string, mixed> */
    private function actionPayload(EnforcementAction $action, array $capabilities = []): array
    {
        return ['action' => $action->value, 'severity' => 'MODERATE', 'restricted_capabilities' => $capabilities, 'duration_days' => $action === EnforcementAction::TemporaryRestriction ? 14 : null, 'resolution' => 'Admin reviewed the available evidence.'];
    }

    /** @param list<string> $capabilities */
    private function advanceToRestriction(User $admin, EnforcementCase $case, array $capabilities): void
    {
        $this->actingAs($admin)->patch(route('admin.enforcement.update', $case), $this->actionPayload(EnforcementAction::Warning));
        $this->actingAs($admin)->patch(route('admin.enforcement.update', $case), $this->actionPayload(EnforcementAction::AccountReview));
        $this->actingAs($admin)->patch(route('admin.enforcement.update', $case), $this->actionPayload(EnforcementAction::TemporaryRestriction, $capabilities));
    }
}
