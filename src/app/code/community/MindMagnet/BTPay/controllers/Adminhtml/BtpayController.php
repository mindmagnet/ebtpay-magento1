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
class MindMagnet_BTPay_Adminhtml_BtpayController extends Mage_Adminhtml_Controller_Action
{

    /**
     * Initialize order
     * 
     * @param string $order_increment_id
     * @return Mage_Sales_Model_Order
     */
    protected function _initOrder($order_increment_id)
    {
        $order = Mage::getModel("sales/order")->loadByIncrementId($order_increment_id);
        if(!$order || !$order->getId()){
            Mage::throwException(Mage::helper('btpay')->__('Order #%s was not found!',$order_increment_id));
        }
        return $order;
    }
    
    /**
     * Initialize payment module
     * 
     * @param Mage_Sales_Model_Order $order
     * @return MindMagnet_BTPay_Model_Standard
     */
    protected function _initPaymentModule($order = null)
    {
        if(!$order || !$order->getId()){
            return Mage::getSingleton('btpay/standard');
        }
        
        $btpay_module = $order->getPayment()->getMethodInstance();
        
        //Set payment method store id
        $btpay_module->setStore($order->getStoreId());
        
        return $btpay_module;
    }

    /**
     * Do not use this as action
     */
    public function indexAction()
    {
        $this->_redirectReferer();
    }

    /**
     * Call BT Pay API (capture, void) actions
     */
    public function call_apiAction()
    {
        $btpay_data = $this->getRequest()->getPost('btpay');
        $btpay_module = Mage::getSingleton('btpay/standard');
        
        //$btpaygate_lib_path = Mage::getModuleDir('', 'MindMagnet_BTPay').DS.'lib'.DS.'btpaygate'.DS.'btpaygate.php';
        //require_once ($btpaygate_lib_path);
        
        try {
            if($btpay_data && isset($btpay_data['order_id'])){

                $order = $this->_initOrder($btpay_data['order_id']);
                
                $btpay_module = $this->_initPaymentModule($order);
                

                if(empty($btpay_data['order_id'])){
                    $btpay_data['order_id'] = $order->getIncrementId();
                }
                if(empty($btpay_data['amount'])){
                    $btpay_data['amount'] = $order->getBaseTotalDue();
                }
                if(empty($btpay_data['currency'])){
                    $btpay_data['currency'] = $order->getBaseCurrencyCode();
                }
                if(empty($btpay_data['order'])){
                    $btpay_data['order'] = $order->getIncrementId();
                }
                if(empty($btpay_data['action'])){
                    Mage::throwException(Mage::helper('btpay')->__('Datele trimise sunt invalide!'));
                }
                if(empty($btpay_data['rrn'])){
                    Mage::throwException(Mage::helper('btpay')->__('RRN nu poate fi gol!'));
                }
                if(empty($btpay_data['int_ref'])){
                    Mage::throwException(Mage::helper('btpay')->__('Int Ref nu poate fi gol!'));
                }
                
                $btpay_request = Mage::getSingleton('btpay/paygate_request');
                $btpay_request->setAmount($btpay_data['amount']);
                $btpay_request->setCurrency($btpay_data['currency']);
                $btpay_request->setOrder($btpay_data['order']);
                
                $_is_partial = false;
                if($btpay_data['amount'] != $order->getPayment()->getBaseAmountOrdered()){
                    //partial refund
                    $_is_partial = true;
                }
                
                $back_url = $this->getUrl('adminhtml/btpay/'.$btpay_data['action'].'_callback', array('order_id' => $order->getIncrementId()));
                
                $form_params = $btpay_module->getActionParams($btpay_request, $btpay_data['action'], $back_url, $btpay_data['rrn'], $btpay_data['int_ref'], $_is_partial);

                $btpay_module->debugInfo(array('request' => $form_params), Zend_Log::DEBUG);
                
                $form_html = $btpay_module->renderForm($form_params);

                $this->getResponse()->setBody($form_html);
                return;
            }else{
                Mage::throwException(Mage::helper('btpay')->__('Datele trimise sunt invalide!'));
            }
            //Mage::throwException(Mage::helper('btpay')->__('A apărut o eroare în procesarea cererii dumneavoastră.'));
        } catch (Mage_Core_Exception $e) {
            $btpay_module->debugInfo('ERROR: '.$e->getMessage(), Zend_Log::ERR);
            
            $this->_getSession()->addError($e->getMessage());
        } catch (Exception $e) {
            $btpay_module->debugInfo('ERROR: '.$e->getMessage(), Zend_Log::ERR);
            
            $this->_getSession()->addError($e->getMessage());
            Mage::logException($e);
        }
        $this->_redirectReferer();
    }

    /**
     * Capture callback action
     */
    public function capture_callbackAction()
    {
        $order = null;
        $order_id = $this->getRequest()->getParam('order_id');
        $btpay_module = Mage::getSingleton('btpay/standard');
        //$btpaygate_lib_path = Mage::getModuleDir('', 'MindMagnet_BTPay').DS.'lib'.DS.'btpaygate'.DS.'btpaygate.php';
        //require_once ($btpaygate_lib_path);
        
        $_response_data = $this->getRequest()->getQuery();
        
        $btpay_module->debugInfo(array('response' => $_response_data), Zend_Log::DEBUG);
        
        try {
            if($_response_data && $order_id){

                $order = $this->_initOrder($order_id);
                
                $btpay_module = $this->_initPaymentModule($order);
                
                $btpay_response = Mage::getSingleton('btpay/paygate_response', $_response_data);

                $transaction_status = false;
                //CAPTURE
                if ($btpay_response->isValid() && $btpay_response->isCaptured()) {
                    $transaction_status = true;
                }
                //Save transaction into BT Pay Table 
                Mage::getModel('btpay/transaction')->saveTransaction($btpay_response, 'capture', $transaction_status, $order->getId(), $btpay_module->getCode());
                
                if($transaction_status){
                    $this->_getSession()->addSuccess(Mage::helper('btpay')->__('Cererea dumneavoastră a fost efectuată cu succes.'));
                }else{
                    $this->_getSession()->addError(Mage::helper('btpay')->__('A apărut o eroare în procesarea cererii dumneavoastră.'));
                }
            }else{
                Mage::throwException(Mage::helper('btpay')->__('A apărut o eroare în procesarea cererii dumneavoastră.'));
            }
        } catch (Mage_Core_Exception $e) {
            $btpay_module->debugInfo('ERROR: '.$e->getMessage(), Zend_Log::ERR);
            
            $this->_getSession()->addError($e->getMessage());
        } catch (Exception $e) {
            $btpay_module->debugInfo('ERROR: '.$e->getMessage(), Zend_Log::ERR);
            
            $this->_getSession()->addError($e->getMessage());
            Mage::logException($e);
        }
        
        if(!empty($order)){
            $this->_redirect('*/sales_order/view/', array('order_id'=>$order->getId()));
        }else{
            $this->_redirect('*/sales_order/index/');
        }
    }

    /**
     * Void callback action
     */
    public function void_callbackAction()
    {
        $order = null;
        $order_id = $this->getRequest()->getParam('order_id');
        $btpay_module = Mage::getSingleton('btpay/standard');
        //$btpaygate_lib_path = Mage::getModuleDir('', 'MindMagnet_BTPay').DS.'lib'.DS.'btpaygate'.DS.'btpaygate.php';
        //require_once ($btpaygate_lib_path);
        
        $_response_data = $this->getRequest()->getQuery();
        
        $btpay_module->debugInfo(array('response' => $_response_data), Zend_Log::DEBUG);
        
        try {
            if($_response_data && $order_id){

                $order = $this->_initOrder($order_id);
                
                $btpay_module = $this->_initPaymentModule($order);
                
                $btpay_response = Mage::getSingleton('btpay/paygate_response', $_response_data);
                
                $transaction_status = false;
                //VOID
                if ($btpay_response->isValid() && $btpay_response->isVoided()) {
                    $transaction_status = true;
                }
                //Save transaction into BT Pay Table 
                Mage::getModel('btpay/transaction')->saveTransaction($btpay_response, 'void', $transaction_status, $order->getId(), $btpay_module->getCode());
                
                if($transaction_status){
                    $this->_getSession()->addSuccess(Mage::helper('btpay')->__('Cererea dumneavoastră a fost efectuată cu succes.'));
                }else{
                    $this->_getSession()->addError(Mage::helper('btpay')->__('A apărut o eroare în procesarea cererii dumneavoastră.'));
                }
            }else{
                Mage::throwException(Mage::helper('btpay')->__('A apărut o eroare în procesarea cererii dumneavoastră.'));
            }
        } catch (Mage_Core_Exception $e) {
            $btpay_module->debugInfo('ERROR: '.$e->getMessage(), Zend_Log::ERR);
            
            $this->_getSession()->addError($e->getMessage());
        } catch (Exception $e) {
            $btpay_module->debugInfo('ERROR: '.$e->getMessage(), Zend_Log::ERR);
            
            $this->_getSession()->addError($e->getMessage());
            Mage::logException($e);
        }
        
        if(!empty($order)){
            $this->_redirect('*/sales_order/view/', array('order_id'=>$order->getId()));
        }else{
            $this->_redirect('*/sales_order/index/');
        }
    }

    /**
     * ACL check
     * @return bool
     */
    protected function _isAllowed()
    {
        return Mage::getSingleton('admin/session')->isAllowed('sales/order');
    }
}
