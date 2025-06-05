<?php
// app/Services/PushNotificationService.php

namespace App\Services;

use App\Models\User;
use App\Models\UserPushToken;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Laravel\Sanctum\PersonalAccessToken;

class PushNotificationService
{
	/**
	 * Send push notification to user
	 */
	public function sendToUser(User $user, array $notificationData): bool
	{
		$pushTokens = $user->expo_push_notifications();
		Log::debug('push tokens for user ' . $user->user_id . ': ', [$pushTokens]);

		if (empty($pushTokens)) {
			Log::info("No active push tokens for user {$user->user_id}");
			return false;
		}

		return $this->sendToTokens($pushTokens, $notificationData);
	}

	/**
	 * Send notification to specific tokens
	 */
	public function sendToTokens(array $pushTokens, array $notificationData): bool
	{
		if (empty($pushTokens)) {
			return false;
		}

		$messages = [];
		foreach ($pushTokens as $pushToken) {
			$messages[] = [
				'to' => $pushToken,
				'title' => $notificationData['title'] ?? 'Bytesme',
				'body' => $notificationData['body'] ?? '',
				'data' => $notificationData['data'] ?? [],
				'sound' => 'default',
				'priority' => 'high',
				'badge' => $notificationData['badge'] ?? null,
			];
		}

		$expo_push_url = config('services.expo.push_url', 'https://exp.host/--/api/v2/push/send');

		try {
			$response = Http::timeout(30)->post($expo_push_url, $messages);

			if ($response->successful()) {
				Log::info('Push notifications sent successfully', [
					'tokens_count' => count($pushTokens),
					'response' => $response->json()
				]);

				// Handle invalid tokens
				$this->handleExpoPushResponse($response->json(), $pushTokens);

				return true;
			} else {
				Log::error('Failed to send push notifications', [
					'status' => $response->status(),
					'response' => $response->body()
				]);
				return false;
			}
		} catch (\Exception $e) {
			Log::error('Exception sending push notifications: ' . $e->getMessage());
			return false;
		}
	}

	/**
	 * Handle Expo push response and remove invalid tokens
	 */
	private function handleExpoPushResponse(array $response, array $tokens): void
	{
		if (!isset($response['data']))
			return;

		foreach ($response['data'] as $index => $result) {
			if (isset($result['status']) && $result['status'] === 'error') {
				$token = $tokens[$index] ?? null;
				if ($token && isset($result['details']['error'])) {
					$error = $result['details']['error'];

					// Remove invalid tokens (later)
					// if (in_array($error, ['DeviceNotRegistered', 'InvalidCredentials', 'MessageTooBig'])) {
					// 	Perosn::where('expo_push_token', $token)->delete();
					// 	Log::info("Removed invalid push token: {$token}, error: {$error}");
					// }
				}
			}
		}
	}

	/**
	 * Add or update push token for user
	 */
	public function updatePushToken(string $accessToken, string $pushToken): bool
	{
		try {
			$personalAccessToken = PersonalAccessToken::findToken($accessToken);
			Log::debug("Personal access token: " . $personalAccessToken);
			if (!$personalAccessToken) {
				return response()->json([
					'success' => false,
					'message' => 'Invalid or expired token'
				], 401);
			}

			$personalAccessToken->expo_push_token = $pushToken;
			$personalAccessToken->save();

			Log::info("Push token added/updated for user {$personalAccessToken->tokenable_id}");
			return true;
		} catch (\Exception $e) {
			Log::error("Error adding push token for user {$personalAccessToken->tokenable_id}: " . $e->getMessage());
			return false;
		}
	}
}