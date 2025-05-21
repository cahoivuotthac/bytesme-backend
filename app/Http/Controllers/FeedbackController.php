<?php

namespace App\Http\Controllers;

use App\Models\FeedbackImproveTag;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use App\Models\FeedbackImage;
use App\Models\OrderFeedback;


class FeedbackController extends Controller
{
	public function sendFeedback(Request $request)
	{
		try {
			$validatedData = $request->validate([
				'order_id' => 'required|exists:orders,order_id',
				'content' => 'nullable|string|max:255',
				'rating' => 'required|integer|min:1|max:5',
				'improve_tags' => 'nullable',
				'is_anonymous' => 'nullable|string|in:true,false',
				// 'images' => 'nullable|array|max:3',
				'images.*' => 'image|max:2048'
			]);
		} catch (\Exception $e) {
			Log::error('FeedbackController@sendFeedback: validation error: ' . $e->getMessage());
			return response()->json([
				'message' => 'Invalid input data: ' . $e->getMessage(),
			], 400);
		}

		Log::info('All input data: ' . json_encode($request->except('images')));

		$user = Auth::user();
		$isAnonymous = $request->boolean('is_anonymous', false);

		try {
			$orderFeedback = OrderFeedback::create([
				'user_id' => $isAnonymous ? null : $user->user_id,
				'order_id' => $validatedData['order_id'],
				'feedback_content' => $validatedData['content'] ?? null,
				'num_star' => $validatedData['rating'],
			]);

			Log::debug('Order feedback created: ' . json_encode($orderFeedback));

			// Handle improve tags
			$improveTags = is_array($request->improve_tags) ? $request->improve_tags :
				(json_decode($request->improve_tags, true) ?? []);

			if (!empty($improveTags)) {
				foreach ($improveTags as $tag) {
					FeedbackImproveTag::create([
						'order_feedback_id' => $orderFeedback->order_feedback_id,
						'tag' => $tag,
					]);
				}
			}

			// Handle images
			if ($request->hasFile('images')) {
				Log::debug('Images: ' . json_encode($request->file('images')));
				foreach ($request->file('images') as $image) {
					$imageBinary = file_get_contents($image->getRealPath());

					FeedbackImage::create([
						'order_feedback_id' => $orderFeedback->order_feedback_id,
						'feedback_image' => $imageBinary,
					]);
				}
			}

			return response()->json([
				'message' => 'Feedback submitted successfully',
				'feedback_id' => $orderFeedback->order_feedback_id,
			], 201);
		} catch (\Exception $e) {
			Log::error('Error submitting feedback: ' . $e->getMessage());
			return response()->json([
				'message' => 'Error submitting feedback: ' . $e->getMessage()
			], 500);
		}
	}

}
