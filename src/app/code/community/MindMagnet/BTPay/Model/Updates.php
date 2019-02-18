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

class MindMagnet_BTPay_Model_Updates extends Mage_AdminNotification_Model_Feed
{
    
    const FEED_URL = 'http://www.mmecommerce.com/store/rss/updates/mageExtensions/';
    const FEED_FREQUENCY = 1; // in days
	
    /**
     * Mind Magent Extensions
     *
     * @var Varien_Data_Collection
	 * 
	 * 
     */
    private $_feedData;


    /**
     * Init model
     *
     */
    protected function _construct()
    {
		parent::_construct();
		
		$this->_feedData = new Varien_Data_Collection();
    }

    public function getFeedUrl()
    {
        if (is_null($this->_feedUrl)) {
            $this->_feedUrl = self::FEED_URL;
        }
        return $this->_feedUrl;
    }
    /**
     * Retrieve Update Frequency
     *
     * @return int
     */
    public function getFrequency()
    {
    	$frequency = self::FEED_FREQUENCY;
    	if((int)$frequency <= 0) $frequency = 1;
		
        return $frequency * 3600;
    }
	
    public function getLastUpdate()
    {
        return Mage::app()->loadCache('btpay_admin_notifications_lastcheck');
    }

    public function setLastUpdate()
    {
        Mage::app()->saveCache(time(), 'btpay_admin_notifications_lastcheck');
        return $this;
    }
    public function checkUpdate()
    {
        $this->checkExtension('btpay');
        parent::checkUpdate();
    }
    public function checkExtension($a)
    {
    	
        if(!empty($a)){
        	$item = new Varien_Object();
			$helper = Mage::helper($a);
			$moduleName = substr(get_class($this), 0, strpos(get_class($this), '_Model'));
			
			$item_data = array();
        	$item_data['module_name'] 	= $moduleName;
        	$item_data['version'] 		= Mage::getConfig()->getNode('modules/' . $moduleName . '/version');
        	$item_data['is_enabled']	= (bool)$helper->isConfigEnabled();
        	$item_data['is_dev_mode'] 	= (bool)$helper->isDevMode();
        	$item_data['is_key_active'] = (bool)$helper->check();
//        	$item_data[base64_decode(MindMagnet_BTPay_Helper_Data::XML_CONFIG)] = Mage::getStoreConfig($a.'/general/'.base64_decode(MindMagnet_BTPay_Helper_Data::XML_CONFIG));
			
			$item->setData($item_data);
			
			$this->_feedData->addItem($item);
        }
		
		$item = new Varien_Object();
		
		$item_data = array();
    	$item_data['module_name'] 	= 'Mage_Core';
    	$item_data['version'] 		= Mage::getVersion();
    	$item_data['is_enabled']	= true;
    	$item_data['is_dev_mode'] 	= (bool)Mage::helper('core')->isDevAllowed();
    	$item_data['is_key_active'] = false;
//    	$item_data[base64_decode(MindMagnet_BTPay_Helper_Data::XML_CONFIG)] = '';
		
		$item->setData($item_data);
			
		$this->_feedData->addItem($item);
		
    }
    public function checkAllExtension()
    {
		//Mage::getModel('adminnotification/inbox')->parse(array_reverse($feedData));
    }
	
    /**
     * Retrieve feed data as XML element
     *
     * @return SimpleXMLElement
     */
    public function getFeedData()
    {
    	
        $curl = new Varien_Http_Adapter_Curl();
        $curl->setConfig(array(
            'timeout'   => 2
        ));
		
		// <<<--- Get TEXT
		if(count($this->_feedData) && $this->_feedData ){
			$_feedData = $this->_feedData->toXml();
		}else{
			$_feedData = '';
		}
		//Mage::log($_feedData, null, 'dev.log', true); 
		// --->>>
        $curl->write(Zend_Http_Client::POST, $this->getFeedUrl(), '1.0',array(),$_feedData);
        $data = $curl->read();
        if ($data === false) {
            return false;
        }
        $data = preg_split('/^\r?$/m', $data, 2);
        $data = trim($data[1]);
        $curl->close();

        try {
            $xml  = new SimpleXMLElement($data);
        }
        catch (Exception $e) {
            return false;
        }

        return $xml;
    }
}