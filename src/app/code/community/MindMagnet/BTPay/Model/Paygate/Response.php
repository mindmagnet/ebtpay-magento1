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

class MindMagnet_BTPay_Model_Paygate_Response extends Varien_Object 
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

    
    protected $_response = null;
    
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
    public function isValid() {
        $correctpsign = '';
        foreach ($this->orderedResponse() as $_key => $_value) {
            if (is_null($_value)) {
                $correctpsign .= '-';
            } else {
                $correctpsign .= strlen($_value).$_value;
            }
        }
        return (strtoupper(hash_hmac('sha1',$correctpsign,pack('H*', Mage::getSingleton('btpay/standard')->getConfigData('encryption_key')))) == $this->_response['P_SIGN']);
    }
    
    /**
     * 
     * Helper function that returns an ordered response array for correct PSign calculation
     */
    private function orderedResponse() {
        switch ($this->_response['TRTYPE']) {
            case MindMagnet_BTPay_Model_Standard::TRTTYPE_PREAUTH:
                $fields = array('TERMINAL','TRTYPE','ORDER','AMOUNT','CURRENCY','DESC','ACTION','RC','MESSAGE','RRN','INT_REF','APPROVAL','TIMESTAMP','NONCE');
            break;
            case MindMagnet_BTPay_Model_Standard::TRTTYPE_CAPTURE:
                $fields = array('ACTION','RC','MESSAGE','TRTYPE','AMOUNT','CURRENCY','ORDER','RRN','INT_REF','TIMESTAMP','NONCE');
            break;
            case MindMagnet_BTPay_Model_Standard::TRTTYPE_VOID:
                $fields = array('ACTION','RC','MESSAGE','TRTYPE','AMOUNT','CURRENCY','ORDER','RRN','INT_REF','TIMESTAMP','NONCE');
            break;
        }
        $result = array();
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
        return isset($this->_response['AMOUNT']) ? floatval($this->_response['AMOUNT']) : null;
    }
    public function getCurrency()
    {
        return isset($this->_response['CURRENCY']) ? $this->_response['CURRENCY'] : null;
    }
    public function getOrder()
    {
        return isset($this->_response['ORDER']) ? $this->_response['ORDER'] : null;
    }
    public function getRrn()
    {
        return isset($this->_response['RRN']) ? $this->_response['RRN'] : null;
    }
    public function getIntRef()
    {
        return isset($this->_response['INT_REF']) ? $this->_response['INT_REF'] : null;
    }
    /**
     * Action Posible values
     * 
     * 0 - tranzactie aprobata
     * 1 - tranzactie duplicata
     * 2 - tranzatie respinsa
     * 3 - eraore de procesare
     * 
     * @return string
     */
    public function getAction()
    {
        return isset($this->_response['ACTION']) ? $this->_response['ACTION'] : null;
    }
    /**
     * Valoare generata de banca emitenta conform standardului ISO8583.
     * Puteti descarca o lista cu coduri posibile aici:
     * @link https://www.activare3dsecure.ro/teste3d/error.txt
     * @see app/code/community/MindMagnet/BTPay/doc/RomCard - RC error list.txt
     * 
     * Length = 2
     * 
     * @return string
     */
    public function getRc()
    {
        return isset($this->_response['RC']) ? $this->_response['RC'] : null;
    }
    /**
     * Descrierea campului RC.
     * 
     * Length = 1-50
     * 
     * @return string
     */
    public function getMessage()
    {
        return isset($this->_response['MESSAGE']) ? $this->_response['MESSAGE'] : null;
    }
    /**
     * Codul de autorizare al tranzactiei (generat de banca emitenta).
     * 
     * Type = Numeric
     * Length = 6
     * 
     * @return string
     */
    public function getApproval()
    {
        return isset($this->_response['APPROVAL']) ? $this->_response['APPROVAL'] : null;
    }
    
    /**
     * Get All date stored
     * 
     * @return array
     */
    public function getAllData()
    {
        return $this->_response;
    }
}
