<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
include("btpaygate.php");

//AUTH VALIDATION
$gr = new BTGatewayResponse($_GET);
if ($gr->isValid('3F6F4235AB32D147BC5F67FE1BAF3E33') && $gr->isAuthorized()) {
	//psign is valid, verify response
	echo "VALID RESPONSE ... AMOUNT = ".$gr->getAmount()."<br/>".PHP_EOL;
	echo "RRN: ".$gr->getRrn()."<br />";
	echo "IntRef: ".$gr->getIntRef()."<br />";
	echo '<a href="index.php?rrn='.$gr->getRrn().'&intref='.$gr->getIntRef().'">Go To Index</a>';
} else {
	//possible fraud, log details mark as failed
	echo "LOGGING POSSIBLE FRAUD";
}
