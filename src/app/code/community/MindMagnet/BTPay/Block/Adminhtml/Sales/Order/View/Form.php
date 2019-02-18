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
class MindMagnet_BTPay_Block_Adminhtml_Sales_Order_View_Form extends MindMagnet_BTPay_Block_Adminhtml_Sales_Order_View_Abstract
{
    public function getLastTransaction()
    {
        return Mage::getModel('btpay/transaction')->loadByOrderId($this->getOrder()->getIncrementId());
    }
    
    public function getFormData(){
       if(!$this->hasData('form_data')){
           
           $data = new Varien_Object;
           $_order = $this->getOrder();
           
           $data->addData($this->getLastTransaction()->getData());
           
           //Load data from order if the last transaction was not saved in the table
           if(!$data->getData('amount_processed')){
               $data->setData('amount_processed', $_order->getPayment()->getAdditionalInformation('pending_authorization_amount'));
           }
           if(!$data->getData('currency_code')){
               $data->setData('currency_code', $_order->getBaseCurrencyCode());
           }
           if(!$data->getData('order_increment_id')){
               $data->setData('order_increment_id', $_order->getIncrementId());
           }
           if(!$data->getData('order')){
               $data->setData('order', $_order->getIncrementId());
           }
           if(!$data->getData('rrn')){
               $data->setData('rrn', $_order->getPayment()->getAdditionalInformation('rrn'));
           }
           if(!$data->getData('int_ref')){
               $data->setData('int_ref', $_order->getPayment()->getAdditionalInformation('int_ref'));
           }
           $this->setData('form_data', $data);
       }
       return $this->_getData('form_data');
    }
}
