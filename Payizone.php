<?php

/**
 *
 * Payizone Pos Basic PHP Class
 *
 * PHP versions 5 and 7
 *
 * @author  Mirarus <aliguclutr@gmail.com>
 * @version 1.0
 * @link https://github.com/mirarus/Payizone
 *
 */

class Payizone
{

	private 
	$config = [],
	$product = [],
	$buyer = [],
	$card = [],
	$callback_url,
	$order_id,
	$installment,
	$error;

	public function set_config($data=[])
	{
		if ($data['merchant_id'] == null || $data['merchant_mail'] == null || $data['merchant_secret'] == null) {
			$this->error = "Missing api information.";
		} else {
			$this->config = [
				'merchant_id'     => $data['merchant_id'],
				'merchant_mail'   => $data['merchant_mail'],
				'merchant_secret' => $data['merchant_secret']
			];
		}
	}
	
	public function set_product($data=[])
	{
		if ($data['price'] == null || $data['description'] == null) {
			$this->error = "Missing product information.";
		} else {
			if ($data['price'] >= 1) {
				$this->product = [
					'price'       => $data['price'],
					'description' => $data['description']
				];
			} else {
				$this->error = "Amount Should Be Minimum 1";
			}
		}
	}
	
	public function set_buyer($data=[])
	{
		if ($data['phone'] == null) {
			$this->error = "Missing buyer information.";
		} else {
			$this->buyer = [
				'phone' => $data['phone']
			];
		}
	}

	public function set_card($data=[])
	{
		if ($data['fullname'] == null || $data['number'] == null || $data['exp_month'] == null || $data['exp_year'] == null || $data['cvc'] == null) {
			$this->error = "Missing Card information.";
		} else {
			if (strlen($data['exp_month']) == 2 && strlen($data['exp_year']) == 2 && strlen($data['cvc']) == 3) {
				$this->card = [
					'fullname'  => $data['fullname'],
					'number'    => $data['number'],
					'exp_month' => $data['exp_month'],
					'exp_year'  => $data['exp_year'],
					'cvc'       => $data['cvc']
				];
			} else {
				$this->error = "Missing Card information.";
			}
		}
	}

	public function set_callback_url($url)
	{
		$this->callback_url = $url;
	}

	public function set_order_id($order_id)
	{
		$this->order_id = $order_id;
	}

	public function set_installment($installment)
	{
		if ($installment <= 12) {
			$this->installment = $installment;
		} else {
			$this->error = "Max ınstallment Count 12";
		}
	}

	public function get_error()
	{
		if ($this->error != null) {
			return $this->error;
		}
	}

	private function max_installment()
	{
		if ($this->config == null || $this->product == null || $this->card == null) {
			$this->error = "Insufficient Data";
		} else {

			$merchant_id     = $this->config['merchant_id'];
			$merchant_mail   = $this->config['merchant_mail'];
			$merchant_secret = $this->config['merchant_secret'];

			$hash  = hash("sha256", $merchant_id . "|" . $merchant_mail . "|" . $merchant_secret);
			$price = number_format($this->product['price'], 2, '.', '');
			

			$ch = curl_init();
			curl_setopt_array($ch, [
				CURLOPT_URL => "https://getapi.payizone.com/installment",
				CURLOPT_RETURNTRANSFER => true,
				CURLOPT_SSL_VERIFYPEER => false,
				CURLOPT_SSL_VERIFYHOST => false,
				CURLOPT_FRESH_CONNECT => true,
				CURLOPT_TIMEOUT => 30,
				CURLOPT_POST => true,
				CURLOPT_POSTFIELDS => [
					'hash'       => $hash,
					'apiSecret'  => $merchant_secret,
					'cardNumber' => $this->card['number'],
					'amount'     => $price
				]
			]);

			$response = @curl_exec($ch);
			if (curl_errno($ch)) {
				$this->error = curl_error($ch);
			} else {
				$result = json_decode($response, true);

				if ($result['status'] == true) {
					return $result['installments'];
				} else {
					$this->error = $result['message'];
				}
			}
			curl_close($ch);
		}
	}

	public function init()
	{
		if ($this->config == null || $this->product == null || $this->buyer == null || $this->card == null || $this->order_id == null || $this->callback_url == null) {
			$this->error = "Insufficient Data";
		} else {

			$merchant_id     = $this->config['merchant_id'];
			$merchant_mail   = $this->config['merchant_mail'];
			$merchant_secret = $this->config['merchant_secret'];

			$hash       = hash("sha256", $merchant_id . "|" . $merchant_mail . "|" . $merchant_secret);
			$other_code = (time() . 'PAYIZONE' . $this->order_id);
			$price      = number_format($this->product['price'], 2, '.', '');
			$post		= [];

			if ($this->installment != null) {
				$installment     = (($this->installment == 1) ? 0 : $this->installment) - 1;
				$max_installment = $this->max_installment();
				$new_installment = @$max_installment[@$installment];
				if ($new_installment != null) {
					$post['installment'] = $installment['installment'];
					$post['payHash']     = $installment['token'];
					$post['amount']      = $installment['amount'];
				}
			}

			$ch = curl_init();
			curl_setopt_array($ch, [
				CURLOPT_URL => "https://getapi.payizone.com",
				CURLOPT_RETURNTRANSFER => true,
				CURLOPT_SSL_VERIFYPEER => false,
				CURLOPT_SSL_VERIFYHOST => false,
				CURLOPT_FRESH_CONNECT => true,
				CURLOPT_TIMEOUT => 30,
				CURLOPT_POST => true,
				CURLOPT_POSTFIELDS => array_replace([
					'hash' 				 => $hash,
					'apiSecret' 		 => $merchant_secret,
					'clientIp' 		     => $this->GetIP(),
					'userAgent' 		 => $_SERVER['HTTP_USER_AGENT'],
					'otherCode' 		 => $other_code,
					'redirectUrl' 		 => $this->callback_url,
					'phoneNumber'        => $this->buyer['phone'],
					'cardHolderFullName' => $this->card['fullname'],
					'cardNumber'         => $this->card['number'],
					'expMonth' 			 => $this->card['exp_month'],
					'expYear' 			 => $this->card['exp_year'],
					'cvcNumber' 		 => $this->card['cvc'],
					'amount'      		 => $price,
					'assetMessage' 		 => $this->product['description']
				], $post)
			]);

			$response = @curl_exec($ch);
			if (curl_errno($ch)) {
				$this->error = curl_error($ch);
			} else {
				$result = json_decode($response, true);
				if ($result['status'] == true) {
					return $result['paymentUrl'];
				} else {
					$this->error = $result['message'];
				}
			}
			curl_close($ch);
		}
	}

	public function callback()
	{
		if ($this->config == null) {
			$this->error = "Insufficient Data";
		} else {

			$merchant_id     = $this->config['merchant_id'];
			$merchant_mail   = $this->config['merchant_mail'];
			$merchant_secret = $this->config['merchant_secret'];

			$status         = $this->post('status');
			$result_message = $this->post('resultMessage');
			$other_code     = $this->post('otherCode');
			$verify_hash    = $this->post('VerifyHash');
			$amount         = $this->post('amount');

			if ($status == true) {

				$hash = hash("sha256", $merchant_id . "|" . $merchant_mail . "|" . $merchant_secret . "|" . $other_code . "|true");
				if ($hash == $verify_hash) {
					return [
						'order_id' => explode('PAYIZONE', $other_code)[1],
						'amount'   => $amount,
						'hash'     => $verify_hash
					];
				} else {
					$this->error = "Invalid Verification Code";
				}
			} else {
				$this->error = $result_message;
			}
		}
	}

	public function GetIP()
	{
		if (getenv("HTTP_CLIENT_IP")) {
			$ip = getenv("HTTP_CLIENT_IP");
		} elseif (getenv("HTTP_X_FORWARDED_FOR")) {
			$ip = getenv("HTTP_X_FORWARDED_FOR");
			if (strstr($ip, ',')) {
				$tmp = explode (',', $ip);
				$ip = trim($tmp[0]);
			}
		} else{
			$ip = getenv("REMOTE_ADDR");
		}
		return $ip;
	}

	public function post($par, $empty=true) {
		if ($empty == true) {
			return (isset($_POST[$par]) && !empty($_POST[$par])) ? $_POST[$par] : null;
		} else {
			return (isset($_POST[$par])) ? $_POST[$par] : null;
		}
	}
}