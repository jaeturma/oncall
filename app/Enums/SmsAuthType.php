<?php

namespace App\Enums;

/** How `GenericHttpSmsProvider` authenticates to the configured API. */
enum SmsAuthType: string
{
    case BearerToken = 'BEARER_TOKEN';
    case ApiKeyHeader = 'API_KEY_HEADER';
    case BasicAuth = 'BASIC_AUTH';
    case QueryParam = 'QUERY_PARAM';
}
