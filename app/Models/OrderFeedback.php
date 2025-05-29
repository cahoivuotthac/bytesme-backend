<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

/**
 * Class ProductFeedback
 * 
 * @property int $order_feedback_id
 * @property string|null $product_id
 * @property int|null $user_id
 * @property string|null $feedback_content
 * @property int $num_star
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * 
 * @property Product|null $product
 * @property User|null $user
 * @property Collection|FeedbackImage[] $feedback_images
 *
 * @package App\Models
 */
class OrderFeedback extends Model
{
	protected $table = 'order_feedbacks';
	protected $primaryKey = 'order_feedback_id';

	protected $casts = [
		'user_id' => 'int', // nullable (in case of anonymous feedback)
		'order_id' => 'int',
		'feedback_content' => 'string',
		'num_star' => 'int',
	];

	protected $fillable = [
		'user_id',
		'order_id',
		'feedback_content',
		'num_star',
		'feedback_tags',
	];

	// public function product()
	// {
	// 	return $this->belongsTo(Product::class, 'product_id', 'product_id');
	// }

	public function order() {
		return $this->belongsTo(Order::class, 'order_id', 'order_id');
	}

	public function user()
	{
		return $this->belongsTo(User::class, 'user_id', 'user_id');
	}

	public function feedback_images()
	{
		return $this->hasMany(FeedbackImage::class, 'order_feedback_id', 'order_feedback_id');
	}
}
