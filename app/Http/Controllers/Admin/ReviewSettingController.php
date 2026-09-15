<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateReviewSettingsRequest;
use App\Models\AuditLog;
use App\Models\ReviewSetting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class ReviewSettingController extends Controller
{
    public function edit(): View
    {
        Gate::authorize('manage-review-settings');

        return view('admin.settings.reviews', ['settings' => ReviewSetting::current()]);
    }

    public function update(UpdateReviewSettingsRequest $request): RedirectResponse
    {
        Gate::authorize('manage-review-settings');
        $data = $request->validated();

        DB::transaction(function () use ($data, $request): void {
            $settings = ReviewSetting::current();
            $before = $settings->toArray();

            $settings->update([
                'reviews_enabled' => $request->boolean('reviews_enabled'),
                'review_window_days' => $data['review_window_days'],
                'comment_required' => $request->boolean('comment_required'),
                'max_comment_length' => $data['max_comment_length'],
                'provider_response_enabled' => $request->boolean('provider_response_enabled'),
                'response_max_length' => $data['response_max_length'],
                'reviews_per_page' => $data['reviews_per_page'],
            ]);

            AuditLog::create([
                'actor_id' => $request->user()->id,
                'event' => 'review_settings.updated',
                'subject_type' => ReviewSetting::class,
                'subject_id' => $settings->id,
                'before_json' => $before,
                'after_json' => $settings->fresh()->toArray(),
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);
        });

        return redirect()->route('admin.settings.reviews.edit')->with('status', 'Review settings saved.');
    }
}
