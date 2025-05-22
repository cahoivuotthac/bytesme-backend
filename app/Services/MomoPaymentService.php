<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;

class MomoPaymentService
{
	public function signData($payload, $secretKey)
	{
		$rawHash = "";
		ksort($payload);
		foreach ($payload as $key => $value) {
			$rawHash .= ($key . "=" . $value . "&");
		}
		$rawHash = rtrim($rawHash, "&");
		Log::debug('Raw hash for signing: ' . $rawHash);
		$sig = hash_hmac("sha256", $rawHash, $secretKey);
		Log::debug('Signature: ' . $sig);
		return $sig;
	}

	public function createPaymentIntent($orderId, $orderInfo, $amount, $lang = 'vi')
	{
		// Default order info
		$defaultOrderInfo = 'Thanh toán đơn hàng Bytesme Bakery bằng ví MoMo';

		// Validate input
		if (empty($orderId) || empty($amount)) {
			Log::debug('Payment requset failed: Invalid order ID or amount');
			return response()->json(['message' => 'Invalid order ID or amount'], 400);
		}

		Log::debug("App url is: " . config('app.url'));

		// Only include fields required for signature in the correct order
		$payloadForSign = [
			'accessKey' => config('services.momo.access_key'),
			'amount' => $amount,
			'extraData' => '',
			'ipnUrl' => config('app.url') . '/order/payment/momo/ipn-callback',
			'orderId' => $orderId,
			'orderInfo' => $defaultOrderInfo,
			'partnerCode' => config('services.momo.partner_code'),
			'redirectUrl' => config('app.url') . '/order/payment/momo/redirect-callback',
			'requestId' => time() . "",
			'requestType' => 'captureWallet',
		];

		// The payload sent to MoMo include additional fields
		$payload = $payloadForSign;
		$payload['partnerName'] = config('services.momo.partner_name');
		$payload['lang'] = $lang;
		$payload['autoCapture'] = true;
		$payload['requestType'] = 'captureWallet';

		$secretKey = config('services.momo.secret_key');
		$signature = $this->signData($payloadForSign, $secretKey);
		$payload['signature'] = $signature;

		// Send the request to Momo
		$m2ApiEndpoint = config('services.momo.base_url') . '/v2/gateway/api/create';
		try {
			$response = Http::post($m2ApiEndpoint, $payload);
			$responseData = $response->json();
			if (!isset($responseData['resultCode']) || $responseData['resultCode'] != '0') {
				Log::debug('Payment request failed: ' . json_encode($responseData));
				return [
					'success' => false,
					'message' => 'Payment request failed',
				];
			}
			return [
				'success' => true,
				'payUrls' => [
					'web' => $responseData['payUrl'],
					'qr' => $responseData['qrCodeUrl'],
					'mobile' => $responseData['deeplink'],
				]
			];
		} catch (\Exception $e) {
			Log::debug('Payment request failed: ' . $e->getMessage());
			return [
				'success' => false,
				'message' => 'Payment request failed: ' . $e->getMessage()
			];
		}
	}
}