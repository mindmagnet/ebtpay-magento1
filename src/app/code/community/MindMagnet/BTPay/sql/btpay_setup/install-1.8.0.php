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

/** @var $installer Mage_Sales_Model_Resource_Setup */
$installer = $this;

$installer->startSetup();

/**
 * Create table 'btpay/transaction'
 */
$table = $installer->getConnection()
    ->newTable($installer->getTable('btpay/transaction'))
    ->addColumn('transaction_id', Varien_Db_Ddl_Table::TYPE_INTEGER, null, array(
        'identity'  => true,
        'unsigned'  => true,
        'nullable'  => false,
        'primary'   => true,
        ), 'Entity Id')
    ->addColumn('order_id', Varien_Db_Ddl_Table::TYPE_INTEGER, null, array(
        'unsigned'  => true,
        'nullable'  => false,
        ), 'Order Id')
    ->addColumn('order_increment_id', Varien_Db_Ddl_Table::TYPE_TEXT, 50, array(
        ), 'Order Increment Id')
    ->addColumn('transaction_type', Varien_Db_Ddl_Table::TYPE_TEXT, 50, array(
        ), 'Transaction Type')
    ->addColumn('transaction_status', Varien_Db_Ddl_Table::TYPE_SMALLINT, null, array(
        'unsigned'  => true,
        ), 'Transaction Status')
    ->addColumn('amount_processed', Varien_Db_Ddl_Table::TYPE_DECIMAL, '12,4', array(
        ), 'Amount Processed')
    ->addColumn('currency_code', Varien_Db_Ddl_Table::TYPE_TEXT, 3, array(
        ), 'Currency Code')
    ->addColumn('order', Varien_Db_Ddl_Table::TYPE_TEXT, 50, array(
        ), 'Txn Order')
    ->addColumn('rrn', Varien_Db_Ddl_Table::TYPE_TEXT, 50, array(
        ), 'Txn RRN')
    ->addColumn('int_ref', Varien_Db_Ddl_Table::TYPE_TEXT, 50, array(
        ), 'Txn IntRef')
    ->addColumn('responce_message', Varien_Db_Ddl_Table::TYPE_TEXT, 255, array(
        ), 'Gateway Responce Message')
    ->addColumn('payment_method', Varien_Db_Ddl_Table::TYPE_TEXT, 255, array(
        ), 'Payment Method')
    ->addColumn('extra_info', Varien_Db_Ddl_Table::TYPE_TEXT, '64K', array(
        ), 'Additional Information')
    ->addColumn('created_at', Varien_Db_Ddl_Table::TYPE_TIMESTAMP, null, array(
        ), 'Created At')
    ->addIndex($installer->getIdxName('btpay/transaction', array('order_id')),
        array('order_id'))
    ->addIndex($installer->getIdxName('btpay/transaction', array('order_increment_id')),
        array('order_increment_id'))
    ->addIndex($installer->getIdxName('btpay/transaction', array('transaction_type')),
        array('transaction_type'))
    ->addForeignKey($installer->getFkName('btpay/transaction', 'order_id', 'sales/order', 'entity_id'),
        'order_id', $installer->getTable('sales/order'), 'entity_id',
        Varien_Db_Ddl_Table::ACTION_CASCADE, Varien_Db_Ddl_Table::ACTION_CASCADE)
    ->setComment('BTPay Payment Transaction');
$installer->getConnection()->createTable($table);


$installer->endSetup();

