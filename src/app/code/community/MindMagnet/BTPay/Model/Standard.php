<?php
/**
 * MindMagnet
 *
 * 
 *
 * @category    MindMagnet
 * @package     MindMagnet_BTPay
 * @author      Claudiu Marginean <claudiu.marginean@mindmagnetsoftware.com>
 * @copyright   Copyright (c) 2012 Mind Magnet Software (http://www.mindmagnetsoftware.com)
 * 
 */

class MindMagnet_BTPay_Model_Standard extends Mage_Payment_Model_Method_Abstract
{
	/**
	 * Payment method identification name
	 * 
	 * @var string
	 */
	protected $_code = 'btpay_standard';
    
    /**
     * API config identification name
     * 
     * @var string
     */
    protected $_api_code = 'btpay_api';
    
    /**
     * API config fields codes
     * 
     * @var string
     */
    protected $_api_fields = array('gateway_url', 'test', 'debug', 'merchant_name', 'merchant_url', 'merchant_email', 'terminal', 'encryption_key', 'license_key');
	
    /**
     * Payment block paths
     *
     * @var string
     */
	protected $_formBlockType = 'btpay/form_standard';
    protected $_infoBlockType = 'btpay/info_standard';

	/**
     * Can use this payment method in administration panel?
     * 
     * @var boolean
     */
    protected $_canUseInternal              = false;
 
    /**
     * Can show this payment method as an option on checkout payment page?
     * 
     * @var boolean
     */
    protected $_canUseCheckout              = true;
    protected $_canUseForMultishipping      = false;
    
    /**
     * Is this payment method a gateway?
     * 
     * @var boolean
     */
    protected $_isGateway                   = true;
	
    /**
     * Payment Method features
     * @var bool
     */
    protected $_canOrder                    = false;
    protected $_canAuthorize                = true;
    protected $_canCapture                  = true;
    protected $_canCapturePartial           = true;
    protected $_canRefund                   = true;
    protected $_canRefundInvoicePartial     = true;
    protected $_canVoid                     = true;
    protected $_isInitializeNeeded          = false;
    protected $_canFetchTransactionInfo     = false;
    protected $_canReviewPayment            = false;
    protected $_canCreateBillingAgreement   = false;
    protected $_canManageRecurringProfiles  = false;
    

    const DEFAULT_TEST_GATEWAY = 'https://www.activare3dsecure.ro/teste3d/cgi-bin/';
    
    const DEFAULT_LIVE_GATEWAY = 'https://www.secure11gw.ro/portal/cgi-bin/';

    
    const TRTTYPE_PREAUTH   = 0;
    const TRTTYPE_CAPTURE   = 21;
    const TRTTYPE_VOID      = 24;
    
    const TRTTYPE_PARTIAL_REFUND = 25;
    const TRTTYPE_VOID_FRAUD     = 26; //anulare pe motiv de frauda
    
    /**
     * Fields that should be replaced in debug with '***'
     *
     * @var array
     */
    protected $_debugReplacePrivateDataKeys = array('P_SIGN', 'encryption_key');
    
    /**
     * Return redirect url
     * 
     * @return string
     */
    public function getOrderPlaceRedirectUrl()
    {
        return Mage::getUrl('btpay/standard/place', array('_secure' => true));
    }
    
    /**
     * Retrieve information from payment configuration
     *
     * @param string $field
     * @param int|string|null|Mage_Core_Model_Store $storeId
     *
     * @return mixed
     */
    public function getConfigData($field, $storeId = null)
    {
        if (null === $storeId) {
            $storeId = $this->getStore();
        }
        $path = 'payment/'.$this->getCode().'/'.$field;
        
        if(in_array($field, $this->_api_fields)){
            $path = 'payment/'.$this->_api_code.'/'.$field;
        }
        
        return Mage::getStoreConfig($path, $storeId);
    }
    
    /**
     * Check whether payment method can be used
     *
     * TODO: payment method instance is not supposed to know about quote
     *
     * @param Mage_Sales_Model_Quote|null $quote
     *
     * @return bool
     */
    public function isAvailable($quote = null)
    {
        $checkResult = new StdClass;
        $checkResult->isAvailable = parent::isAvailable($quote);
        
        if (!Mage::helper('btpay')->isEnabled() && !$this->getConfigData('test')){
            $checkResult->isAvailable = false;
        }
        
        //BOF - IP limitation
        if($ip_limit = $this->getConfigData('ip_limit')){
            $allowedIPs = explode(',', $ip_limit);
            foreach($allowedIPs as $k => $ip){
               $allowedIPs[$k] = trim($ip);
            }
            if (!in_array($_SERVER['REMOTE_ADDR'], $allowedIPs)) {
                //If the request remote IP is not in allow list disable the module
                $checkResult->isAvailable = false;
            }
        }
        //EOF - IP limitation
        
        return $checkResult->isAvailable;
    }

    /**
     * Saves data from FORM Block
     * 
     * @see Mage_Payment_Model_Method_Abstract::assignData()
     * @return PayLane_PayLaneCreditCard_Model_Standard
     */
    public function assignData($data)
    {
        parent::assignData($data);

        $info = $this->getInfoInstance();
        $rambursare = null;
        if (is_array($data) && isset($data['rambursare'])) {
            $rambursare = $data['rambursare'];
        } elseif ($data instanceof Varien_Object && $data->getRambursare()) {
            $rambursare = $data->getRambursare();
        }
        $info->setAdditionalInformation('rambursare', $rambursare);
        
        return $this;
    }
    
    /**
     * Prepare info instance for save
     *
     * @return Mage_Payment_Model_Abstract
     */
    public function prepareSave()
    {
        $info = $this->getInfoInstance();
        
        //$info->setCcNumberEnc($info->encrypt($info->getCcNumber()));
        //$info->setCcCidEnc($info->encrypt($info->getCcCid()));
        
        $info->setCcNumber(null)->setCcCid(null);
        
        return $this;
    }
    
    /**
     * Get instructions text from config
     *
     * @return string
     */
    public function getInstructions()
    {
        return trim($this->getConfigData('instructions'));
    }
    
    /**
     * Validate user data from input form on the checkout page
     * 
     * @see Mage_Payment_Model_Method_Abstract::validate()
     * @return PayLane_PayLaneCreditCard_Model_Standard
     */
    public function validate()
    {
        parent::validate();

        if(!$this->validateConfiguration()){
            Mage::throwException(Mage::helper('btpay')->__('Invalid Payment Module Configuration!'));
        }
        
        if (!Mage::helper('btpay')->isEnabled() && !$this->getConfigData('test')){
            Mage::throwException('Invalid/incomplete '.base64_decode('bGljZW5zZV9rZXk='));
        }
        
        if($this->getConfigData('test') && $this->getCode() == 'btpay_star'){
            //Star BT does not work in test mode.
            Mage::throwException(Mage::helper('btpay')->__('This Payment Module does not work in test mode!'));
        }
        
        $info = $this->getInfoInstance();
        
        $rambursare = $info->getAdditionalInformation('rambursare');

        if ((strlen($rambursare) > 1) && !is_numeric($rambursare))
        {
            $error = Mage::helper('btpay')->__('Please select how many installments you wish for this payment');
            
            Mage::throwException($error);
        }


        return $this;
    }

    /**
     * Check partial refund availability for invoice
     *
     * @return bool
     */
    public function canRefundPartialPerInvoice()
    {
        
        if($this->getConfigData('test')){
            //return false;
            //make test version send TRTTYPE_VOID for partial refunds
        }
        
        return $this->_canRefundInvoicePartial;
    }

    /**
     * Send authorize request to gateway
     *
     * @param  Varien_Object $payment
     * @param  decimal $amount
     * @return Mage_Paygate_Model_Authorizenet
     * @throws Mage_Core_Exception
     */
    public function authorize(Varien_Object $payment, $amount)
    {
        $payment->setIsTransactionPending(true);
        
        //this is important because we call $payment->authorize() from controller class and only if Transaction Id is present, 
        // the authorization has been made and we need to let $payment create transactions for it
        if($payment->getTransactionId()){
            $payment->setIsTransactionPending(false);
        }
        //Round amount to 2 decimals, the same decimals number like renderAuthForm()
        $payment->setAdditionalInformation('pending_authorization_amount', sprintf("%.2f", $amount));
    }
    
    /**
     * Capture payment abstract method
     *
     * @param Varien_Object $payment
     * @param float $amount
     *
     * @return Mage_Payment_Model_Abstract
     */
    public function capture(Varien_Object $payment, $amount)
    {
        if (!$this->canCapture()) {
            Mage::throwException(Mage::helper('payment')->__('Capture action is not available.'));
        }
        
        //$btpaygate_lib_path = Mage::getModuleDir('', 'MindMagnet_BTPay').DS.'lib'.DS.'btpaygate'.DS.'btpaygate.php';
        //require_once ($btpaygate_lib_path);
        
        /* @since version 1.8.0 this fucntion is @deprecated */
        //return to allow magento Invoice and Refund to work
        return;
        
        //Format $btpay_request request object
        $btpay_request = Mage::getSingleton('btpay/paygate_request');
        $btpay_request->setAmount($amount);
        $btpay_request->setCurrency($payment->getOrder()->getBaseCurrencyCode());
        $btpay_request->setOrder($payment->getOrder()->getIncrementId());
        
        //Call to gateway capute action
        $btpay_response = $this->__capture(
            $btpay_request,
            Mage::getUrl('btpay/standard/return', array('_secure' => true)),
            $payment->getAdditionalInformation('rrn'),
            $payment->getAdditionalInformation('int_ref')
        );
            
        if ($btpay_response->isValid() && $btpay_response->isCaptured()) {
            
            //Save transaction into BT Pay Table 
            Mage::getModel('btpay/transaction')->saveTransaction($btpay_response, 'capture', true, $payment->getOrder()->getId(), $this->getCode());

            $debug_msg =    "\n"."CAPTURE VALID RESPONSE ... \n".
                            " ORDER = ".$btpay_response->getOrder()."\n".
                            " AMOUNT = ".$btpay_response->getAmount()."\n".
                            " RRN: ".$btpay_response->getRrn()."\n".
                            " IntRef: ".$btpay_response->getIntRef()."\n".
                            " ACTION: ".$btpay_response->getAction()."\n".
                            " RC: ".$btpay_response->getRc()."\n".
                            " MESSAGE: ".$btpay_response->getMessage()."\n";
            
            $this->debugInfo($debug_msg, Zend_Log::DEBUG);
            
            $payment->setIsTransactionPending(false);
            $payment->setIsTransactionClosed(false);
            
            //DO NOT SET THIS - this is automaticalli generated based on authorize tranzaction
            //$payment->setTransactionId($btpay_response->getIntRef());
            
            $payment->setTransactionAdditionalInfo('capture_response_code', $btpay_response->getAction());
            $payment->getOrder()->addStatusHistoryComment(
                Mage::helper('btpay')->__('Capture response from gateway is valid.')."<br />".
                Mage::helper('btpay')->__('Capture Amount: %s', $payment->getOrder()->getBaseCurrency()->formatTxt($btpay_response->getAmount()) )."<br />".
                Mage::helper('btpay')->__('IntRef: %s RRN: %s<br />ACTION_CODE: %s RC_CODE: %s MESSAGE: %s ', 
                    $btpay_response->getIntRef(), 
                    $btpay_response->getRrn(), 
                    $btpay_response->getAction(), 
                    $btpay_response->getRc(), 
                    $btpay_response->getMessage()
                )
            );
            
            return $this;
                    
        } else {
            
            //Save transaction into BT Pay Table 
            Mage::getModel('btpay/transaction')->saveTransaction($btpay_response, 'capture', false, $payment->getOrder()->getId(), $this->getCode());

            $debug_msg =    "\n"."CAPTURE INVALID RESPONSE ... \n".
                            " ORDER = ".$btpay_response->getOrder()."\n".
                            " AMOUNT = ".$btpay_response->getAmount()."\n".
                            " RRN: ".$btpay_response->getRrn()."\n".
                            " IntRef: ".$btpay_response->getIntRef()."\n".
                            " ACTION: ".$btpay_response->getAction()."\n".
                            " RC: ".$btpay_response->getRc()."\n".
                            " MESSAGE: ".$btpay_response->getMessage()."\n";
            
            $this->debugInfo($debug_msg, Zend_Log::CRIT);
            
            $this->debugInfo(
                Mage::helper('btpay')->__('Invalid response returned from payment gateway!')."\n".
                Mage::helper('btpay')->__('Capture Amount: %s', $payment->getOrder()->getBaseCurrency()->formatTxt($btpay_response->getAmount()) )."\n".
                Mage::helper('btpay')->__('IntRef: %s RRN: %s'."\n".'ACTION_CODE: %s RC_CODE: %s MESSAGE: %s ', 
                    $btpay_response->getIntRef(), 
                    $btpay_response->getRrn(), 
                    $btpay_response->getAction(), 
                    $btpay_response->getRc(), 
                    $btpay_response->getMessage()
                ), 
                Zend_Log::ERR
            );
            
            Mage::throwException(
                Mage::helper('btpay')->__('Invalid response returned from payment gateway!')."<br />".
                Mage::helper('btpay')->__('Capture Amount: %s', $payment->getOrder()->getBaseCurrency()->formatTxt($btpay_response->getAmount()) )."<br />".
                Mage::helper('btpay')->__('IntRef: %s RRN: %s<br />ACTION_CODE: %s RC_CODE: %s MESSAGE: %s <br />', 
                    $btpay_response->getIntRef(), 
                    $btpay_response->getRrn(), 
                    $btpay_response->getAction(), 
                    $btpay_response->getRc(), 
                    $btpay_response->getMessage()
                )
            );
        }
        
            
        return $this;
    }

    /**
     * Refund specified amount for payment
     *
     * @param Varien_Object $payment
     * @param float $amount
     *
     * @return Mage_Payment_Model_Abstract
     */
    public function refund(Varien_Object $payment, $amount)
    {

        if (!$this->canRefund()) {
            Mage::throwException(Mage::helper('payment')->__('Refund action is not available.'));
        }
        
        $this->_void($payment, $amount);

        return $this;
    }
    
    /**
     * Cancel payment abstract method
     *
     * @param Varien_Object $payment
     *
     * @return Mage_Payment_Model_Abstract
     */
    public function cancel(Varien_Object $payment)
    {
        $this->_void($payment);
        
        return $this;
    }

    /**
     * Void payment abstract method
     *
     * @param Varien_Object $payment
     *
     * @return Mage_Payment_Model_Abstract
     */
    public function void(Varien_Object $payment)
    {
        if (!$this->canVoid($payment)) {
            Mage::throwException(Mage::helper('payment')->__('Void action is not available.'));
        }
        
        $this->_void($payment);
        
        return $this;
    }

    /**
     * Void payment abstract method
     *
     * @param Varien_Object $payment
     * @param float $amount
     *
     * @return Mage_Payment_Model_Abstract
     */
    public function _void(Varien_Object $payment, $amount = null)
    {
        
        /* @since version 1.8.0 this fucntion is @deprecated */
        //return to allow magento Invoice and Refund to work
        return;
        
        if(is_null($amount) || empty($amount)){
            
            $amount = $payment->getBaseAmountAuthorized();
            
            if(empty($amount)){
                $amount = $payment->getBaseAmountOrdered();
            }
            
        }

        if(empty($amount)){
            Mage::throwException(Mage::helper('btpay')->__('Refund amount can not be empty!'));
        }
        
        
        //$btpaygate_lib_path = Mage::getModuleDir('', 'MindMagnet_BTPay').DS.'lib'.DS.'btpaygate'.DS.'btpaygate.php';
        //require_once ($btpaygate_lib_path);
        
        //Format $btpay_request request object
        $btpay_request = Mage::getSingleton('btpay/paygate_request');
        $btpay_request->setAmount($amount);
        $btpay_request->setCurrency($payment->getOrder()->getBaseCurrencyCode());
        $btpay_request->setOrder($payment->getOrder()->getIncrementId());
        
        
        $trt_type = self::TRTTYPE_VOID;
        
        if($amount != $payment->getBaseAmountOrdered() && $this->getConfigData('test') == false){
            //partial refund
            //use this TRTTYPE only on live server
            $trt_type = self::TRTTYPE_PARTIAL_REFUND;
        }
         
        //Call to gateway capute action
        $btpay_response = $this->__void(
            $btpay_request,
            Mage::getUrl('btpay/standard/return', array('_secure' => true)),
            $payment->getAdditionalInformation('rrn'),
            $payment->getAdditionalInformation('int_ref'),
            $trt_type
        );
        
        if ($btpay_response->isValid() && $btpay_response->isVoided()) {
            
            //Save transaction into BT Pay Table 
            Mage::getModel('btpay/transaction')->saveTransaction($btpay_response, 'void', true, $payment->getOrder()->getId(), $this->getCode());
            
            $debug_msg =    "\n"."VOID VALID RESPONSE ... \n".
                            " ORDER = ".$btpay_response->getOrder()."\n".
                            " AMOUNT = ".$btpay_response->getAmount()."\n".
                            " RRN: ".$btpay_response->getRrn()."\n".
                            " IntRef: ".$btpay_response->getIntRef()."\n".
                            " ACTION: ".$btpay_response->getAction()."\n".
                            " RC: ".$btpay_response->getRc()."\n".
                            " MESSAGE: ".$btpay_response->getMessage()."\n";
            
            $this->debugInfo($debug_msg, Zend_Log::DEBUG);
            
            
            if($amount == $payment->getBaseAmountOrdered()){
                $payment->setIsTransactionClosed(true);
            }else{
                $payment->setIsTransactionClosed(true);
            }
            
            //DO NOT SET THIS - this is automaticalli generated based on authorize tranzaction
            //$payment->setTransactionId($btpay_response->getIntRef());
            
            $payment->setTransactionAdditionalInfo('void_response_code', $btpay_response->getAction());
            $payment->getOrder()->addStatusHistoryComment(
                Mage::helper('btpay')->__('Refund response from gateway is valid.')."<br />".
                Mage::helper('btpay')->__('Refund Amount: %s', $payment->getOrder()->getBaseCurrency()->formatTxt($btpay_response->getAmount()) )."<br />".
                Mage::helper('btpay')->__('IntRef: %s RRN: %s<br />ACTION_CODE: %s RC_CODE: %s MESSAGE: %s ', 
                    $btpay_response->getIntRef(), 
                    $btpay_response->getRrn(), 
                    $btpay_response->getAction(), 
                    $btpay_response->getRc(), 
                    $btpay_response->getMessage()
                )
            );
            
            return $this;
                    
        } else {
            
            //Save transaction into BT Pay Table 
            Mage::getModel('btpay/transaction')->saveTransaction($btpay_response, 'void', false, $payment->getOrder()->getId(), $this->getCode());
            
            $debug_msg =    "\n"."VOID INVALID RESPONSE ... \n".
                            " ORDER = ".$btpay_response->getOrder()."\n".
                            " AMOUNT = ".$btpay_response->getAmount()."\n".
                            " RRN: ".$btpay_response->getRrn()."\n".
                            " IntRef: ".$btpay_response->getIntRef()."\n".
                            " ACTION: ".$btpay_response->getAction()."\n".
                            " RC: ".$btpay_response->getRc()."\n".
                            " MESSAGE: ".$btpay_response->getMessage()."\n";
            
            $this->debugInfo($debug_msg, Zend_Log::CRIT);
                
            
            $this->debugInfo(
                Mage::helper('btpay')->__('Invalid response returned from payment gateway!')."\n".
                Mage::helper('btpay')->__('Refund Amount: %s', $payment->getOrder()->getBaseCurrency()->formatTxt($btpay_response->getAmount()) )."\n".
                Mage::helper('btpay')->__('IntRef: %s RRN: %s'."\n".'ACTION_CODE: %s RC_CODE: %s MESSAGE: %s ', 
                    $btpay_response->getIntRef(), 
                    $btpay_response->getRrn(), 
                    $btpay_response->getAction(), 
                    $btpay_response->getRc(), 
                    $btpay_response->getMessage()
                ), 
                Zend_Log::ERR
            );
            
            Mage::throwException(
                Mage::helper('btpay')->__('Invalid response returned from payment gateway!')."<br />".
                Mage::helper('btpay')->__('Refund Amount: %s', $payment->getOrder()->getBaseCurrency()->formatTxt($btpay_response->getAmount()) )."<br />".
                Mage::helper('btpay')->__('IntRef: %s RRN: %s<br />ACTION_CODE: %s RC_CODE: %s MESSAGE: %s <br />', 
                    $btpay_response->getIntRef(), 
                    $btpay_response->getRrn(), 
                    $btpay_response->getAction(), 
                    $btpay_response->getRc(), 
                    $btpay_response->getMessage()
                )
            );
        }
        
            
        return $this;
    }

    /**
     * >>>>> Helpers functions >>>>
     */
    

    /**
     * Debug info using Payment Abstract debug fucntion
     *
     * @param mixed $debugData
     * @param integer $level
     * @param integer $trace_level Default = 1
     */
    public function debugInfo($debugData = null, $level = null, $trace_level = 1)
    {
        if(is_null($debugData) || is_string($debugData)){
            $message = $debugData;
            
            if(is_null($message)){
                $message = 'DEBUG_TRACE_CALL';
            }
            $debugData = array(
                'message' => $message
            );
            
        }elseif(!is_array($debugData)){
            $debugData = array(
                'extra' => $message
            );
        }

        //Add trace call
        $trace = debug_backtrace();
        if(!empty($trace[$trace_level])) $debugData['trace_call'] = $trace[$trace_level];
        
        if(!empty($trace[$trace_level-1]['file'])) $debugData['trace_call']['file'] = $trace[$trace_level-1]['file'];
        if(!empty($trace[$trace_level-1]['line'])) $debugData['trace_call']['line'] = $trace[$trace_level-1]['line'];
        
        unset($debugData['trace_call']['object']);
        unset($debugData['trace_call']['args']);
        
        //if(in_array($level, array( Zend_Log::CRIT, Zend_Log::EMERG ))){
        //    $debugData['trace_full'] = $trace;
        //}
        
        //Force debug info on error
        if(in_array($level, array( Zend_Log::CRIT, Zend_Log::EMERG, Zend_Log::ERR ))){
            $old_debug_flag = $this->getDebugFlag();
            $this->setDebugFlag(true);
            
            $this->debugData($debugData);
            
            $this->setDebugFlag($old_debug_flag);
            return true;
        }
        
        $this->debugData($debugData);
    }

    /**
     * Get form parameters based on payment action
     * 
     * @param MindMagnet_BTPay_Model_Paygate_Request $payment
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
    public function getActionParams(MindMagnet_BTPay_Model_Paygate_Request $request, $action = 'preauthorize', $backref, $rrn = null, $intref = null, $_is_partial = false) {
        if (!$request->isValid()) throw new Exception('Invalid/incomplete payment object', 1);
        if (!$this->validateConfiguration()) throw new Exception('Invalid/incomplete payment configuration', 1);
        if (!Mage::helper('btpay')->isEnabled() && !$this->getConfigData('test')) throw new Exception('Invalid/incomplete '.base64_decode('bGljZW5zZV9rZXk='), 1);
        
        $allow_actions = array('preauthorize', 'capture', 'void');
        if(!in_array($action, $allow_actions)){
             throw new Exception('Invalid action!', 3);
        }
        
        $params = array();
        
        if($action == 'preauthorize'){
            
            $params = array(
                'AMOUNT'         => sprintf("%.2f",$request->getAmount()),
                'CURRENCY'       => $request->getCurrency(),
                'ORDER'          => $request->getOrder(),
                'DESC'           => $request->getDesc(),
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
            
            $paymentInfo = $this->getInfoInstance();
            if($paymentInfo && (int)$paymentInfo->getAdditionalInformation('rambursare') > 0){
                $params['RAMBURSARE'] = (int)$paymentInfo->getAdditionalInformation('rambursare');
            }
            
        }elseif($action == 'capture'){
            $params = array(
                'ORDER'         => $request->getOrder(),
                'AMOUNT'        => sprintf("%.2f",$request->getAmount()),
                'CURRENCY'      => $request->getCurrency(),
                'RRN'           => $rrn,
                'INT_REF'       => $intref,
                'TRTYPE'        => self::TRTTYPE_CAPTURE,
                'TERMINAL'      => $this->getConfigData('terminal'),
                'TIMESTAMP'     => gmdate('YmdHis'),
                'NONCE'         => self::generateNonce(),
                'BACKREF'       => $backref
            );
            
            if($_is_partial){
                //add extra info on partial transactions here
            }
            
        }elseif($action == 'void'){
            $params = array(
                'ORDER'         => $request->getOrder(),
                'AMOUNT'        => sprintf("%.2f",$request->getAmount()),
                'CURRENCY'      => $request->getCurrency(),
                'RRN'           => $rrn,
                'INT_REF'       => $intref,
                'TRTYPE'        => self::TRTTYPE_VOID,
                'TERMINAL'      => $this->getConfigData('terminal'),
                'TIMESTAMP'     => gmdate('YmdHis'),
                'NONCE'         => self::generateNonce(),
                'BACKREF'       => $backref
            );
            
            if($_is_partial && !$this->getConfigData('test')){
                //partial refund TRTTYPE is only available on live
                $params['TRTYPE'] = self::TRTTYPE_PARTIAL_REFUND;
            }
        }
        
        //calculate PSign and add in to params
        $params['P_SIGN'] = self::calculatePSign($params);
        
        return $params;
    }

    /**
     * Render HTML form for all API calls
     * 
     * @param array $params
     * @return string $html
     */
    public function renderForm($params) {
        if(!is_array($params)){
            return '';
        }
        
        $html = '<!DOCTYPE html>
        <html>
        <head>
            <meta content="text/html; charset=utf-8" http-equiv="Content-Type">
            <title>BT Pay - Tranzactie in curs</title>
        </head>
        <body>';
        
        $html .= '<div style="margin: 0 auto; width: 960px; min-height: 450px; text-align: center;">';
        
        $html .= '<p>Tranzactie in curs ...</p>';
        //Debug only
        //$html .= "<pre>$params = ".print_r($params,1)."</pre>";
        
        $html .= '<div id="payment_form_block" style="display:block !important; border:0 !important; margin:0 !important; padding:0 !important; font-size:0 !important; line-height:0 !important; width:0 !important; height:0 !important; overflow:hidden !important;">';
        
        $html .= '<form action="'.$this->getGatewayUrl().'" method="post">';
        foreach ($params as $_name => $_value) {
            $html .= '<input type="hidden" name="'.$_name.'" value="'.stripslashes($_value).'" />';
        }
        $html .= '<input type="submit" value="Executa plata" />';
        $html .= '</form>';
        
        $html .= '</div>';
        
        $html .= '<script type="text/javascript"> if(document.forms[0]) { document.forms[0].submit(); } else { document.getElementById("payment_form_block").removeAttribute("style"); } </script>';
        
        $html .= '</div>';
        $html .= '</body></html>';
        
        return $html;
    }

    /**
     * Render HTML form for authorization
     * 
     * @param MindMagnet_BTPay_Model_Paygate_Request $request
     * @param URL $backref
     * @throws Exception 1,Invalid/incomplete payment object
     * @return string $html
     */
    public function renderAuthForm(MindMagnet_BTPay_Model_Paygate_Request $request, $backref) {
        if (!$request->isValid()) throw new Exception('Invalid/incomplete payment object', 1);
        if (!$this->validateConfiguration()) throw new Exception('Invalid/incomplete payment configuration', 1);
        if (!Mage::helper('btpay')->isEnabled() && !$this->getConfigData('test')) throw new Exception('Invalid/incomplete '.base64_decode('bGljZW5zZV9rZXk='), 1);
        
        $form = array(
            'AMOUNT'        => sprintf("%.2f",$request->getAmount()),
            'CURRENCY'      => $request->getCurrency(),
            'ORDER'         => $request->getOrder(),
            'DESC'          => $request->getDesc(),
            'MERCH_NAME'    => $this->getConfigData('merchant_name'),
            'MERCH_URL'     => $this->getConfigData('merchant_url'),
            'MERCHANT'      => '0000000'.$this->getConfigData('terminal'),
            'TERMINAL'      => $this->getConfigData('terminal'),
            'EMAIL'         => $this->getConfigData('merchant_email'),
            'TRTYPE'        => self::TRTTYPE_PREAUTH,
            'COUNTRY'       => NULL,
            'MERCH_GMT'     => NULL,
            'TIMESTAMP'     => gmdate('YmdHis'),
            'NONCE'         => self::generateNonce(),
            'BACKREF'       => $backref
        );
        
        $paymentInfo = $this->getInfoInstance();
        if($paymentInfo && (int)$paymentInfo->getAdditionalInformation('rambursare') > 0){
            $form['RAMBURSARE'] = (int)$paymentInfo->getAdditionalInformation('rambursare');
        }
        
        $form['P_SIGN'] = $this->calculatePSign($form);

        $this->debugInfo(array('request' => $form), Zend_Log::DEBUG);
        
        $res = '<form action="'.$this->getGatewayUrl().'" method="post">';
        foreach ($form as $_name => $_value) {
            $res .= '<input type="hidden" name="'.$_name.'" value="'.stripslashes($_value).'" />';
        }
        $res .= '<input type="submit" value="Executa plata" />';
        $res .= '</form>';
        return $res;
    }
    
    /**
     * 
     * Executes capture through cURL call
     * @param MindMagnet_BTPay_Model_Paygate_Request $request
     * @param URL $backref
     * @param string $rrn
     * @param string $intref
     * @throws Exception 1,cURL connection error
     * @return MindMagnet_BTPay_Model_Paygate_Response
     */
    public function __capture(MindMagnet_BTPay_Model_Paygate_Request $request, $backref, $rrn, $intref) {
        $form = array(
            'ORDER'         => $request->getOrder(),
            'AMOUNT'        => sprintf("%.2f",$request->getAmount()),
            'CURRENCY'      => $request->getCurrency(),
            'RRN'           => $rrn,
            'INT_REF'       => $intref,
            'TRTYPE'        => self::TRTTYPE_CAPTURE,
            'TERMINAL'      => $this->getConfigData('terminal'),
            'TIMESTAMP'     => gmdate('YmdHis'),
            'NONCE'         => self::generateNonce(),
            'BACKREF'       => $backref
        );
        $form['P_SIGN'] = $this->calculatePSign($form);
        
        $this->debugInfo(array('request' => $form), Zend_Log::DEBUG);
        
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

            $this->debugInfo(array('response_raw' => $formresult), Zend_Log::DEBUG);
            
            $result = self::parseResponseHtml($formresult);
            
            $this->debugInfo(array('response' => $result), Zend_Log::DEBUG);
            
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

                    $this->debugInfo(array('response_raw' => $formresult), Zend_Log::DEBUG);
                    
                    $result = self::parseResponseHtml($formresult);
                    
                    $this->debugInfo(array('response' => $result), Zend_Log::DEBUG);
                    
                    if(empty($result['from_action'])){
                        throw new Exception('Invalid HTML response from Gateway!', 1);
                    }
                    
                } else {
                    throw new Exception('cURL connection error', 1);
                }
            }
            
            $btpay_response = Mage::getSingleton('btpay/paygate_response', $result['input_values']);
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
     * @param MindMagnet_BTPay_Model_Paygate_Request $request
     * @param URL $backref
     * @param string $rrn
     * @param string $intref
     * @param string $trtype
     * @throws Exception 1,cURL connection error
     * @return MindMagnet_BTPay_Model_Paygate_Response
     */
    public function __void(MindMagnet_BTPay_Model_Paygate_Request $request, $backref, $rrn, $intref, $trtype) {
        $form = array(
            'ORDER'         => $request->getOrder(),
            'AMOUNT'        => sprintf("%.2f",$request->getAmount()),
            'CURRENCY'      => $request->getCurrency(),
            'RRN'           => $rrn,
            'INT_REF'       => $intref,
            'TRTYPE'        => $trtype,
            'TERMINAL'      => $this->getConfigData('terminal'),
            'TIMESTAMP'     => gmdate('YmdHis'),
            'NONCE'         => self::generateNonce(),
            'BACKREF'       => $backref
        );
        $form['P_SIGN'] = $this->calculatePSign($form);
        
        $this->debugInfo(array('request' => $form), Zend_Log::DEBUG);
        
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

            $this->debugInfo(array('response_raw' => $formresult), Zend_Log::DEBUG);
            
            $result = self::parseResponseHtml($formresult);
            
            $this->debugInfo(array('response' => $result), Zend_Log::DEBUG);
            
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

                    $this->debugInfo(array('response_raw' => $formresult), Zend_Log::DEBUG);
                    
                    $result = self::parseResponseHtml($formresult);
                    
                    $this->debugInfo(array('response' => $result), Zend_Log::DEBUG);
                    
                    if(empty($result['from_action'])){
                        throw new Exception('Invalid HTML response from Gateway!', 1);
                    }
                    
                } else {
                    throw new Exception('cURL connection error', 1);
                }
            }
            
            $btpay_response = Mage::getSingleton('btpay/paygate_response', $result['input_values']);
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
        if (!$this->getConfigData('merchant_name')) return false;
        if (!$this->getConfigData('merchant_url')) return false;
        if (!$this->getConfigData('terminal')) return false;
        if (!$this->getConfigData('merchant_email')) return false;
        if (!$this->getConfigData('encryption_key')) return false;
        return true;
    }
    
    /**
     * Get Payment Gateway
     * Validates configuration
     * @return string
     */
    public function getGatewayUrl() 
    {
        if(!$this->getConfigData('gateway_url')){
            return ($this->getConfigData('test') ? self::DEFAULT_TEST_GATEWAY : self::DEFAULT_LIVE_GATEWAY);
        }else{
            return $this->getConfigData('gateway_url');
        }
        
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
     * Statis list of valid currencies
     */
    public static function validCurrencies() {
        return array('RON','USD','EUR');
    }
 

    /**
     * Check method for processing with base currency
     *
     * @param string $currencyCode
     * @return boolean
     */
    public function canUseForCurrency($currencyCode)
    {
        return in_array($currencyCode, self::validCurrencies());
    }
    
    /**
     * Exemple function that saves an invoice
     * 
     * @deprecated NOT USED
     */
    public function setCurrentOrderPaid()
    {
        // get order id
        $order_id = Mage::getSingleton('checkout/session')->getLastRealOrderId();
        if (is_null($order_id))
        {
            Mage::app()->getFrontController()->getResponse()->setRedirect(Mage::getUrl("/"));
        }
                
        // get order
        $order = Mage::getModel('sales/order');
        $order = Mage::getModel('sales/order')->loadByIncrementId($order_id);
        if (is_null($order))
        {
            Mage::app()->getFrontController()->getResponse()->setRedirect(Mage::getUrl("/"));
        }
                
        // change order status to complete
        //$order->setStatus('complete');
                
        try 
        {
            if (!$order->canInvoice())
            {
                Mage::throwException(Mage::helper('core')->__('Cannot create an invoice.'));
            } 
                
            // create payment invoice for this order
            $invoice = Mage::getModel('sales/service_order', $order)->prepareInvoice(); 
            
            if (!$invoice->getTotalQty()) 
            {
                Mage::throwException(Mage::helper('core')->__('Cannot create an invoice without products.'));
            } 
                    
            $invoice->setRequestedCaptureCase(Mage_Sales_Model_Order_Invoice::CAPTURE_ONLINE);
            $invoice->register();
            
            //BOF - MindMagnet - Set Processing Status on success payment transfers
            //Trigger ERP is Valid flag for Order
            //Update Total amount Payed on Order
            $invoice->pay();
            $invoice->getOrder()->setState(Mage_Sales_Model_Order::STATE_PROCESSING, true);
            //EOF - MindMagnet - Set Processing Status on success payment transfers

            $transactionSave = Mage::getModel('core/resource_transaction')->addObject($invoice)->addObject($invoice->getOrder());
            $transactionSave->save();
        }
        catch (Mage_Core_Exception $e) 
        { 
        }
                
        // send notification email and save changes
        $order->sendNewOrderEmail();
        $order->save();
        
        $payment = $order->getPayment();
        if (is_null($payment) || !($payment instanceof Varien_Object))
        {
            Mage::app()->getFrontController()->getResponse()->setRedirect(Mage::getUrl("/"));
            return;
        }
        
        $payment->setAdditionalInformation('card_number', "");
        $payment->setAdditionalInformation('security_code', "");
        $payment->setAdditionalInformation('expiration_month', "");
        $payment->setAdditionalInformation('expiration_year', "");
        $payment->setAdditionalInformation('name_on_card', "");
        
        $payment->save();
        $order->save();
    }

    /**
     * Register order cancellation. Return money to customer if needed.
     *
     * @param Mage_Sales_Model_Order $order
     * @param string $message
     * @param bool $voidPayment
     */
    protected function _declineOrder(Mage_Sales_Model_Order $order, $message = '', $voidPayment = true)
    {
        try {
            if ($voidPayment) {
                $order->getPayment()
                    ->setTransactionId(null)
                    ->void();
            }
            $order->registerCancellation($message)
                ->save();
        } catch (Exception $e) {
            //quiet decline
            Mage::logException($e);
        }
    }
}
