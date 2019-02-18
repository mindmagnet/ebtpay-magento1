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

class MindMagnet_BTPay_Model_Observer extends Mage_Core_Model_Abstract
{	

    /**
     * Predispath admin action controller
     *
     * @param Varien_Event_Observer $observer
     */
    public function checkUpdate(Varien_Event_Observer $observer)
    {
        if (Mage::getSingleton('admin/session')->isLoggedIn()) {
			$updatesModel = Mage::getModel('btpay/updates');
            /* @var $updatesModel MindMagnet_BTPay_Model_Updates */
			
			$updatesModel->checkUpdate();
        }
    }
    
    /**
     * Add in order view the BT Pay Transactions section after gift_options
     *
     * Use this to add in app/design/adminhtml/default/default/template/sales/order/view/tab/info.phtml if the block does not apperar
     * <code>
     * <?php if($this->getLayout()->getBlock('btpay_transactions')) echo $this->getLayout()->getBlock('btpay_transactions')->toHtml(); ?>
     * </code>
     * 
     * @param Varien_Event_Observer $observer
     */
    public function addOrderViewTransactions(Varien_Event_Observer $observer)
    {    
        try{
            if ($block = $observer->getBlock()) {
                $gift_block_name = 'gift_options';
                if(version_compare(Mage::getVersion(), '1.5.0.1', '<')){
                    $gift_block_name = 'order_giftmessage';
                }
                if($block->getNameInLayout() == $gift_block_name){
                    $btpay_transactions_block = $block->getLayout()->getBlock('btpay_transactions');
                    if($btpay_transactions_block && $btpay_transactions_block->canDisplayBlock()){
                        $parent_html = $observer->getTransport()->getHtml();
                        $observer->getTransport()->setHtml($btpay_transactions_block->toHtml().$parent_html);
                    }
                }
            }
        } catch (Exception $e) {  
            Mage::logException($e);
        } 
    }
}