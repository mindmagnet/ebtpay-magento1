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

class MindMagnet_BTPay_Block_Form_Star extends Mage_Payment_Block_Form
{
    
    /**
     * Instructions text
     *
     * @var string
     */
    protected $_instructions;
    
    /**
     * Available installments array
     *
     * @var string
     */
    protected $_installments = null;
    
	/**
	 * Import form for card details input
	 * 
	 * @see Mage_Core_Block_Template::_construct()
	 */
	protected function _construct()
	{
		parent::_construct();
		$this->setTemplate('btpay/form/star.phtml');
	}
    
    /**
     * Get available installments text from config
     *
     * @return string
     */
    public function getAvailableInstallments() {
        
        if (is_null($this->_installments)) {
            
            $rambursare = $this->getMethod()->getConfigData('rambursare');
            
            if(!$this->getMethod() && empty($rambursare)){
                $this->_installments = false;
                
                return $this->_installments;
            }
            
            $_installments = explode(",", $rambursare);
            
            $this->_installments = array();
            foreach ($_installments as $_inst) {
                if( ((int)$_inst) > 1) $this->_installments[] = (int) $_inst;
            }

        }
        return $this->_installments;
    }
    
    /**
     * Get instructions text from config
     *
     * @return string
     */
    public function getInstructions()
    {
        if (is_null($this->_instructions)) {
            $this->_instructions = $this->getMethod()->getInstructions();
        }
        return $this->_instructions;
    }
}