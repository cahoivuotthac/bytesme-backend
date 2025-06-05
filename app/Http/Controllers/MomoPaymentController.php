<?php

namespace App\Http\Controllers;

use App\Constants;
use App\Models\Order;
use App\Models\User;
use App\Models\MomoServiceTransaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Events\OnlinePaymentEvent;
use App\Notifications\OnlinePaymentNotification;
use App\Services\MomoPaymentService;

class MomoPaymentController extends Controller
{
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

			// Send noti to admin
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
		if ((int) $receivedData['resultCode'] === 0) {
			Log::debug('Payment successful: ' . json_encode($receivedData));
			$order->order_is_paid = true;
			$order->order_status = 'pending'; // set order status to pending (previously was online_payment_pending)
			$order->order_payment_date = now();
			$order->save();

			// Save the transaction in the Momo service store
			MomoServiceTransaction::create(
				[
					'transaction_id' => $receivedData['transId'],
					'order_id' => $order->order_id
				],
			);

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
			// Send noti to admin
			User::where('role_type', 1)
				->first()
				->notify(new OnlinePaymentNotification(
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
