<?php
/**
 * MindMagnet
 *
 * 
 *
 * @category    MindMagnet
 * @package     MindMagnet_BTPay
 * @author      Claudiu Marginean <claudiu.marginean@mindmagnetsoftware.com>
 * @copyright   Copyright (c) 2013 Mind Magnet Software (http://www.mindmagnetsoftware.com)
 * 
 */
class MindMagnet_BTPay_Model_Transaction extends Mage_Core_Model_Abstract
{

    /**
     * Event object prefix
     *
     * @see Mage_Core_Model_Absctract::$_eventPrefix
     * @var string
     */
    protected $_eventPrefix = 'btpay_transaction';

    /**
     * Event object prefix
     *
     * @see Mage_Core_Model_Absctract::$_eventObject
     * @var string
     */
    protected $_eventObject = 'btpay_transaction';

    protected $_transactionCurrency = null;

    /**
     * Initialize resource model
     */
    protected function _construct()
    {
        $this->_init('btpay/transaction');
        return parent::_construct();
    }

    /**
     * Retrieve transaction currency for working with prices
     *
     * @return Mage_Directory_Model_Currency
     */
    public function getTransactionCurrency()
    {
        if (is_null($this->_transactionCurrency)) {
            $this->_transactionCurrency = Mage::getModel('directory/currency')->load($this->getCurrencyCode());
        }
        return $this->_transactionCurrency;
    }
    /**
     * Retrieve transaction currency for working with prices
     *
     * @return Mage_Directory_Model_Currency
     */
    public function getTransactionTypeLable()
    {
        if(!$this->hasData('transaction_type_lable')){
            $transaction_type_lable = '';
            switch ($this->getTransactionType()) {
                case 'preauthorize':
                    $transaction_type_lable = Mage::helper('btpay')->__('Autorizare');
                    break;
                case 'capture':
                    $transaction_type_lable = Mage::helper('btpay')->__('Încasare');
                    break;
                case 'void':
                    $transaction_type_lable = Mage::helper('btpay')->__('Anulare');
                    break;
                
                default:
                    $transaction_type_lable = Mage::helper('btpay')->__('N/A');
                    break;
            }
            $this->setData('transaction_type_lable', $transaction_type_lable);
        }
        return $this->getData('transaction_type_lable');
    }

    /**
     * Load self by specified order Increment ID.
     * @param string $orderIncrementId
     * @return MindMagnet_BTPay_Model_Transaction
     */
    public function loadByOrderId($orderIncrementId)
    {
        $this->getResource()->loadObjectByOrderId(
            $this, $orderIncrementId
        );
        return $this;
    }
    /**
     * Load self by specified order ID.
     * @param string $transaction_type
     * @param MindMagnet_BTPay_Model_Paygate_Response $btpay_response
     * @return MindMagnet_BTPay_Model_Transaction
     */
    public function saveTransaction($btpay_response, $transaction_type, $transaction_status, $order_id , $payment_method)
    {
        $this->setId(null);        
        $this->setOrderId($order_id);
        $this->setOrderIncrementId($btpay_response->getOrder());
        $this->setTransactionType($transaction_type);
        $this->setTransactionStatus($transaction_status);
        $this->setAmountProcessed($btpay_response->getAmount());
        $this->setCurrencyCode($btpay_response->getCurrency());
        $this->setOrder($btpay_response->getOrder());
        $this->setRrn($btpay_response->getRrn());
        $this->setIntRef($btpay_response->getIntRef());
        $this->setResponceMessage($btpay_response->getMessage());
        $this->setPaymentMethod($payment_method);
        $this->setExtraInfo(array('response'=>$btpay_response->getAllData()));
        
        try{
            $this->save();
        } catch (Exception $e) {
            Mage::logException($e);
        } 
        return $this;
    }

    /**
     * Extra information setter
     * Updates data inside the 'extra_info' array
     *
     * @param mixed $key
     * @param mixed $value
     * @return Mage_Paypal_Model_Order_Payment_Transaction
     * @throws Mage_Core_Exception
     */
    public function setExtraInfo($key, $value = null)
    {
        if (is_object($value)) {
            Mage::throwException(Mage::helper('btpay')->__('Payment transactions disallow storing objects.'));
        }
        //Get old data
        $info = $this->_getData('extra_info');
        if (!$info) {
            $info = array();
        }
        
        if(is_array($key)){
            $info = array_merge($info, $key);
        }else{
            $info[$key] = $value;
        }
        return $this->setData('extra_info', $info);
    }

    /**
     * Getter for entire extra_info value or one of its element by key
     * @param string $key
     * @return array|null|mixed
     */
    public function getExtraInfo($key = null)
    {
        $info = $this->_getData('extra_info');
        if (!$info) {
            $info = array();
        }
        if ($key) {
            return (isset($info[$key]) ? $info[$key] : null);
        }
        return $info;
    }

    /**
     * Unsetter for entire extra_info value or one of its element by key
     * @param string $key
     * @return MindMagnet_BTPay_Model_Transaction
     */
    public function unsExtraInfo($key = null)
    {
        if ($key) {
            $info = $this->_getData('extra_info');
            if (is_array($info)) {
                unset($info[$key]);
            }
        } else {
            $info = array();
        }
        return $this->setData('extra_info', $info);
    }

    /**
     * Verify data required for saving
     * @return MindMagnet_BTPay_Model_Transaction
     * @throws Mage_Core_Exception
     */
    protected function _beforeSave()
    {
        if (!$this->getId()) {
            $this->setCreatedAt(Mage::getModel('core/date')->gmtDate());
        }
        return parent::_beforeSave();
    }
}
