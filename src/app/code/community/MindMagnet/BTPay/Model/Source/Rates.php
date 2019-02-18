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

class MindMagnet_BTPay_Model_Source_Rates
{
    public function toOptionArray()
    {
        $options = array(
        	array(	'label'=>'Standard Module', 
        			'value'=>'standard',
        	),
        	array(	'label'=>'Standard with Rates Module', 
        			'value'=>'standard_rates',
        	),
        	
        );

        return $options;
    }
}