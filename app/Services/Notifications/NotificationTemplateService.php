<?php

namespace App\Services\Notifications;

use App\Models\NotificationTemplate;
use App\Services\OtpService;

/**
 * Renders the title/body for one event on one channel. Always falls back to
 * the catalog's safe application default (Phase M Step 25) when: no admin
 * template row exists, the row is disabled, or the relevant field is blank.
 * Replacement is a literal `str_replace` — no template engine, mirroring
 * {@see OtpService::renderTemplate()} from Phase L.
 */
class NotificationTemplateService
{
    /**
     * @param  array<string, string|int>  $placeholders  Bare names (no braces) => value.
     * @return array{title: string, body: string}
     */
    public function render(string $eventKey, string $channel, array $placeholders): array
    {
        $event = NotificationCatalog::event($eventKey);
        $template = NotificationTemplate::query()->where('event_key', $eventKey)->where('enabled', true)->first();

        $titleField = $channel === 'push' ? 'push_title' : 'database_title';
        $bodyField = $channel === 'push' ? 'push_body' : 'database_body';

        $title = filled($template?->{$titleField}) ? $template->{$titleField} : $event['default_title'];
        $body = filled($template?->{$bodyField}) ? $template->{$bodyField} : $event['default_body'];

        return [
            'title' => $this->substitute($title, $placeholders),
            'body' => $this->substitute($body, $placeholders),
        ];
    }

    /** @param  array<string, string|int>  $placeholders */
    private function substitute(string $template, array $placeholders): string
    {
        $search = [];
        $replace = [];

        foreach ($placeholders as $name => $value) {
            $search[] = '{{'.$name.'}}';
            $replace[] = (string) $value;
        }

        return str_replace($search, $replace, $template);
    }
}
