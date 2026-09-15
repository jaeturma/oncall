<x-layouts.admin title="Review settings" description="Configure whether reviews are collected, the review/response windows, and comment length limits.">
    <form class="card card-pad grid max-w-3xl gap-6 sm:p-8" method="POST" action="{{ route('admin.settings.reviews.update') }}">
        @csrf @method('PATCH')
        <x-form.errors />

        <x-form.checkbox name="reviews_enabled" label="Reviews enabled" hint="When off, no new reviews can be submitted. Existing reviews stay visible." :checked="(bool) old('reviews_enabled', $settings->reviews_enabled)" boxed />

        <h2 class="h3 mt-2">Submission window</h2>
        <div class="grid gap-6 sm:grid-cols-2">
            <x-form.input name="review_window_days" type="number" label="Review window (days after completion)" min="1" max="365" :value="old('review_window_days', $settings->review_window_days)" required />
            <x-form.input name="max_comment_length" type="number" label="Max comment length (characters)" min="100" max="5000" :value="old('max_comment_length', $settings->max_comment_length)" required />
        </div>
        <x-form.checkbox name="comment_required" label="Written comment required" hint="When off, a star rating alone is enough to submit a review." :checked="(bool) old('comment_required', $settings->comment_required)" boxed />

        <h2 class="h3 mt-2">Provider responses</h2>
        <x-form.checkbox name="provider_response_enabled" label="Provider responses enabled" hint="Lets the reviewed provider post one public response per review." :checked="(bool) old('provider_response_enabled', $settings->provider_response_enabled)" boxed />
        <x-form.input name="response_max_length" type="number" label="Max response length (characters)" min="100" max="3000" :value="old('response_max_length', $settings->response_max_length)" required class="sm:max-w-xs" />

        <h2 class="h3 mt-2">Display</h2>
        <x-form.input name="reviews_per_page" type="number" label="Reviews per page" min="5" max="50" :value="old('reviews_per_page', $settings->reviews_per_page)" required class="sm:max-w-xs" />

        <div class="flex flex-wrap gap-3 border-t border-line pt-6">
            <x-ui.button variant="primary" data-loading-text="Saving…">Save settings</x-ui.button>
            <x-ui.button variant="secondary" :href="route('admin.review-reports.index')">View review reports</x-ui.button>
        </div>
    </form>
</x-layouts.admin>
