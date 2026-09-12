<?php

namespace Tests\Feature;

use App\Enums\VerificationStatus;
use App\Models\Municipality;
use App\Models\ProviderDocument;
use App\Models\ProviderProfile;
use App\Models\Province;
use App\Models\Service;
use App\Models\User;
use App\Services\MobileVerificationService;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class ContactVerificationTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_registering_sends_an_email_verification_notification(): void
    {
        Notification::fake();

        $this->post(route('register'), [
            'name' => 'Nora Santos',
            'email' => 'nora@example.test',
            'role' => 'SERVICE_FINDER',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ])->assertRedirect(route('dashboard'));

        $user = User::where('email', 'nora@example.test')->sole();
        $this->assertNull($user->email_verified_at);
        Notification::assertSentTo($user, VerifyEmail::class);
    }

    public function test_signed_verification_link_marks_email_verified(): void
    {
        $user = User::factory()->unverified()->create();
        $url = URL::temporarySignedRoute('verification.verify', now()->addMinutes(60), ['id' => $user->id, 'hash' => sha1($user->email)]);

        $this->actingAs($user)->get($url)->assertRedirect(route('verification.index'));

        $this->assertTrue($user->refresh()->hasVerifiedEmail());
    }

    public function test_tampered_verification_link_is_rejected(): void
    {
        $user = User::factory()->unverified()->create();
        $url = URL::temporarySignedRoute('verification.verify', now()->addMinutes(60), ['id' => $user->id, 'hash' => sha1('someone-else@example.test')]);

        $this->actingAs($user)->get($url)->assertForbidden();

        $this->assertFalse($user->refresh()->hasVerifiedEmail());
    }

    public function test_unverified_user_can_request_a_new_link(): void
    {
        Notification::fake();
        $user = User::factory()->unverified()->create();

        $this->actingAs($user)->post(route('verification.send'))->assertRedirect();

        Notification::assertSentTo($user, VerifyEmail::class);
    }

    public function test_already_verified_user_resend_is_a_no_op(): void
    {
        Notification::fake();
        $user = User::factory()->create();

        $this->actingAs($user)->post(route('verification.send'))->assertRedirect(route('verification.index'));

        Notification::assertNothingSent();
    }

    public function test_user_requests_and_confirms_a_mobile_verification_code(): void
    {
        $user = User::factory()->create(['phone' => null]);

        $this->actingAs($user)->post(route('verification.mobile.send'), ['phone' => '09171234567'])->assertRedirect(route('verification.index'));
        $this->assertSame('09171234567', $user->refresh()->phone);
        $this->assertFalse($user->isMobileVerified());

        $code = app(MobileVerificationService::class)->issueCode($user);

        $this->actingAs($user)->post(route('verification.mobile.verify'), ['code' => $code])->assertRedirect(route('verification.index'));
        $this->assertTrue($user->refresh()->isMobileVerified());
    }

    public function test_wrong_mobile_code_is_rejected(): void
    {
        $user = User::factory()->create(['phone' => '09171234567']);
        app(MobileVerificationService::class)->issueCode($user);

        $this->actingAs($user)->post(route('verification.mobile.verify'), ['code' => '000000'])->assertSessionHasErrors('code');
        $this->assertFalse($user->refresh()->isMobileVerified());
    }

    public function test_mobile_number_must_be_unique(): void
    {
        User::factory()->create(['phone' => '09171234567']);
        $user = User::factory()->create(['phone' => null]);

        $this->actingAs($user)->post(route('verification.mobile.send'), ['phone' => '09171234567'])->assertSessionHasErrors('phone');
    }

    public function test_guest_provider_profile_shows_mobile_and_email_badges_without_revealing_contact_details(): void
    {
        $province = Province::factory()->create();
        $municipality = Municipality::factory()->for($province)->create();
        $service = Service::factory()->create();
        $provider = User::factory()->serviceProvider()->mobileVerified()->create(['identity_verification_status' => VerificationStatus::Verified]);
        ProviderDocument::factory()->for($provider)->create(['status' => VerificationStatus::Verified, 'expires_at' => null]);
        $profile = ProviderProfile::factory()->for($provider, 'user')->create(['province_id' => $province->id, 'municipality_id' => $municipality->id, 'verification_status' => VerificationStatus::Verified]);
        $profile->providerServices()->create(['service_id' => $service->id]);

        $response = $this->get(route('providers.show', $profile));

        $response->assertOk()->assertSee('Mobile Verified')->assertSee('Email Verified')->assertDontSee($provider->phone)->assertDontSee($provider->email);
    }
}
