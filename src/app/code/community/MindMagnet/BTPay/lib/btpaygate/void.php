<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
include("btpaygate.php");

try {
	$payment = new BTPayment();
	$payment->amount = array_key_exists('amount', $_GET) ? floatval($_GET['amount']) : 10.23;
	$payment->currency = array_key_exists('currency', $_GET) ? $_GET['currency'] : 'RON';
	$payment->order = array_key_exists('order', $_GET) ? $_GET['order'] : '123456';
	
    $config_data = array(
        'test_mode'         => true,
        'debug_mode'        => true,
        'merchant_name'     => 'BTRL Test',
        'merchant_url'      => 'http://www.btrl.ro',
        'merchant_email'    => 'vlad.stanescu@mindmagnetsoftware.com',
        'terminal'          => '60001099',
        'encryption_key'    => '3F6F4235AB32D147BC5F67FE1BAF3E33',
        'license_key'       => null,
    );
    
    $payment_gateway = new BTPayGate($config_data);
    
	$void = $payment_gateway->void($payment,'http://www.mindmagnetsoftware.com/btpaygate/void.php',$_GET['rrn'],$_GET['intref']);
	if ($void->isValid($config_data['encryption_key'])) {
		//psign is valid, verify response
		echo "VALID RESPONSE ... RESULTCODE = ".$void->getCaptureResult()."<br/>".PHP_EOL;
	} else {
		//possible fraud, log details mark as failed
		echo "LOGGING POSSIBLE FRAUD";
	}
} catch (Exception $e) {
	echo "Exception: ".$e->getMessage();
}