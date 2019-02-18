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

class MindMagnet_BTPay_Helper_Data extends Mage_Core_Helper_Abstract
{
	const XML_CONFIG = 'bGljZW5zZV9rZXk=';

    /**
     * MindMagnet_BTPay module platform
     *
     * @var string
     */
    protected $_platform;
    /**
     * MindMagnet_BTPay module Self Name
     *
     * @var string
     */
    protected $_selfName;

    public function isConfigEnabled()
    {
        //return Mage::getStoreConfigFlag('payment/btpay/active');
        return true;
    }
	
    public function getSelfName()
    {
        if (is_null($this->_selfName)) {
            $this->_selfName = Mage::getConfig()->getNode('modules/' . $this->_getModuleName() . '/self_name');
        }
        return $this->_selfName;
    }
	
    public function getPlatform()
    {
        if (is_null($this->_platform)) {
            $this->_platform = Mage::getConfig()->getNode('modules/' . $this->_getModuleName() . '/platform');
        }
        return $this->_platform;
    }

    public function isEnabled()
    {		
        return $this->isConfigEnabled()&&($this->isDevMode()||$this->check());
    }
	
	public static function hasMagePersistentModule()
	{
		return Mage::getConfig()->getModuleConfig('Mage_Persistent')->is('active');	
	}
	
    public function check($s = null, $l = null)
    {
        return true;

    	$s = trim(preg_replace('/\s{2,}/siu', '', Mage::getStoreConfig('payment/btpay_api/'.base64_decode(self::XML_CONFIG))));
		$s_list = Mage::helper('core/string')->str_split($s, 40,false,true);
		$serverName = trim($_SERVER['SERVER_NAME']);
		if(is_array($s_list) && !empty($s_list) && !empty($serverName) ){
			foreach($s_list as $s){
				if($this->_checkItem($this->getDomain($serverName),$s)){
					return true;
				}
				if($this->_checkItem($serverName,$s)){
					return true;
				}
			}
		}

		return false;
    }
	
    private function _checkItem($d, $s)
    {
        $key = $this->_getKey();
        if(strlen($key) && strlen($d) && strlen($s) && sha1($key.$d) == $s) {
            return true;
        }

        return false;
    }

	
    public function isDevMode()
    {
    	//If Server Name is an IP then this extension is in DevMode
		if(Mage::helper('core/http')->validateIpAddr($_SERVER['SERVER_NAME'])){
			return true;
		}
		
    	//If Server Name is 'localhost' then this extension is in DevMode
		if(trim($_SERVER['SERVER_NAME']) == 'localhost'){
			return true;
		}
		
    	//If Server Name is has '.local' then this extension is in DevMode
		if(strpos($_SERVER['SERVER_NAME'], '.local') !== false){
			return true;
		}
		
		return false; // ignore rest
		
		//Based on Magento "isDevAllowed" logic  
        $allowedIps = Mage::getStoreConfig(Mage_Core_Helper_Data::XML_PATH_DEV_ALLOW_IPS);
        $remoteAddr = Mage::helper('core/http')->getRemoteAddr();
		//$httpHost   = Mage::helper('core/http')->getHttpHost();
		//$serverAddr = Mage::helper('core/http')->getServerAddr();
		
		if (!empty($allowedIps) && !empty($remoteAddr) && Mage::helper('core')->isDevAllowed()){
			return true;
		}
		
		return false;
    }
	
    private function _getKey()
    {
    	$platform = $this->getPlatform();
    	if($platform){
    		$platform = strtoupper((string)$platform);
			
			return sha1(base64_encode($this->_getModuleName()).$platform);
    	}
        return sha1(base64_encode($this->_getModuleName()));
    }
	
	/**
	 * PHP version of validateIpAddr()
	 * 
	 * @deprecated in favor of Mage::helper('core/http')->validateIpAddr();
	 */
    public function validateIpAddr($ip)
    {
    	return ip2long($ip) !== false;
    }
	
	public function getDomain($url)
	{
	  $pieces = parse_url($url);
	  $domain = isset($pieces['host']) ? $pieces['host'] : $url;
	  if (preg_match('/(?P<domain>[a-z0-9][a-z0-9\-]{1,63}\.[a-z\.]{2,6})$/i', $domain, $regs)) {
	    return $regs['domain'];
	  }
	  return false;
	}
}