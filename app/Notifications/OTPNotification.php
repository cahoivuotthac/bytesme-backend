<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class OTPNotification extends Notification
{
	use Queueable;

	protected $otp;

	/**
	 * Create a new notification instance.
	 */
	public function __construct($otp)
	{
		$this->otp = $otp;
	}

	/**
	 * Get the notification's delivery channels.
	 *
	 * @return array<int, string>
	 */
	public function via(object $notifiable): array
	{
		return ['mail'];
	}

	/**
	 * Get the mail representation of the notification.
	 */
	public function toMail($notifiable): MailMessage
	{
		return (new MailMessage)
			->subject('Your OTP Verification Code')
			->line('Your OTP code is: ' . $this->otp)
			->line('This code will expire in 10 minutes.')
			->line('If you did not request this code, please ignore this email.');
	}

	/**
	 * Get the array representation of the notification.
	 *
	 * @return array<string, mixed>
	 */
	public function toArray(object $notifiable): array
	{
		return [
			//
		];
	}
}
