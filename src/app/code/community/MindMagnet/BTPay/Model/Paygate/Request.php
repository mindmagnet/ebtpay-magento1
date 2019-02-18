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

class MindMagnet_BTPay_Model_Paygate_Request extends Varien_Object 
{
    public $_amount = null;
    public $_currency = null;
    public $_order = null;
    public $_desc = null;
    
    public function isValid() {
        if (is_null($this->_amount)) return false;
        if (is_null($this->_currency)) return false;
        if (is_null($this->_order)) return false;
        return true;
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
        if (!in_array($value, MindMagnet_BTPay_Model_Standard::validCurrencies())) throw new Exception('Invalid currency', 2); 
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
}
