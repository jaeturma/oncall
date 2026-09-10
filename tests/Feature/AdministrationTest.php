<?php

namespace Tests\Feature;

use App\Enums\JobStatus;
use App\Enums\ReportCategory;
use App\Enums\ReportStatus;
use App\Enums\UserRole;
use App\Models\AuditLog;
use App\Models\EnforcementCase;
use App\Models\Job;
use App\Models\ProviderProfile;
use App\Models\Service;
use App\Models\ServiceRequest;
use App\Models\User;
use App\Models\UserReport;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdministrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_from_administration(): void
    {
        $this->get(route('admin.dashboard'))->assertRedirect(route('login'));
    }

    public function test_non_admin_cannot_access_administration_dashboards(): void
    {
        $user = User::factory()->create(['role' => UserRole::ServiceFinder]);

        foreach (['admin.dashboard', 'admin.users.index', 'admin.providers.index', 'admin.jobs.index', 'admin.reports.index', 'admin.audit-logs.index'] as $route) {
            $this->actingAs($user)->get(route($route))->assertForbidden();
        }
    }

    public function test_admin_dashboard_summarizes_existing_operational_modules(): void
    {
        $admin = User::factory()->admin()->create()->refresh();
        User::factory()->count(2)->create();
        ProviderProfile::factory()->create();
        Service::factory()->create();
        $job = Job::factory()->create(['status' => JobStatus::InProgress]);
        UserReport::factory()->create(['job_id' => $job->id, 'status' => ReportStatus::Submitted]);
        AuditLog::factory()->create(['actor_id' => $admin->id, 'event' => 'admin.test', 'subject_type' => User::class, 'subject_id' => $admin->id]);

        $this->actingAs($admin)->get(route('admin.dashboard'))->assertOk()->assertSee('Users')->assertSee('Providers')->assertSee('Active jobs')->assertSee('Open reports')->assertSee('Finance summary');
    }

    public function test_admin_can_filter_user_directory_by_role_and_status(): void
    {
        $admin = User::factory()->admin()->create()->refresh();
        $provider = User::factory()->serviceProvider()->create(['name' => 'Visible Provider']);
        User::factory()->create(['name' => 'Hidden Finder', 'role' => UserRole::ServiceFinder]);

        $this->actingAs($admin)->get(route('admin.users.index', ['role' => UserRole::ServiceProvider->value]))->assertOk()->assertSee($provider->name)->assertDontSee('Hidden Finder');
    }

    public function test_admin_provider_and_job_dashboards_show_operational_records(): void
    {
        $admin = User::factory()->admin()->create()->refresh();
        $profile = ProviderProfile::factory()->create();
        $request = ServiceRequest::factory()->create(['requested_provider_id' => $profile->user_id]);
        $job = Job::factory()->create(['service_request_id' => $request->id, 'service_finder_id' => $request->service_finder_id, 'provider_id' => $profile->user_id]);

        $this->actingAs($admin)->get(route('admin.providers.index'))->assertOk()->assertSee($profile->user->name);
        $this->actingAs($admin)->get(route('admin.jobs.index'))->assertOk()->assertSee('#'.$job->id)->assertSee($job->serviceRequest->service->name);
    }

    public function test_admin_report_queue_links_to_enforcement_review_and_escapes_description(): void
    {
        $admin = User::factory()->admin()->create()->refresh();
        $reporter = User::factory()->create();
        $reportedUser = User::factory()->create();
        $description = '<script>alert("report")</script> Incident';
        $report = UserReport::factory()->create(['reporter_id' => $reporter->id, 'reported_user_id' => $reportedUser->id, 'category' => ReportCategory::UnsafeBehavior, 'description' => $description]);
        $case = EnforcementCase::factory()->create(['user_id' => $reportedUser->id, 'related_report_id' => $report->id]);

        $this->actingAs($admin)->get(route('admin.reports.index'))->assertOk()->assertSee($description)->assertDontSee($description, false)->assertSee(route('admin.enforcement.show', $case), false);
    }

    public function test_admin_audit_dashboard_lists_actor_and_event(): void
    {
        $admin = User::factory()->admin()->create(['name' => 'Audit Administrator'])->refresh();
        AuditLog::factory()->create(['actor_id' => $admin->id, 'event' => 'enforcement.action_applied', 'subject_type' => User::class, 'subject_id' => $admin->id]);

        $this->actingAs($admin)->get(route('admin.audit-logs.index'))->assertOk()->assertSee('enforcement.action_applied')->assertSee('Audit Administrator');
    }
}
