<?php

namespace App\Enums;

/**
 * How `PushNotificationService` classifies an FCM send failure so
 * `SendPushNotificationJob` knows whether to retry, give up, or deactivate
 * the token (Phase M Step 42).
 */
enum PushFailureType: string
{
    case Retryable = 'RETRYABLE';
    case Permanent = 'PERMANENT';
    case InvalidToken = 'INVALID_TOKEN';
    case ConfigurationError = 'CONFIGURATION_ERROR';
}
