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

class MindMagnet_BTPay_Model_Resource_Transaction_Collection
    extends Mage_Core_Model_Mysql4_Collection_Abstract
{
    /**
     * Created Before filter
     *
     * @var string
     */
    protected $_createdBefore          = "";
    /**
     * Initialize collection items factory class
     */
    protected function _construct()
    {
        $this->_init('btpay/transaction');
        parent::_construct();
    }

    /**
     * CreatedAt filter setter
     *
     * @param string $date
     * @return MindMagnet_BTPay_Model_Resource_Transaction_Collection
     */
    public function addCreatedBeforeFilter($date)
    {
        $this->_createdBefore = $date;
        return $this;
    }

    /**
     * Prepare filters
     *
     * @return MindMagnet_BTPay_Model_Resource_Transaction_Collection
     */
    protected function _beforeLoad()
    {
        parent::_beforeLoad();

        if ($this->isLoaded()) {
            return $this;
        }

        // filters
        if ($this->_createdBefore) {
            $this->getSelect()->where('main_table.created_at < ?', $this->_createdBefore);
        }
        return $this;
    }

    /**
     * Unserialize additional_information in each item
     *
     * @return MindMagnet_BTPay_Model_Resource_Transaction_Collection
     */
    protected function _afterLoad()
    {
        foreach ($this->_items as $item) {
            $this->getResource()->unserializeFields($item);
        }
        return parent::_afterLoad();
    }
}
