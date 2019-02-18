<?php
/**
 * BT Pay Payment gateway class
 * 
 * @author      Mind Magnet Software <office@mindmagnetsoftware.com>
 * @copyright   Copyright (c) 2013 Mind Magnet Software (http://www.mindmagnetsoftware.com)
 * @version 1.4
 *
 */
class BTPayGate
{
    
    /**
     * configurations
     * 
     * @deprecated since version 1.0
     * 
     * config values has been moved outside of the php class and they can be set with ->setConfigData()
     */
	/*
	const MERCH_NAME = 'BTRL Test'; //String	1-50	Numele comerciantului (numele firmei care apare pe site si este declarat la banca).
	const MERCH_URL = 'http://www.btrl.ro'; //String	1-50	Adresa site-ului.
	const MERCHANT ='000000060001099';	//Numeric	15	Valoare asignata de catre banca. Se compune din: 0000000+TERMINAL.
	const TERMINAL = '60001099'; //String	8	Valoare asignata de catre banca. Se gaseste in sectiunea "Configurare cont".
	const EMAIL	= 'vlad.stanescu@mindmagnetsoftware.com'; //String	1-50	Email de contact comerciant.
	const ENCRYPTION_KEY = '3F6F4235AB32D147BC5F67FE1BAF3E33';
	*/
	
	/**
     * Configurations private array
     * 
     * @since version 1.3
     * 
     * Here you can hard code the default config values
     * or use $payment_gateway->setConfigData($_configDataArray) 
     * or use $payment_gateway = new BTPayGate($_configDataArray)
     */
	private $_configData = array(
        'test_mode'         => true,
        'debug_mode'        => true,
        'gateway_url'       => null,
        'merchant_name'     => null,
        'merchant_url'      => null,
        'merchant_email'    => null,
        'terminal'          => null,
        'encryption_key'    => null,
        'license_key'       => null, //optional param used in modules that required a license_key to work
    );
    
	const TRTTYPE_PREAUTH = 0;
	const TRTTYPE_CAPTURE = 21;
	const TRTTYPE_VOID = 24;
	
    const TRTTYPE_PARTIAL_REFUND = 25;
    const TRTTYPE_VOID_FRAUD     = 26; //anulare pe motiv de frauda

    const DEFAULT_TEST_GATEWAY = 'https://www.activare3dsecure.ro/teste3d/cgi-bin/';
    
    const DEFAULT_LIVE_GATEWAY = 'https://www.secure11gw.ro/portal/cgi-bin/';

    /**
     * Constructor with initializer payment module with the configuration data
     * 
     * Setting configuration using __construct is optional
     * 
     * @since version 1.3
     * @param array $config_data
     */
    public function __construct($config_data = null) {
        if (!is_null($config_data)) {
            $this->setConfigData($config_data);
        }
        
        return $this;
    }

    /**
     * Retrieve information from payment configuration
     * 
     * @since version 1.3
     * @param string $field
     * @return mixed
     */
    public function getConfigData($field = null)
    {
        if(is_null($field)){
            return $this->_configData;
        }
        
        return (isset($this->_configData[$field]) ? $this->_configData[$field] : null);
    }
    
    /**
     * Set information from payment configuration
     * 
     * @since version 1.3
     * @param string|array $field
     * @param int|string|null $value
     * @return bool
     */
    public function setConfigData($field, $value = null)
    {
        if(is_array($field)){
            $fields = $field;
            foreach($fields as $field => $value){
                $this->_configData[$field] = $value;
            }
        }else{
            $this->_configData[$field] = $value;
        }
        
        //automatically set gateway_url based on test_mode
        if(isset($this->_configData['test_mode'])){
            $this->_configData['gateway_url'] = ($this->_configData['test_mode'] ? self::DEFAULT_TEST_GATEWAY : self::DEFAULT_LIVE_GATEWAY );
        }
        
        return true;
    }
    
    /**
     * Get Gateway URL besed on test mode config
     */
    public function getGatewayUrl()
    {
        return ($this->getConfigData('test_mode') ? self::DEFAULT_TEST_GATEWAY : self::DEFAULT_LIVE_GATEWAY );
    }
    
	/**
	 * 
	 * Render HTML form for authorization
	 * @param BTPayment $payment
	 * @param URL $backref
	 * @throws Exception
     * Exception 1, Invalid/incomplete payment object
     * Exception 2, Invalid/incomplete API configuration
	 * @return string $html
	 */
	public function renderAuthForm(BTPayment $payment, $backref) {
		if (!$payment->isValid()) throw new Exception('Invalid/incomplete payment object', 1);
		if (!$this->validateConfiguration()) throw new Exception('Invalid/incomplete API configuration', 2);
		
		$form = array(
			'AMOUNT'         => sprintf("%.2f",$payment->amount),
			'CURRENCY'       => $payment->currency,
			'ORDER'          => $payment->order,
			'DESC'           => $payment->desc,
            'MERCH_NAME'     => $this->getConfigData('merchant_name'),
            'MERCH_URL'      => $this->getConfigData('merchant_url'),
            'MERCHANT'       => '0000000'.$this->getConfigData('terminal'),
            'TERMINAL'       => $this->getConfigData('terminal'),
            'EMAIL'          => $this->getConfigData('merchant_email'),
            'TRTYPE'         => self::TRTTYPE_PREAUTH,
			'COUNTRY'        => NULL,
			'MERCH_GMT'      => NULL,
			'TIMESTAMP'      => gmdate('YmdHis'),
			'NONCE'          => self::generateNonce(),
			'BACKREF'        => $backref
		);
        
        if($payment->getRambursare() >= 2){
            $form['RAMBURSARE'] = $payment->getRambursare();
        }
        
		$form['P_SIGN'] = self::calculatePSign($form);
		
		$res = '<form action="'.$this->getGatewayUrl().'" method="post">';
		foreach ($form as $_name => $_value) {
			$res .= '<input type="hidden" name="'.$_name.'" value="'.stripslashes($_value).'" />';
		}
		$res .= '<input type="submit" value="Executa plata" />';
		$res .= '</form>';
		return $res;
	}

    /**
     * Get form parameters based on payment action
     * 
     * @param BTPayment $payment
     * @param string $action - Action name
     * @param string $backref - Back URL
     * @param string $rrn
     * @param string $intref
     * @param bool $_is_partial
     * @throws Exception
     *  Exception 1, Invalid/incomplete payment object
     *  Exception 2, Invalid/incomplete API configuration
     *  Exception 3, Invalid action!
     * @return array $params - form parameters used to generate the hidden fields
     */
    public function getActionParams(BTPayment $payment, $action = 'preauthorize', $backref, $rrn = null, $intref = null, $_is_partial = false) {
        if (!$payment->isValid()) throw new Exception('Invalid/incomplete payment object', 1);
        if (!$this->validateConfiguration()) throw new Exception('Invalid/incomplete API configuration', 1);
        
        $allow_actions = array('preauthorize', 'capture', 'void');
        if(!in_array($action, $allow_actions)){
             throw new Exception('Invalid action!', 3);
        }
        
        $params = array();
        
        if($action == 'preauthorize'){
            
            $params = array(
                'AMOUNT'         => sprintf("%.2f",$payment->amount),
                'CURRENCY'       => $payment->currency,
                'ORDER'          => $payment->order,
                'DESC'           => $payment->desc,
                'MERCH_NAME'     => $this->getConfigData('merchant_name'),
                'MERCH_URL'      => $this->getConfigData('merchant_url'),
                'MERCHANT'       => '0000000'.$this->getConfigData('terminal'),
                'TERMINAL'       => $this->getConfigData('terminal'),
                'EMAIL'          => $this->getConfigData('merchant_email'),
                'TRTYPE'         => self::TRTTYPE_PREAUTH,
                'COUNTRY'        => NULL,
                'MERCH_GMT'      => NULL,
                'TIMESTAMP'      => gmdate('YmdHis'),
                'NONCE'          => self::generateNonce(),
                'BACKREF'        => $backref
            );
            
            if($payment->getRambursare() >= 2){
                $params['RAMBURSARE'] = $payment->getRambursare();
            }
            
        }elseif($action == 'capture'){
            $params = array(
                'ORDER' => $payment->order,
                'AMOUNT' => sprintf("%.2f",$payment->amount),
                'CURRENCY' => $payment->currency,
                'RRN' => $rrn,
                'INT_REF' => $intref,
                'TRTYPE' => self::TRTTYPE_CAPTURE,
                'TERMINAL' => $this->getConfigData('terminal'),
                'TIMESTAMP' => gmdate('YmdHis'),
                'NONCE' => self::generateNonce(),
                'BACKREF' => $backref
            );
            
            if($_is_partial){
                //add extra info on partial transactions here
            }
            
        }elseif($action == 'void'){
            $params = array(
                'ORDER' => $payment->order,
                'AMOUNT' => sprintf("%.2f",$payment->amount),
                'CURRENCY' => $payment->currency,
                'RRN' => $rrn,
                'INT_REF' => $intref,
                'TRTYPE' => self::TRTTYPE_VOID,
                'TERMINAL' => $this->getConfigData('terminal'),
                'TIMESTAMP' => gmdate('YmdHis'),
                'NONCE' => self::generateNonce(),
                'BACKREF' => $backref
            );
            
            if($_is_partial && !$this->getConfigData('test_mode')){
                //partial refund TRTTYPE is only available on live
                $params['TRTYPE'] = self::TRTTYPE_PARTIAL_REFUND;
            }
        }
        
        //calculate PSign and add in to params
        $params['P_SIGN'] = self::calculatePSign($params);
        
        return $params;
    }
	
	/**
	 * 
	 * Executes capture through cURL call
	 * @param BTPayment $payment
	 * @param URL $backref
	 * @param string $rrn
	 * @param string $intref
	 * @param bool $_is_partial
	 * @throws Exception
     *  Exception 1, cURL connection error 
     *  Exception 2, Invalid HTML response from Gateway!
	 * @return BTGatewayResponse $result
	 */
	public function capture(BTPayment $payment, $backref, $rrn, $intref, $_is_partial = false) {
		$form = array(
			'ORDER' => $payment->order,
			'AMOUNT' => sprintf("%.2f",$payment->amount),
			'CURRENCY' => $payment->currency,
			'RRN' => $rrn,
			'INT_REF' => $intref,
			'TRTYPE' => self::TRTTYPE_CAPTURE,
			'TERMINAL' => $this->getConfigData('terminal'),
			'TIMESTAMP' => gmdate('YmdHis'),
			'NONCE' => self::generateNonce(),
			'BACKREF' => $backref
		);
        
        if($_is_partial){
            //add extra info on partial transactions here
        }
        
		$form['P_SIGN'] = self::calculatePSign($form);
		
		$ch = curl_init();
		curl_setopt($ch,CURLOPT_URL, $this->getGatewayUrl());
		curl_setopt($ch,CURLOPT_PORT,443);
		curl_setopt($ch,CURLOPT_SSL_VERIFYPEER,false);
		curl_setopt($ch,CURLOPT_SSL_VERIFYHOST,false);
        curl_setopt($ch,CURLOPT_HEADER,false); 
		curl_setopt($ch,CURLOPT_POST, true);
		curl_setopt($ch,CURLOPT_POSTFIELDS, http_build_query($form));
		curl_setopt($ch,CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch,CURLOPT_USERAGENT,'Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.8.1.13) Gecko/20080311 Firefox/2.0.0.13');
		curl_setopt($ch,CURLOPT_CONNECTTIMEOUT,30);
		$formresult = curl_exec($ch);
		$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE); 
		curl_close($ch);

        if ($httpCode=='200') {

            //$this->debugInfo(array('response_raw' => $formresult));
            
            $result = self::parseResponseHtml($formresult);
            
            //$this->debugInfo(array('response' => $result));
            
            if(empty($result['from_action'])){
                throw new Exception('Invalid HTML response from Gateway!', 2);
            }
            
            if($result['from_action'] != $backref){
                //we have to send again a new request to the gateway
                $ch = curl_init();
                curl_setopt($ch,CURLOPT_URL, $this->getGatewayUrl());
                curl_setopt($ch,CURLOPT_PORT,443);
                curl_setopt($ch,CURLOPT_SSL_VERIFYPEER,false);
                curl_setopt($ch,CURLOPT_SSL_VERIFYHOST,false);
                curl_setopt($ch,CURLOPT_HEADER,false); 
                curl_setopt($ch,CURLOPT_POST, true);
                curl_setopt($ch,CURLOPT_POSTFIELDS, http_build_query($result['input_values']));
                curl_setopt($ch,CURLOPT_RETURNTRANSFER, 1);
                curl_setopt($ch,CURLOPT_USERAGENT,'Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.8.1.13) Gecko/20080311 Firefox/2.0.0.13');
                curl_setopt($ch,CURLOPT_CONNECTTIMEOUT,30);
                $formresult = curl_exec($ch);
                $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE); 
                curl_close($ch);
                
                if ($httpCode=='200') {

                    //$this->debugInfo(array('response_raw' => $formresult));
                    
                    $result = self::parseResponseHtml($formresult);
                    
                    //$this->debugInfo(array('response' => $result));
                    
                    if(empty($result['from_action'])){
                        throw new Exception('Invalid HTML response from Gateway!', 2);
                    }
                    
                } else {
                    throw new Exception('cURL connection error', 1);
                }
            }
            
            $btpay_response = new BTGatewayResponse($result['input_values']);
            return $btpay_response;
        } else {
            throw new Exception('cURL connection error', 1);
        }
	}
	
    /**
     * Helper method that parses HTML from gateway to identify return URL parameters and parse them into an array
     * 
     *  array(
     *      'input_values' = array('name' => 'value'),
     *      'from_action'  = 'https://...',
     *      'from_method'  = 'POST'  
     *  )
     *
     * 
     * @param string $html
     * @return array
     */
    public static function parseResponseHtml($html) {
        $result = array('input_values' => array(), 'from_action' => false, 'from_method' => 'POST');
        
        if(empty($html)) return $result;
        
        try{

            $expected_fields = array('ACTION','RC','MESSAGE','TRTYPE','AMOUNT','CURRENCY','ORDER','RRN','INT_REF','TIMESTAMP','NONCE','P_SIGN');

            preg_match('%<form.*?method.*?=.*?"(.*?)".*?action.*?=.*?"(.*?)".*?>(.*?)</form>%s', $html, $html_data);
            
            //var_dump($html_data);
            if(count($html_data) != 4){
                throw new Exception('Invalid HTML response from Gateway!', 1);
            }
            
            $result['from_method'] = $html_data[1];
            $result['from_action'] = $html_data[2];

            preg_match_all('/<input.*?name.*?=.*?"(.*?)".*?value.*?=.*?"(.*?)".*?>/', $html_data[3], $html_input_data);
            
            //var_dump($html_input_data);
            if(count($html_input_data) != 3 || count($html_input_data[0]) <= 0 ){
                throw new Exception('Invalid HTML response from Gateway!', 1);
            }
            
            $input_data = array();
            foreach($html_input_data[0] as $key => $val){
                $_input_name  = $html_input_data[1][$key];
                $_input_value = $html_input_data[2][$key];
                
                $input_data[$_input_name] = $_input_value;
            }
            
            $result['input_values'] = $input_data;
            
            //var_dump($result);
           
        } catch (Exception $e) {
            $result['from_action'] = false;
        }     
        return $result;
    }
	
	/**
	 * 
	 * Executes void through cURL call
	 * @param BTPayment $payment
	 * @param URL $backref
	 * @param string $rrn
	 * @param string $intref
	 * @param bool $_is_partial
     * @throws Exception
     *  Exception 1, cURL connection error 
     *  Exception 2, Invalid HTML response from Gateway!
	 * @return BTGatewayResponse $result
	 */
	public function void(BTPayment $payment, $backref, $rrn, $intref, $_is_partial = false) {
		$form = array(
			'ORDER' => $payment->order,
			'AMOUNT' => sprintf("%.2f",$payment->amount),
			'CURRENCY' => $payment->currency,
			'RRN' => $rrn,
			'INT_REF' => $intref,
			'TRTYPE' => self::TRTTYPE_VOID,
			'TERMINAL' => $this->getConfigData('terminal'),
			'TIMESTAMP' => gmdate('YmdHis'),
			'NONCE' => self::generateNonce(),
			'BACKREF' => $backref
		);
        
        if($_is_partial && !$this->getConfigData('test_mode')){
            //partial refund TRTTYPE is only available on live
            $form['TRTYPE'] = self::TRTTYPE_PARTIAL_REFUND;
        }
        
		$form['P_SIGN'] = $this->calculatePSign($form);
		
		$ch = curl_init();
		curl_setopt($ch,CURLOPT_URL, $this->getGatewayUrl());
		curl_setopt($ch,CURLOPT_PORT,443);
		curl_setopt($ch,CURLOPT_SSL_VERIFYPEER,false);
		curl_setopt($ch,CURLOPT_SSL_VERIFYHOST,false);
        curl_setopt($ch,CURLOPT_HEADER,false); 
		curl_setopt($ch,CURLOPT_POST, true);
		curl_setopt($ch,CURLOPT_POSTFIELDS, http_build_query($form));
		curl_setopt($ch,CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch,CURLOPT_USERAGENT,'Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.8.1.13) Gecko/20080311 Firefox/2.0.0.13');
		curl_setopt($ch,CURLOPT_CONNECTTIMEOUT,30);
		$formresult = curl_exec($ch);
		$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE); 
		curl_close($ch);
        
        if ($httpCode=='200') {

            //$this->debugInfo(array('response_raw' => $formresult));
            
            $result = self::parseResponseHtml($formresult);
            
            //$this->debugInfo(array('response' => $result));
            
            if(empty($result['from_action'])){
                throw new Exception('Invalid HTML response from Gateway!', 1);
            }
            
            if($result['from_action'] != $backref){
                //we have to send again a new request to the gateway
                $ch = curl_init();
                curl_setopt($ch,CURLOPT_URL, $this->getGatewayUrl());
                curl_setopt($ch,CURLOPT_PORT,443);
                curl_setopt($ch,CURLOPT_SSL_VERIFYPEER,false);
                curl_setopt($ch,CURLOPT_SSL_VERIFYHOST,false);
                curl_setopt($ch,CURLOPT_HEADER,false); 
                curl_setopt($ch,CURLOPT_POST, true);
                curl_setopt($ch,CURLOPT_POSTFIELDS, http_build_query($result['input_values']));
                curl_setopt($ch,CURLOPT_RETURNTRANSFER, 1);
                curl_setopt($ch,CURLOPT_USERAGENT,'Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.8.1.13) Gecko/20080311 Firefox/2.0.0.13');
                curl_setopt($ch,CURLOPT_CONNECTTIMEOUT,30);
                $formresult = curl_exec($ch);
                $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE); 
                curl_close($ch);
                
                if ($httpCode=='200') {

                    //$this->debugInfo(array('response_raw' => $formresult));
                    
                    $result = self::parseResponseHtml($formresult);
                    
                    //$this->debugInfo(array('response' => $result));
                    
                    if(empty($result['from_action'])){
                        throw new Exception('Invalid HTML response from Gateway!', 2);
                    }
                    
                } else {
                    throw new Exception('cURL connection error', 1);
                }
            }
            
            $btpay_response = new BTGatewayResponse($result['input_values']);
            return $btpay_response;
        } else {
            throw new Exception('cURL connection error', 1);
        }
	}
	
	/*
	 * ------ HELPERS
	 */
	/**
	 * 
	 * Calculate PSign for a set of fields
	 * @param array $params
	 * @return string $psign
	 */
	public function calculatePSign(array $params) {
		$res = '';
		foreach ($params as $_key=>$_value) {
			if (is_null($_value)) {
				$res .= '-';
			} else {
				$res .= strlen($_value).$_value;
			}
		}
        $encryption_key = $this->getConfigData('encryption_key');
        return strtoupper(hash_hmac('sha1',$res,pack('H*', $encryption_key)));
	}
	
	/**
	 * 
	 * Validates configuration
	 * @return bool $valid
	 */
	public function validateConfiguration() {
	    if (!$this->getConfigData('test_mode') && !$this->isDevMode() && !$this->check()) return false;
        if (!$this->getConfigData('merchant_name')) return false;
        if (!$this->getConfigData('merchant_url')) return false;
        if (!$this->getConfigData('terminal')) return false;
        if (!$this->getConfigData('merchant_email')) return false;
        if (!$this->getConfigData('encryption_key')) return false;
        return true;
	}

	/**
	 * 
	 * Generate Nonce
	 * @return string $nonce
	 */
	public static function generateNonce() {
		$return = '';
	    for ($i=0;$i<32;$i++) {
	    	switch (mt_rand(0,2)) {
	    		case 0: $return .= chr(mt_rand(65, 90)); break;
	    		case 1: $return .= chr(mt_rand(97, 122)); break;
	    		case 2: $return .= chr(mt_rand(48, 57)); break;
	    	}
	    }
		
		return $return;
	}
	
	/**
	 * 
	 * Static list of valid currencies
	 */
	public static function validCurrencies() {
		return array('RON','USD','EUR');
	}
    
    public function check($s = null, $l = null)
    {
        $s = trim(preg_replace('/\s{2,}/siu', '', $this->getConfigData(base64_decode('bGljZW5zZV9rZXk='))));
        $s_list = str_split($s, 40);
        $serverName = trim($_SERVER['SERVER_NAME']);
        if(is_array($s_list) && !empty($s_list) && !empty($serverName) ){
            foreach($s_list as $s){
                if($this->_checkItem($this->getDomain($serverName),$s)){
                    return true;
                }
                if($this->_checkItem($serverName,$s)){
                    return true;
                }
            }
        }

        return false;
    }
    
    private function _checkItem($d, $s)
    {
        $key = 'f9051185c4786c05691ea34542d7be37c06520ed';
        if(strlen($key) && strlen($d) && strlen($s) && sha1($key.$d) == $s) {
            return true;
        }

        return false;
    }

    
    public function isDevMode()
    {
        //If Server Name is an IP then this extension is in DevMode
        if($this->validateIpAddr($_SERVER['SERVER_NAME'])){
            return true;
        }
        
        //If Server Name is 'localhost' then this extension is in DevMode
        if(trim($_SERVER['SERVER_NAME']) == 'localhost'){
            return true;
        }
        
        //If Server Name is has '.local' then this extension is in DevMode
        if(strpos($_SERVER['SERVER_NAME'], '.local') !== false){
            return true;
        }
        
        return false;
    }
    
    /**
     * PHP version of validateIpAddr()
     * 
     */
    public function validateIpAddr($ip)
    {
        return ip2long($ip) !== false;
    }
    
    public function getDomain($url)
    {
        $pieces = parse_url($url);
        $domain = isset($pieces['host']) ? $pieces['host'] : $url;
        if (preg_match('/(?P<domain>[a-z0-9][a-z0-9\-]{1,63}\.[a-z\.]{2,6})$/i', $domain, $regs)) {
            return $regs['domain'];
        }
        return false;
    }
}

/**
 * 
 * Response validator
 * @author MindMagnet
 *
 */
class BTGatewayResponse
{
    /**
     * Action Response Posible values:
     * 
     * 0 - tranzactie aprobata
     * 1 - tranzactie duplicata
     * 2 - tranzatie respinsa
     * 3 - eraore de procesare
     */
    const ACTION_APPROVED   = '0';
    const ACTION_DUPLICATE  = '1';
    const ACTION_REJECTED   = '2';
    const ACTION_ERROR      = '3';
	
    /**
     * @var array response GET parameters received from BT Pay Gateway
     */
	protected $_response = array();
	
	/**
	 * 
	 * Constructor with initializer of response (generically browser $_POST)
	 * @param array $response
	 * @throws Exception 1,Missing response array
	 */
	public function __construct($response=null) {
		if (is_null($response)) {
			throw new Exception('Missing response array',1);
		} else {
			$this->_response = $response;
		}
		return $this;
	}
	
	/**
	 * 
	 * Validates PSign provided with correct PSign calculation
	 * @return bool $valid
	 */
	public function isValid($encryption_key) {
	    if(!isset($this->_response['P_SIGN'])) return false;
        
		$correctpsign = '';
		foreach ($this->orderedResponse() as $_key => $_value) {
			if (is_null($_value)) {
				$correctpsign .= '-';
			} else {
				$correctpsign .= strlen($_value).$_value;
			}
		}
		return (strtoupper(hash_hmac('sha1',$correctpsign,pack('H*', $encryption_key))) == $this->_response['P_SIGN']);
	}
	
	/**
	 * 
	 * Helper function that returns an ordered response array for correct PSign calculation
	 */
	private function orderedResponse() {
	    $result = array();
        
        //return empty result if TRTYPE is not set
        if(!isset($this->_response['TRTYPE'])) return $result;
        
		switch ($this->_response['TRTYPE']) {
			case BTPayGate::TRTTYPE_PREAUTH:
				$fields = array('TERMINAL','TRTYPE','ORDER','AMOUNT','CURRENCY','DESC','ACTION','RC','MESSAGE','RRN','INT_REF','APPROVAL','TIMESTAMP','NONCE');
			break;
			case BTPayGate::TRTTYPE_CAPTURE:
				$fields = array('ACTION','RC','MESSAGE','TRTYPE','AMOUNT','CURRENCY','ORDER','RRN','INT_REF','TIMESTAMP','NONCE');
			break;
			case BTPayGate::TRTTYPE_VOID:
				$fields = array('ACTION','RC','MESSAGE','TRTYPE','AMOUNT','CURRENCY','ORDER','RRN','INT_REF','TIMESTAMP','NONCE');
			break;
		}
		
		foreach ($fields as $_field) {
		    if(!isset($this->_response[$_field])) continue;
            
			$result[$_field] = $this->_response[$_field];
		}
		return $result;
	}
	
    
    /**
     * 
     * Returns capture result flag
     * @throws Exception 1,Response is not a capture
     * @return string $result 
     */
    public function getCaptureResult() {
        if (!array_key_exists('ACTION', $this->_response)) throw new Exception('Response is not a capture',1);
        return $this->_response['ACTION'];
    }
    
    /**
     * 
     * Returns capture result
     * 
     * Daca in mesajul primit de la RomCard campul ACTION=0, mesajul a fost corect si efectuat cu succes. 
     * In cazul in care ACTION are alta valoare, va rugam sa verificati la RomCard starea tranzactiei
     * 
     * @throws Exception 1,Response is not a capture
     */
    public function isCaptured() {
        if (!array_key_exists('ACTION', $this->_response)) throw new Exception('Response is not a capture',1);
        return $this->_response['ACTION']==self::ACTION_APPROVED;
    }
    
    /**
     * 
     * Returns void result flag
     * @throws Exception 1,Response is not a void
     * @return string $result 
     */
    public function getVoidResult() {
        if (!array_key_exists('ACTION', $this->_response)) throw new Exception('Response is not a void',1);
        return $this->_response['ACTION'];
    }
    
    /**
     * 
     * Returns void result
     * 
     * Daca in mesajul primit de la RomCard campul ACTION=0, mesajul a fost corect si efectuat cu succes. 
     * In cazul in care ACTION are alta valoare, va rugam sa verificati la RomCard starea tranzactiei
     * 
     * @throws Exception 1,Response is not a void
     */
    public function isVoided() {
        if (!array_key_exists('ACTION', $this->_response)) throw new Exception('Response is not a void',1);
        return $this->_response['ACTION']==self::ACTION_APPROVED;
    }
    
    /**
     * 
     * Returns void result
     * 
     * In cazul in care o tranzactie a fost autorizata (ACTION=0 si exista cod de autorizare in campul "APROVAL"), 
     * comerciantul va trimite produsul/serviciul catre client.
     * 
     * @throws Exception 1,Response is not valid authorize
     */
    public function isAuthorized() {
        if (!array_key_exists('ACTION', $this->_response) || !array_key_exists('APPROVAL', $this->_response)) throw new Exception('Response is not valid authorize',1);
        return $this->_response['ACTION']==self::ACTION_APPROVED && !empty($this->_response['APPROVAL']);
    }
	
	/*
	 * ---------- GETTERS/SETTERS
	 */
	
	public function getAmount()
	{
		return floatval($this->_response['AMOUNT']);
	}
	public function getCurrency()
	{
		return $this->_response['CURRENCY'];
	}
	public function getOrder()
	{
		return $this->_response['ORDER'];
	}
	public function getRrn()
	{
		return $this->_response['RRN'];
	}
	public function getIntRef()
	{
		return $this->_response['INT_REF'];
	}
    /**
     * general getter
     * 
     * @param string $field
     * 
     * @return mixed
     */
    public function getResponse($field = null)
    {
        if(is_null($field)){
            return $this->_response;
        }
        
        return (isset($this->_response[$field]) ? $this->_response[$field] : null);
    }
}


/**
 * 
 * Payment object
 * @author MindMagnet
 *
 */
class BTPayment
{
	public $_amount = null;
	public $_currency = null;
	public $_order = null;
	public $_desc = null;
	public $_rambursare = null;
	
	public function isValid() {
		if (is_null($this->amount)) return false;
		if (is_null($this->currency)) return false;
		if (is_null($this->order)) return false;
		return true;
	}
	
	public function __set($name, $value) {
        $method = 'set' . str_replace('_','',$name);
        if (('mapper' == $name) || !method_exists($this, $method)) {
            throw new Exception('Invalid property ('.$name.','.$value.')');
        }
        $this->$method($value);
    }

    public function __get($name) {
        $method = 'get' . str_replace('_','',$name);
        if (('mapper' == $name) || !method_exists($this, $method)) {
            throw new Exception('Invalid property ('.$name.')');
        }
        return $this->$method();
    }
    
	public function setAmount($value) {
        if ($value<=0) throw new Exception('Invalid amount', 1); 
        $this->_amount = floatval($value);
        return $this;
    }
	public function getAmount() {
        return $this->_amount;
    }

	public function setCurrency($value) {
        if (!in_array($value, BTPayGate::validCurrencies())) throw new Exception('Invalid currency', 2); 
        $this->_currency = $value;
        return $this;
    }
	public function getCurrency() {
        return $this->_currency;
    }

	public function setOrder($value) {
        if ((strlen($value)<6)||(strlen($value)>19)||(!is_numeric($value)))  throw new Exception('Order must be numeric with length of 6-19', 3);
        $this->_order = $value;
        return $this;
    }
	public function getOrder() {
        return $this->_order;
    }

	public function setDesc($value) {
        $this->_desc = substr($value,0,50);
        return $this;
    }
	public function getDesc() {
        return $this->_desc;
    }
	public function setRambursare($value) {
        $this->_rambursare = (int)$value;
        return $this;
    }
	public function getRambursare() {
        return $this->_rambursare;
    }
}