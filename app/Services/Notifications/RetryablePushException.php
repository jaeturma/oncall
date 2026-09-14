<?php

namespace App\Services\Notifications;

use RuntimeException;

/**
 * Thrown by {@see PushNotificationService} when at least one device's send
 * attempt failed for a transient reason, so the queue job retries with
 * backoff (Phase M Step 42) instead of silently dropping the push.
 *
 * Known limitation: a retry re-sends to every active device, including ones
 * that already succeeded on the failed attempt — accepted the same way
 * Phase L accepts its OTP cooldown race, since the alternative (per-token
 * job fan-out with independent retry state) is a much larger change for a
 * cosmetic duplicate push, not a correctness or security issue.
 */
class RetryablePushException extends RuntimeException {}
