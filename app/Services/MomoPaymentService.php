<?php

namespace App\Services;

use App\Notifications\OnlinePaymentNotification;
use App\Models\MomoServiceTransaction;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Events\OnlinePaymentEvent;
use App\Models\Order;
use App\Models\User;
use App\Constants;

class MomoPaymentService
{
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

	public function createPaymentIntent($order, $orderInfo, $amount, $lang = 'vi')
	{
		// Default order info
		$defaultOrderInfo = 'Thanh toán đơn hàng Bytesme Bakery bằng ví MoMo';

		// Validate input
		if (!$order || !$order->order_id || empty($amount)) {
			Log::debug('Payment requset failed: Invalid order or amount');
			return response()->json(['message' => 'Invalid order or amount'], 400);
		}

		// Only include fields required for signature in the correct order
		$payloadForSign = [
			'accessKey' => config('services.momo.access_key'),
			'amount' => $amount,
			'extraData' => '',
			'ipnUrl' => config('app.url') . '/order/payment/momo/ipn-callback',
			'orderId' => $order->order_id,
			'orderInfo' => $ordrInfo ?? $defaultOrderInfo,
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

			// Try sending notification to user
			try {

				$user = $order->user;
				$user->notify(new OnlinePaymentNotification(
					$order->order_id,
					"refunded",
					Constants::PAYMENT_METHOD_MOMO,
				));
			} catch (\Exception $e) {
				Log::error('(MomoPaymentService::refundPaymentForOrder) Failed to send notification due to error: ' . $e->getMessage());
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

	public function handleIpnCallback($callbackData)
	{
		// Find the order by orderId
		$order = Order::where('order_id', $callbackData['orderId'])->first();
		$user = User::where('user_id', $order->user_id)->first();

		if (!$order) {
			Log::debug('Order not found: ' . json_encode($callbackData));
			return response()->json([
				'message' =>
					'Order not found for IPN callback, requestId:' . $callbackData['requestId'] . ', orderId:' . $callbackData['orderId'],
			], 404);
		}
		if (!$user) {
			Log::debug('User not found: ' . json_encode($callbackData));
			return response()->json([
				'message' =>
					'User not found for IPN callback, requestId:' . $callbackData['requestId'] . ', userId:' . $order->user_id,
			], 404);
		}

		// Payment was unsuccessful
		if ((int) $callbackData['resultCode'] !== 0) {
			Log::debug('Payment successful: ' . json_encode($callbackData));
			Log::debug('Payment failed: ' . json_encode($callbackData));
			$order->order_is_paid = false;
			$order->order_status = 'cancelled'; // cancel the order on payment failure
			$order->save();

			// Send direct websocket broadcast to user
			broadcast(new OnlinePaymentEvent(
				$order->order_id,
				"failed", // payment stauts
				Constants::PAYMENT_METHOD_MOMO
			))->toOthers();

			// Send notification to user
			$user->notify(new OnlinePaymentNotification(
				$order->order_id,
				"failed", // payment stauts
				Constants::PAYMENT_METHOD_MOMO
			));

			// Send notification to admin
			User::where('role_type', 1)
				->first()
				->notify(new OnlinePaymentNotification(
					$order->order_id,
					"failed", // payment stauts
					Constants::PAYMENT_METHOD_MOMO
				));

			return response()->json(['message' => 'Acknowledge payment unsuccessful'], 200);
		}

		// Payment was successful
		if ((int) $callbackData['resultCode'] === 0) {
			Log::debug('Payment successful: ' . json_encode($callbackData));
			$order->order_is_paid = true;
			$order->order_status = 'pending'; // set order status to pending (previously was online_payment_pending)
			$order->order_payment_date = now();
			$order->save();

			// Save the transaction in the Momo service store
			MomoServiceTransaction::create(
				[
					'transaction_id' => $callbackData['transId'],
					'order_id' => $order->order_id
				],
			);

			// Send direct websocket broadcast to user
			broadcast(new OnlinePaymentEvent(
				$order->order_id,
				"success",
				Constants::PAYMENT_METHOD_MOMO
			))->toOthers();

			// Send notification to user
			$user->notify(new OnlinePaymentNotification(
				$order->order_id,
				"success", // payment stauts
				Constants::PAYMENT_METHOD_MOMO
			));

			// Send notification to admin
			User::where('role_type', 1)
				->first()
				->notify(new OnlinePaymentNotification(
					$order->order_id,
					"success",
					Constants::PAYMENT_METHOD_MOMO
				));
		} else if ((int) $callbackData['resultCode'] === 1005) {
			Log::debug('Payment timed out: ' . json_encode($callbackData));
			$order->order_is_paid = false;
			$order->order_status = 'cancelled'; // set order status to online_payment_pending
			$order->save();

			// Send direct websocket broadcast
			broadcast(new OnlinePaymentEvent(
				$order->order_id,
				"timeout",
				Constants::PAYMENT_METHOD_MOMO
			))->toOthers();

			// Send notification & events
			$user->notify(new OnlinePaymentNotification(
				$order->order_id,
				"timeout",
				Constants::PAYMENT_METHOD_MOMO
			));
		}

	}
}