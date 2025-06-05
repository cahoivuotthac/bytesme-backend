<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Services\MomoPaymentService;
use App\Models\Order;

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

	public function handleRedirectCallback(Request $request)
	{
		$orderId = $request->query('orderId');
		$resultCode = $request->query('resultCode');
		$message = $request->query('message');
		
		Log::info('Payment redirect callback received from Momo', [
			'orderId' => $orderId,
			'resultCode' => $resultCode,
			'message' => $message
		]);

		// Find the order
		$order = null;
		if ($orderId) {
			$order = Order::where('order_id', $orderId)->first();
		}

		// Determine payment status
		$paymentStatus = 'pending';
		$statusMessage = 'Đang chờ xác nhận thanh toán...';
		
		if ($resultCode !== null) {
			switch ((int)$resultCode) {
				case 0:
					$paymentStatus = 'success';
					$statusMessage = 'Thanh toán thành công!';
					break;
				case 1005:
					$paymentStatus = 'timeout';
					$statusMessage = 'Thanh toán hết thời gian';
					break;
				default:
					$paymentStatus = 'failed';
					$statusMessage = 'Thanh toán thất bại';
					break;
			}
		}

		return view('payment.momo-callback', [
			'orderId' => $orderId,
			'order' => $order,
			'paymentStatus' => $paymentStatus,
			'statusMessage' => $statusMessage,
			'resultCode' => $resultCode,
			'message' => $message
		]);
	}
}
