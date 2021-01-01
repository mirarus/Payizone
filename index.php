<?php

require 'Payizone.php'; 

$payizone = new Payizone();

$payizone->set_config([
	'merchant_id' => '', # Payizone Merchant ID
	'merchant_mail' => '', # Payizone Merchant Mail
	'merchant_secret' => '' # Payizone Merchant Secret
]);

$payizone->set_callback_url('http://127.0.0.1/CallBack.php');

$payizone->set_order_id(1); # Order ID

# $payizone->set_installment(1); # Installment Count

$payizone->set_product([
	'price' => '', # Product Price
	'description' => '' # Product Description
]);

$payizone->set_buyer([
	'phone' => '' # Buyer Phone Number
]);

$payizone->set_card([
	'fullname' => '', #Credit Card Holder FullName
	'number' => '', # Credit Card Number
	'exp_month' => '', # Credit Card Exp Month
	'exp_year' => '', # Credit Card Exp Year
	'cvc' => '' # Credit Card CVC Number
]);


$init = $payizone->init();
if ($init == null) {
	print_r($payizone->get_error());
} else {
	echo $init;
}