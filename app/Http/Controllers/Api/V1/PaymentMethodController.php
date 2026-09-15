<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\PaymentSetting;
use Illuminate\Http\JsonResponse;

/** The server-controlled list of payment methods Flutter is allowed to offer — never hard-coded client-side. */
class PaymentMethodController extends Controller
{
    public function __invoke(): JsonResponse
    {
        return response()->json(['data' => PaymentSetting::current()->allowed_payment_methods]);
    }
}
