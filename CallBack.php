<?php

require 'Payizone.php';

$payizone = new Payizone();

$payizone->set_config([
    'merchant_id' => '', # Payizone Merchant ID
    'merchant_mail' => '', # Payizone Merchant Mail
    'merchant_secret' => '' # Payizone Merchant Secret
]);


$callback = $payizone->callback();
if ($callback == null) {
	echo $payizone->get_error();
} else {
	print_r($callback);
}