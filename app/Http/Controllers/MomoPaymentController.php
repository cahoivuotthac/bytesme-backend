<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Services\MomoPaymentService;

class MomoPaymentController extends Controller
{
	public function handleIpnCallback(Request $request)
	{
		$callbackData = $request->all();
		Log::info(
			'IPN Payment callback received from Momo with data: ',
			[json_encode($callbackData)]
		);

		try {
			$momoPaymentService = app(MomoPaymentService::class);
			$momoPaymentService->handleIpnCallback($callbackData);
		} catch (\Exception $e) {
			Log::error('Error processing Momo IPN callback: ' . $e->getMessage());
			return response()->json(status: 500);
		}

		return response()->json(['message' => 'ACK'], 204); // Response with HTTP 204 is required by Momo
	}
}
