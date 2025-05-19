<?php

namespace App;

class Constants
{
	// User roles
	// public static const ROLE_ADMIN = 'admin';

	// Payment
	public const PAYMENT_METHOD_COD = 'cod';
	public const PAYMENT_METHOD_MOMO = 'momo';
	public const ACCEPTED_PAYMENT_METHODS = [
		self::PAYMENT_METHOD_COD,
		self::PAYMENT_METHOD_MOMO,
	];
}