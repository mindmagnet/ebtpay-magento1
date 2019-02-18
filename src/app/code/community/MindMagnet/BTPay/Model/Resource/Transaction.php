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
class MindMagnet_BTPay_Model_Resource_Transaction 
    extends Mage_Core_Model_Mysql4_Abstract
{
    /**
     * Serializeable field: additional_information
     *
     * @var array
     */
    protected $_serializableFields   = array(
        'extra_info' => array(null, array())
    );

    /**
     * Initialize main table and the primary key field name
     *
     */
    protected function _construct()
    {
        $this->_init('btpay/transaction', 'transaction_id');
    }
    
    /**
     * Load the transaction object by specified order_increment_id
     *
     * @param MindMagnet_BTPay_Model_Transaction $transaction
     * @param string $orderIncrementId
     */
    public function loadObjectByOrderId(MindMagnet_BTPay_Model_Transaction $transaction, $orderIncrementId)
    {
        $select = $this->_getWriteAdapter()->select()
            ->from($this->getMainTable(), '*')
            ->where('order_increment_id = ?', $orderIncrementId)
            ->order('created_at DESC')
            ->limit(1);
            
        $data   = $this->_getWriteAdapter()->fetchRow($select);
        $transaction->setData($data);
        $this->unserializeFields($transaction);
        $this->_afterLoad($transaction);
    }
}
