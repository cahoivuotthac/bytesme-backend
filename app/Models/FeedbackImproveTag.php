<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FeedbackImproveTag extends Model
{
	protected $table = 'feedback_improve_tags';
	protected $primaryKey = 'feedback_improve_tag_id';

	protected $fillable = [
		'tag',
		'order_feedback_id',
	];

	public function order_feedback()
	{
		return $this->belongsTo(OrderFeedback::class, 'order_feedback_id', 'order_feedback_id');
	}
}
