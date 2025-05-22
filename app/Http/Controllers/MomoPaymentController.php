<?php

namespace App\Http\Controllers;

use App\Constants;
use App\Models\Order;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Events\OnlinePaymentEvent;
use App\Notifications\OnlinePaymentNotification;
use App\Services\MomoPaymentService;

class MomoPaymentController extends Controller
{
	// Test route
	public function createPaymentIntent(Request $request)
	{
		// Validate input request
		Log::info('Create MoMo intent request received: ' . json_encode($request->all()));

		try {
			$request->validate([
				'amount' => 'required|integer|min:10000',
				'order_id' => 'required|integer',
				'order_info' => 'nullable|string',
				'language' => 'nullable|string|in:en,vi',
			]);
		} catch (\Exception $e) {
			Log::debug('Payment requset failed: ' . $e->getMessage());
			return response()->json(['message' => 'Invalid request: ', $e->getMessage()], 400);
		}

		$userLang = $request->input('language');
		if ($userLang === 'en') {
			$lang = 'en';
			$defaultOrderInfo = 'Pay for Bytesme order with MoMo wallet';
		} else {
			$lang = 'vi'; // Default to Vietnamese if no valid language is provided
			$defaultOrderInfo = 'Thanh toán đơn hàng Bytesme bằng ví MoMo';
		}

		// Only include fields required for signature in the correct order
		$payloadForSign = [
			'accessKey' => config('services.momo.access_key'),
			'amount' => $request->input('amount'),
			'extraData' => '',
			'ipnUrl' => 'https://8e50-2a09-bac5-d469-25af-00-3c1-3f.ngrok-free.app/order/payment/momo/ipn-callback',
			'orderId' => $request->input('order_id'),
			'orderInfo' => $request->input('order_info') ?? $defaultOrderInfo,
			'partnerCode' => config('services.momo.partner_code'),
			'redirectUrl' => 'https://8e50-2a09-bac5-d469-25af-00-3c1-3f.ngrok-free.app/order/payment/momo/redirect-callback',
			'requestId' => time() . "",
			'requestType' => 'captureWallet',
		];

		// The payload sent to MoMo include additional fields
		$payload = $payloadForSign;
		$payload['partnerName'] = config('services.momo.partner_name');
		$payload['lang'] = $lang;
		$payload['autoCapture'] = true;
		// $payload['ipnUrl'] = 'https://8e50-2a09-bac5-d469-25af-00-3c1-3f.ngrok-free.app/order/payment/momo/ipn-callback';
		// $payload['redirectUrl'] = 'https://8e50-2a09-bac5-d469-25af-00-3c1-3f.ngrok-free.app/order/payment/momo/redirect-callback';
		$payload['requestType'] = 'captureWallet';

		$secretKey = config('services.momo.secret_key');
		$signature = app(MomoPaymentService::class)
			->signData($payloadForSign, $secretKey);
		$payload['signature'] = $signature;

		// Send the request to MoMo
		$m2ApiEndpoint = config('services.momo.base_url') . '/v2/gateway/api/create';
		try {
			$response = Http::post($m2ApiEndpoint, $payload);
			$responseData = $response->json();
			if (!isset($responseData['resultCode']) || $responseData['resultCode'] != '0') {
				Log::debug('Payment requset failed: ' . json_encode($responseData));
				return response()->json(['message' => 'Payment request failed'], 500);
			}
			return response()->json($responseData);
		} catch (\Exception $e) {
			Log::debug('Payment requset failed: ' . $e->getMessage());
			return response()->json(['message' => 'Payment request failed'], 500);
		}
	}

	public function handleIpnCallback(Request $request)
	{
		// Validate signature
		$receivedData = $request->all();
		Log::debug('IPN Payment callback received from Momo: ', [json_encode($receivedData)]);

		// Find the order by orderId
		$order = Order::where('order_id', $receivedData['orderId'])->first();
		$user = User::where('user_id', $order->user_id)->first();

		if (!$order) {
			Log::debug('Order not found: ' . json_encode($receivedData));
			return response()->json([
				'message' =>
					'Order not found for IPN callback, requestId:' . $receivedData['requestId'] . ', orderId:' . $receivedData['orderId'],
			], 404);
		}
		if (!$user) {
			Log::debug('User not found: ' . json_encode($receivedData));
			return response()->json([
				'message' =>
					'User not found for IPN callback, requestId:' . $receivedData['requestId'] . ', userId:' . $order->user_id,
			], 404);
		}

		// Payment was unsuccessful
		if ((int) $receivedData['resultCode'] !== 0) {
			Log::debug('Payment successful: ' . json_encode($receivedData));
			Log::debug('Payment failed: ' . json_encode($receivedData));
			$order->order_is_paid = false;
			$order->order_status = 'cancelled'; // cancel the order on payment failure
			$order->save();

			// Send direct websocket broadcast
			broadcast(new OnlinePaymentEvent(
				$order->order_id,
				"failed", // payment stauts
				Constants::PAYMENT_METHOD_MOMO
			))->toOthers();

			// Send notification & events
			$user->notify(new OnlinePaymentNotification(
				$order->order_id,
				"failed", // payment stauts
				Constants::PAYMENT_METHOD_MOMO
			));

			return response()->json(['message' => 'Acknowledge payment unsuccessful'], 200);
		}

		// Payment was successful
		if ((int) $receivedData['resultCode'] === 0) {
			Log::debug('Payment successful: ' . json_encode($receivedData));
			$order->order_is_paid = true;
			$order->order_status = 'pending'; // set order status to pending (previously was online_payment_pending)
			$order->order_payment_date = now();
			$order->save();

			// Send direct websocket broadcast
			broadcast(new OnlinePaymentEvent(
				$order->order_id,
				"success", // payment stauts
				Constants::PAYMENT_METHOD_MOMO
			))->toOthers();

			// Send notification & events
			$user->notify(new OnlinePaymentNotification(
				$order->order_id,
				"success", // payment stauts
				Constants::PAYMENT_METHOD_MOMO
			));
		} else if ((int) $receivedData['resultCode'] === 1005) {
			Log::debug('Payment timed out: ' . json_encode($receivedData));
			$order->order_is_paid = false;
			$order->order_status = 'cancelled'; // set order status to online_payment_pending
			$order->save();

			// Send direct websocket broadcast
			broadcast(new OnlinePaymentEvent(
				$order->order_id,
				"timeout", // payment stauts
				Constants::PAYMENT_METHOD_MOMO
			))->toOthers();

			// Send notification & events
			$user->notify(new OnlinePaymentNotification(
				$order->order_id,
				"timeout", // payment stauts
				Constants::PAYMENT_METHOD_MOMO
			));
		}

		return response()->json(['message' => 'ACK'], 204); // Response with HTTP 204 required by Momo
	}
}
