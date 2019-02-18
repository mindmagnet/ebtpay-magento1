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
class MindMagnet_BTPay_Block_Adminhtml_Sales_Order_View_Abstract extends Mage_Adminhtml_Block_Template
{
    /**
     * BT Pay Payment Methods
     *
     * @var array
     */
    private $_btpay_payment_methods = array('btpay_standard', 'btpay_star');
    
    /**
     * Indicates that block can display
     *
     * @return bool
     */
    public function canDisplayBlock()
    {
        $order = $this->getOrder();
        if ($order && $order->getId() && $order->getPayment()) {
            if(in_array($order->getPayment()->getMethod(), $this->_btpay_payment_methods)){
                return true;
            }
        }
        return false;
    }
    
    /**
     * Get current order
     *
     * @return Mage_Sales_Model_Order
     */
    public function getOrder()
    {
        return Mage::registry('current_order');
    }
}