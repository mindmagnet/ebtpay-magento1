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
class MindMagnet_BTPay_Block_Adminhtml_Sales_Order_View_Transactions extends MindMagnet_BTPay_Block_Adminhtml_Sales_Order_View_Abstract
{
    public function getTransactions(){
        $collection = Mage::getModel('btpay/transaction')->getCollection();
        $collection->addFieldToFilter('order_increment_id', array('eq' => $this->getOrder()->getIncrementId()));
        
        return $collection;
    }
}
