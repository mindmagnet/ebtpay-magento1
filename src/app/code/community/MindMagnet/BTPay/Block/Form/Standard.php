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
 */

class MindMagnet_BTPay_Block_Form_Standard extends Mage_Payment_Block_Form
{
	/**
	 * Import form for card details input
	 * 
	 * @see Mage_Core_Block_Template::_construct()
	 */
	protected function _construct()
	{
		parent::_construct();
        
        /**
         * @deprecated NOT USED - We use default magento form for this module. We do not need to add extra input values
         */
		//$this->setTemplate('btpay/form/standard.phtml');
	}
}