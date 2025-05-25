<?php

namespace App\Services;

use App\Models\MomoServiceTransaction;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use App\Models\Order;

class MomoPaymentService
{
	// public function signData($payload, $secretKey)
	// {
	// 	$rawHash = "";
	// 	ksort($payload);
	// 	foreach ($payload as $key => $value) {
	// 		$rawHash .= ($key . "=" . $value . "&");
	// 	}
	// 	$rawHash = rtrim($rawHash, "&");
	// 	Log::debug('Raw hash for signing: ' . $rawHash);
	// 	$sig = hash_hmac("sha256", $rawHash, $secretKey);
	// 	Log::debug('Signature: ' . $sig);
	// 	return $sig;
	// }

	private function signPaymentData($data, $secretKey)
	{
		$rawData = 'accessKey=' . $data['accessKey']
			. '&amount=' . $data['amount']
			. '&extraData=' . $data['extraData']
			. '&ipnUrl=' . $data['ipnUrl']
			. '&orderId=' . $data['orderId']
			. '&orderInfo=' . $data['orderInfo']
			. '&partnerCode=' . $data['partnerCode']
			. '&redirectUrl=' . $data['redirectUrl']
			. '&requestId=' . $data['requestId']
			. '&requestType=' . $data['requestType'];

		return hash_hmac('sha256', $rawData, $secretKey);
	}

	private function signRefundData($data, $secretKey)
	{
		$rawData = 'accessKey=' . $data['accessKey']
			. '&amount=' . $data['amount']
			. '&description=' . $data['description']
			. '&orderId=' . $data['orderId']
			. '&partnerCode=' . $data['partnerCode']
			. '&requestId=' . $data['requestId']
			. '&transId=' . $data['transId'];

		return hash_hmac("sha256", $rawData, $secretKey);
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
		$signature = $this->signPaymentData($payloadForSign, $secretKey);
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

	public function refundPaymentForOrder($orderId, $lang = 'vi')
	{
		$order = Order::where('order_id', $orderId)->first();
		$transaction = MomoServiceTransaction::where('order_id', $orderId)->get()->first();

		// Validate order status
		if ($order->order_status !== 'pending') {
			Log::debug('Refund request failed: Order status is not pending');
			return [
				'success' => false,
				'message' => 'Refund request failed: Order status is not pending',
				'code' => 'TOO_LATE_TO_REFUND'
			];
		}

		if (!$order) {
			Log::debug('Order not found: ' . $orderId);
			throw new \Exception('Order not found');
		}

		if (!$transaction) {
			Log::debug('Transaction not found for order: ' . $orderId);
			throw new \Exception('Transaction not found for order');
		}

		$transId = $transaction->transaction_id;
		$accessKey = config('services.momo.access_key');
		$secretKey = config('services.momo.secret_key');
		$partnerCode = config('services.momo.partner_code');
		$amount = $order->order_total_price;
		$requestId = time() . "";
		$refundOrderId = "refund_" . $orderId;
		$description = ($lang === 'en' ? 'Refund for order #' : 'Hoàn tiền cho đơn hàng #') . $orderId;

		$sig = $this->signRefundData([
			'accessKey' => $accessKey,
			'amount' => $amount,
			'description' => $description,
			'orderId' => $refundOrderId,
			'partnerCode' => $partnerCode,
			'requestId' => $requestId,
			'transId' => $transId
		], $secretKey);

		$payload = [
			'partnerCode' => $partnerCode,
			'orderId' => $refundOrderId,
			'requestId' => $requestId,
			'transId' => $transId,
			'lang' => $lang,
			'description' => $description,
			'amount' => $amount,
			'signature' => $sig,
		];
		Log::debug('Refund payload: ' . json_encode($payload));

		$m2ApiEndpoint = config('services.momo.base_url') . '/v2/gateway/api/refund';
		try {
			$response = Http::post($m2ApiEndpoint, $payload);
			$responseData = $response->json();

			// Failed
			if (!isset($responseData['resultCode']) || $responseData['resultCode'] != 0) {
				Log::debug('Refund request failed: ' . json_encode($responseData));
				return [
					'success' => false,
					'message' => 'Refund request failed according to momo: ' . $responseData['message'],
					'code' => 'MOMO_ERROR'
				];
			}

			// Success
			if ($responseData['resultCode'] == 0) {
				Log::debug('Refund request successful: ' . json_encode($responseData));
				return [
					'success' => true,
					'message' => $responseData['message'],
				];
			}
		} catch (\Exception $e) {
			Log::debug('Refund request failed: ' . $e->getMessage());
			return [
				'success' => false,
				'message' => 'Refund request failed due to error: ' . $e->getMessage(),
				'code' => 'INTERNAL_ERROR'
			];
		}
	}
}