<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class NotificationController extends Controller
{
	/**
	 * Get all notifications for the authenticated user
	 * 
	 * @param Request $request
	 * @return \Illuminate\Http\JsonResponse
	 */
	public function getNotifications(Request $request)
	{
		try {
			$user = Auth::user();
			$limit = $request->input('limit', 15); // Default to 15 notifications
			$unreadOnly = $request->boolean('unread_only', false);

			// Get notifications based on the filters
			if ($unreadOnly) {
				$notifications = $user->unreadNotifications()->limit($limit)->get();
			} else {
				$notifications = $user->notifications()->limit($limit)->get();
			}

			return response()->json([
				'success' => true,
				'notifications' => $notifications,
				'unread_count' => $user->unreadNotifications()->count()
			]);
		} catch (\Exception $e) {
			Log::error('Failed to fetch notifications: ' . $e->getMessage());
			return response()->json([
				'success' => false,
				'message' => 'Failed to fetch notifications'
			], 500);
		}
	}

	/**
	 * Get notification details by ID
	 * 
	 * @param Request $request
	 * @param string $id
	 * @return \Illuminate\Http\JsonResponse
	 */
	public function getNotificationById(Request $request, $id)
	{
		try {
			$user = Auth::user();
			$notification = $user->notifications()->where('id', $id)->first();

			if (!$notification) {
				return response()->json([
					'success' => false,
					'message' => 'Notification not found'
				], 404);
			}

			return response()->json([
				'success' => true,
				'notification' => $notification
			]);
		} catch (\Exception $e) {
			Log::error('Failed to fetch notification: ' . $e->getMessage());
			return response()->json([
				'success' => false,
				'message' => 'Failed to fetch notification'
			], 500);
		}
	}

	/**
	 * Mark notifications as read
	 * 
	 * @param Request $request
	 * @return \Illuminate\Http\JsonResponse
	 */
	public function markAsRead(Request $request)
	{
		try {
			$user = Auth::user();
			$ids = $request->input('notification_ids');

			if ($ids) {
				// Mark specific notifications as read
				$user->unreadNotifications()
					->whereIn('id', $ids)
					->update(['read_at' => now()]);
			} else {
				// Mark all notifications as read
				$user->unreadNotifications()
					->update(['read_at' => now()]);
			}

			return response()->json([
				'success' => true,
				'message' => 'Notifications marked as read',
				'unread_count' => $user->unreadNotifications()->count()
			]);
		} catch (\Exception $e) {
			Log::error('Failed to mark notifications as read: ' . $e->getMessage());
			return response()->json([
				'success' => false,
				'message' => 'Failed to mark notifications as read'
			], 500);
		}
	}

	public function markAllAsRead(Request $request)
	{
		try {
			$user = Auth::user();
			$user->unreadNotifications()->update(['read_at' => now()]);

			return response()->json([
				'success' => true,
				'message' => 'All notifications marked as read',
				'unread_count' => $user->unreadNotifications()->count()
			]);
		} catch (\Exception $e) {
			Log::error('Failed to mark all notifications as read: ' . $e->getMessage());
			return response()->json([
				'success' => false,
				'message' => 'Failed to mark all notifications as read'
			], 500);
		}
	}

	/**
	 * Delete notifications
	 * 
	 * @param Request $request
	 * @return \Illuminate\Http\JsonResponse
	 */
	public function deleteNotifications(Request $request)
	{
		try {
			$user = Auth::user();
			$ids = $request->input('notification_ids');

			if ($ids) {
				// Delete specific notifications
				$user->notifications()
					->whereIn('id', $ids)
					->delete();

				$message = 'Selected notifications deleted successfully';
			} else {
				// Delete all notifications
				$user->notifications()->delete();
				$message = 'All notifications deleted successfully';
			}

			return response()->json([
				'success' => true,
				'message' => $message,
				'unread_count' => $user->unreadNotifications()->count()
			]);
		} catch (\Exception $e) {
			Log::error('Failed to delete notifications: ' . $e->getMessage());
			return response()->json([
				'success' => false,
				'message' => 'Failed to delete notifications'
			], 500);
		}
	}
}
