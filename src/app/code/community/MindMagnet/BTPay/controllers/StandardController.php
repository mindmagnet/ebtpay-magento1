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

class MindMagnet_BTPay_StandardController extends Mage_Core_Controller_Front_Action
{
    /**
     * Get checkout session model instance
     *
     * @return Mage_Checkout_Model_Session
     */
    protected function _getSession()
    {
        return Mage::getSingleton('checkout/session');
    }
    
    /**
     * Send request to BT Pay Gateway
     *
     */
    public function placeAction()
    {
        $session = $this->_getSession();
        
        $btpay_module = Mage::getSingleton('btpay/standard');
        /* @var $btpay_module MindMagnet_BTPay_Model_Standard */ 
        
        //$btpaygate_lib_path = Mage::getModuleDir('', 'MindMagnet_BTPay').DS.'lib'.DS.'btpaygate'.DS.'btpaygate.php';
        //require_once ($btpaygate_lib_path);
        try {
            //var_dump($session);
            $order_increment_id = $session->getLastRealOrderId();
            $order_id = $session->getLastOrderId();
            
            if(!$order_increment_id || !$order_id){
                Mage::throwException('Invalid order number!');
            }
                
            if ($order_id && $order_increment_id) {
                
                $order = Mage::getModel("sales/order")->loadByIncrementId($order_increment_id);
                /** @var $order Mage_Sales_Model_Order */  
                
                if( !$order || !$order->getId()){
                    Mage::throwException(Mage::helper('btpay')->__('Order # %s was not found!',$order_increment_id));
                }

                $btpay_module = $order->getPayment()->getMethodInstance();
                
                //Set payment method store id
                $btpay_module->setStore($order->getStoreId());
                
                $amount = $order->getPayment()->getAdditionalInformation('pending_authorization_amount');
                
                if(!$amount){
                    $amount = $order->getBaseTotalDue();
                }
                
                $btpay_request = Mage::getSingleton('btpay/paygate_request');
                $btpay_request->setAmount($amount);
                $btpay_request->setCurrency($order->getBaseCurrencyCode());
                $btpay_request->setOrder($order_increment_id);
                $btpay_request->setDesc('Order '.$order_increment_id);
                
                $debug_msg =    "\n"."AUTHORIZE SEND REQUEST ... \n".
                                " AMOUNT = ". $btpay_request->getAmount()."\n".
                                " CURRENCY = ".$btpay_request->getCurrency()."\n".
                                " ORDER: ".$btpay_request->getOrder()."\n".
                                " DESC: ".$btpay_request->getDesc()."\n";
                $btpay_module->debugInfo($debug_msg, Zend_Log::DEBUG);
                
                
                $html = '';
                $html .= '<div style="margin: 0 auto; width: 960px; min-height: 450px; text-align: center;">';
    
                //Debug only
                //$html .= "<pre>".print_r($payment,1)."</pre>";
                $html .= "<p>".Mage::helper('btpay')->__('Tranzactie in curs ...')."</pre>";
                
                $html .= '<div id="payment_form_block" style="display:block !important; border:0 !important; margin:0 !important; padding:0 !important; font-size:0 !important; line-height:0 !important; width:0 !important; height:0 !important; overflow:hidden !important;">';
                $html .= $btpay_module->renderAuthForm($btpay_request, Mage::getUrl('btpay/standard/return', array('_secure' => true)));
                $html .= '</div>';
                
                $html .= '<script type="text/javascript"> if(document.forms[0]) { document.forms[0].submit(); } else { document.getElementById("payment_form_block").removeAttribute("style"); } </script>';
                
                $html .= '</div>';
                $this->getResponse()->setBody($html);
                
            }
        } catch (Mage_Core_Exception $e) {
            
            $btpay_module->debugInfo('ERROR: '.$e->getMessage(), Zend_Log::ERR);
            
            Mage::getSingleton('core/session')->addError(Mage::helper('btpay')->__('There was an error processing your order. Please contact us or try again later.'));
            $this->_redirect('checkout/onepage/failure');
            return;
        } catch (Exception $e) {
            
            Mage::logException($e);
            
            $btpay_module->debugInfo('PHP_ERROR: '.$e->getMessage(), Zend_Log::ERR);
            
            Mage::getSingleton('core/session')->addError(Mage::helper('btpay')->__('There was an error processing your order. Please contact us or try again later.'));
            $this->_redirect('checkout/onepage/failure');
            return;
        }
    }

    /**
     * Get response from BT Pay Gateway
     *
     */
    public function returnAction()
    {
        $session = $this->_getSession();
        
        $btpay_module = Mage::getSingleton('btpay/standard');
        /* @var $btpay_module MindMagnet_BTPay_Model_Standard */
        
        try {
            
    
            //$btpaygate_lib_path = Mage::getModuleDir('', 'MindMagnet_BTPay').DS.'lib'.DS.'btpaygate'.DS.'btpaygate.php';
            //require_once ($btpaygate_lib_path);
            
            if(!is_array($_GET) || empty($_GET['ORDER'])){
                Mage::throwException('Invalid data returned from payment gateway!');
            }
            
            $order = Mage::getModel("sales/order")->loadByIncrementId($_GET['ORDER']);
            
            if( !$order || !$order->getId()){
                Mage::throwException(Mage::helper('btpay')->__('Invalid order number returned from payment gateway! Order # %s was not found!',$_GET['ORDER']));
            }
            
            if( $order->getState() != Mage_Sales_Model_Order::STATE_PAYMENT_REVIEW){
                Mage::throwException('Invalid order state! Order should be in "'.Mage_Sales_Model_Order::STATE_PAYMENT_REVIEW.'".');
            }
            
            $btpay_module = $order->getPayment()->getMethodInstance();
            //Set payment method store id
            $btpay_module->setStore($order->getStoreId());
            
            $debugData = array(
                'response' => $_GET
            );
            $btpay_module->debugData($debugData, Zend_Log::DEBUG);
                        
            if ($order->getId() &&  $order->getState() == Mage_Sales_Model_Order::STATE_PAYMENT_REVIEW) {
                
                //AUTH VALIDATION
                /** @var $btpay_response MindMagnet_BTPay_Model_Paygate_Response */
                $btpay_response = Mage::getSingleton('btpay/paygate_response', $_GET);
                
                if ($btpay_response->isValid() && $btpay_response->isAuthorized() && $order->getPayment()->getAdditionalInformation('pending_authorization_amount') == $btpay_response->getAmount()) {
                    
                    //Save transaction into BT Pay Table 
                    Mage::getModel('btpay/transaction')->saveTransaction($btpay_response, 'preauthorize', true, $order->getId(), $btpay_module->getCode());
                    
                    //psign is valid, verify response
                    $debug_msg =    "\n"."AUTHORIZE VALID RESPONSE ... \n".
                                    " ORDER = ".$btpay_response->getOrder()."\n".
                                    " AMOUNT = ".$btpay_response->getAmount()."\n".
                                    " RRN: ".$btpay_response->getRrn()."\n".
                                    " IntRef: ".$btpay_response->getIntRef()."\n".
                                    " APPROVAL: ".$btpay_response->getApproval()."\n".
                                    " ACTION: ".$btpay_response->getAction()."\n".
                                    " RC: ".$btpay_response->getRc()."\n".
                                    " MESSAGE: ".$btpay_response->getMessage()."\n";
                    
                    $btpay_module->debugInfo($debug_msg, Zend_Log::DEBUG);
                    
                    //Save transaction inforamtion into Payment Additional Information
                    $order->getPayment()->setAdditionalInformation('rrn', $btpay_response->getRrn());
                    $order->getPayment()->setAdditionalInformation('int_ref', $btpay_response->getIntRef());
                    
                    //Set last_trans_id = int_ref - used by order refund, void and capture actions 
                    // this is set anyway by the $payment->_addTransaction() action.
                    $order->getPayment()->setLastTransId($btpay_response->getIntRef());  
                    
                    //make sure we set Transaction Id
                    $order->getPayment()->setTransactionId($btpay_response->getIntRef());
                    
                    $order->getPayment()->setIsTransactionPending(false); // make sure that IsTransactionPending is false
                    $order->getPayment()->setIsTransactionClosed(false); // this allows void and cancel after authorize  
                    
                    //Add AUTHORIZATION_CODE = APPROVAL in Transaction Additional Info
                    $order->getPayment()->setTransactionAdditionalInfo('authorization_code', $btpay_response->getApproval());

                    $order->getPayment()->authorize(true, $btpay_response->getAmount());

                    $orderStatus = $btpay_module->getConfigData('order_status');
                    $order->setState(Mage_Sales_Model_Order::STATE_PROCESSING, $orderStatus,
                        Mage::helper('btpay')->__('Authorized amount of %s approved by payment gateway.', $order->getBaseCurrency()->formatTxt($btpay_response->getAmount()))."<br />".
                        Mage::helper('btpay')->__('IntRef: %s RRN: %s<br />AUTHORIZATION_CODE: %s<br />ACTION_CODE: %s RC_CODE: %s MESSAGE: %s ', 
                            $btpay_response->getIntRef(), 
                            $btpay_response->getRrn(), 
                            $btpay_response->getApproval(), 
                            $btpay_response->getAction(), 
                            $btpay_response->getRc(), 
                            $btpay_response->getMessage()
                        )
                    );
                    $order->save();
                    
                    $order->sendNewOrderEmail();                 
                    
                    $this->_redirect('checkout/onepage/success');
                    return;
                    
                } else {
                    //Save transaction into BT Pay Table 
                    Mage::getModel('btpay/transaction')->saveTransaction($btpay_response, 'preauthorize', false, $order->getId(), $btpay_module->getCode());
                    
                    //possible fraud, log details mark as failed
                    $debug_msg =    "\n"."AUTHORIZE INVALID RESPONSE ... LOGGING POSSIBLE FRAUD ... \n".
                                    " ORDER = ".$btpay_response->getOrder()."\n".
                                    " AMOUNT = ".$btpay_response->getAmount()."\n".
                                    " RRN: ".$btpay_response->getRrn()."\n".
                                    " IntRef: ".$btpay_response->getIntRef()."\n".
                                    " APPROVAL: ".$btpay_response->getApproval()."\n".
                                    " ACTION: ".$btpay_response->getAction()."\n".
                                    " RC: ".$btpay_response->getRc()."\n".
                                    " MESSAGE: ".$btpay_response->getMessage()."\n";
                    
                    $order_status_message = '';
                    
                    if(!$btpay_response->isValid() || !$btpay_response->isAuthorized()){
                        $order_status_message .= 
                            Mage::helper('btpay')->__('Invalid response returned from payment gateway!')."<br />".
                            Mage::helper('btpay')->__('IntRef: %s RRN: %s<br />AUTHORIZATION_CODE: %s<br />ACTION_CODE: %s RC_CODE: %s MESSAGE: %s ', 
                                $btpay_response->getIntRef(), 
                                $btpay_response->getRrn(), 
                                $btpay_response->getApproval(), 
                                $btpay_response->getAction(), 
                                $btpay_response->getRc(), 
                                $btpay_response->getMessage()
                            );
                    }

                    if($order->getPayment()->getAdditionalInformation('pending_authorization_amount') != $btpay_response->getAmount()){
                        $debug_msg .= Mage::helper('btpay')->__('Invalid authorization amount: response amount = %s does not match initial authorization amount = %s',$btpay_response->getAmount(), $order->getPayment()->getAdditionalInformation('pending_authorization_amount'))."\n";
                        
                        $order_status_message .= '<br />'.Mage::helper('btpay')->__('Invalid authorization amount: response amount = %s does not match initial authorization amount = %s',$btpay_response->getAmount(), $order->getPayment()->getAdditionalInformation('pending_authorization_amount'));
                    }
                    
                    $btpay_module->debugInfo($debug_msg, Zend_Log::CRIT);
                    
                    $order_state = Mage_Sales_Model_Order::STATE_PAYMENT_REVIEW;
                    
                    if($btpay_response->getAction() == MindMagnet_BTPay_Model_Paygate_Response::ACTION_REJECTED || $btpay_response->getAction() == MindMagnet_BTPay_Model_Paygate_Response::ACTION_ERROR){
                        $order_state = Mage_Sales_Model_Order::STATE_CANCELED;
                    }
                                    
                    $order->setState($order_state, true, $order_status_message, false);
                    
                    $order->save();
                    
                    
                    if($order->getPayment()->getAdditionalInformation('pending_authorization_amount') != $btpay_response->getAmount()){
                        Mage::throwException('Possible fraud!'."\n".Mage::helper('btpay')->__('Invalid authorization amount: response amount = %s does not match initial authorization amount = %s',$btpay_response->getAmount(), $order->getPayment()->getAdditionalInformation('pending_authorization_amount')));
                    }
                }

            } else {
                Mage::throwException('Invalid order number returned from payment gateway!');
            }

            
        } catch (Mage_Core_Exception $e) {
            
            $btpay_module->debugInfo('ERROR: '.$e->getMessage(), Zend_Log::ERR);
            
            $debugData = array(
                'response' => $_GET
            );
            $btpay_module->debugData($debugData, Zend_Log::ERR);
            
        } catch (Exception $e) {
            Mage::logException($e);
            
            $btpay_module->debugInfo('PHP_ERROR: '.$e->getMessage(), Zend_Log::ERR);
            
            $debugData = array(
                'response' => $_GET
            );
            $btpay_module->debugData($debugData, Zend_Log::ERR);
        }

        Mage::getSingleton('core/session')->addError(Mage::helper('btpay')->__('There was an error processing your order. Please contact us or try again later.'));
        $this->_redirect('checkout/onepage/failure');
        return;
    }

    /**
     * Get response from BT Pay Gateway on capture action
     * 
     * We use cUrl to get info, this should not be called.
     *
     */
    public function returnCaptureAction()
    {
        $session = $this->_getSession();
        
        $btpay_module = Mage::getSingleton("btpay/standard");
        /* @var $btpay_module MindMagnet_BTPay_Model_Standard */
        
        $btpay_module->debugInfo(null, Zend_Log::DEBUG);
        
        $debugData = array(
            'response' => $_GET
        );
        $btpay_module->debugData($debugData);
  

        //Mage::getSingleton('core/session')->addError(Mage::helper('btpay')->__('There was an error processing your order. Please contact us or try again later.'));
        
        $this->_redirect('');
        return;
    }

    /**
     * Get response from BT Pay Gateway on void action
     * 
     * We use cUrl to get, info this should not be called.
     *
     */
    public function returnVoidAction()
    {
        $session = $this->_getSession();
        
        $btpay_module = Mage::getSingleton("btpay/standard");
        /* @var $btpay_module MindMagnet_BTPay_Model_Standard */
        
        $btpay_module->debugInfo(null, Zend_Log::DEBUG);
        
        $debugData = array(
            'response' => $_GET
        );
        $btpay_module->debugData($debugData);
  

        //Mage::getSingleton('core/session')->addError(Mage::helper('btpay')->__('There was an error processing your order. Please contact us or try again later.'));
        
        $this->_redirect('');
        return;
    }
}
