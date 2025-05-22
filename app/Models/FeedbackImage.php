<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

/**
 * Class FeedbackImage
 * 
 * @property int $feedback_image_id
 * @property int|null $product_feedback_id
 * @property string|null $feedback_image
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * 
 * @property OrderFeedback|null $product_feedback
 *
 * @package App\Models
 */
class FeedbackImage extends Model
{
	protected $table = 'feedback_images';
	protected $primaryKey = 'feedback_image_id';

	protected $casts = [
		'order_feedback_id' => 'int',
		'feedback_image' => 'string',
	];

	protected $fillable = [
		'order_feedback_id',
		'feedback_image',
	];

	public function order_feedback()
	{
		return $this->belongsTo(related: OrderFeedback::class);
	}
}
