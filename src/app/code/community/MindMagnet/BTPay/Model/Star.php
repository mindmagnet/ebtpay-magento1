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

class MindMagnet_BTPay_Model_Star extends MindMagnet_BTPay_Model_Standard
{
	/**
	 * Payment method identification name
	 * 
	 * @var string
	 */
	protected $_code = 'btpay_star';
    
    /**
     * Payment block paths
     *
     * @var string
     */
	protected $_formBlockType = 'btpay/form_star';
    protected $_infoBlockType = 'btpay/info_star';

}