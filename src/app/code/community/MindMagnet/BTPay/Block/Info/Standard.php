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

class MindMagnet_BTPay_Block_Info_Standard extends Mage_Payment_Block_Info
{
    /**
     * Get some specific information in format of array($label => $value)
     *
     * @return array
     */
    public function getSpecificInformation()
    {
        $return = array();
        
        if($this->getInfo()->getAdditionalInformation('rambursare')){
            if($this->getInfo()->getAdditionalInformation('rambursare') == 1){
                $return [ Mage::helper('btpay')->__('Installments') ] = Mage::helper('btpay')->__('Full payment');
            }else{
                $return [ Mage::helper('btpay')->__('Installments') ] = Mage::helper('btpay')->__('%s installments without interest', $this->getInfo()->getAdditionalInformation('rambursare'));
            }
            
        }
        
        //if($this->getMethod()->getInstructions()){
        //    $return [ Mage::helper('btpay')->__('Instructions') ] = $this->getMethod()->getInstructions();
        //}
        return $return;
    }

}