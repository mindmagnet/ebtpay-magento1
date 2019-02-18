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
$installer->run("
DROP TABLE IF EXISTS `{$this->getTable('btpay/transaction')}`;
CREATE TABLE `{$this->getTable('btpay/transaction')}` (
    `transaction_id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT 'Entity Id',
    `order_id` INT(10) UNSIGNED NOT NULL COMMENT 'Order Id',
    `order_increment_id` VARCHAR(50) NULL DEFAULT NULL COMMENT 'Order Increment Id',
    `transaction_type` VARCHAR(50) NULL DEFAULT NULL COMMENT 'Transaction Type',
    `transaction_status` SMALLINT(5) UNSIGNED NULL DEFAULT NULL COMMENT 'Transaction Status',
    `amount_processed` DECIMAL(12,4) NULL DEFAULT NULL COMMENT 'Amount Processed',
    `currency_code` VARCHAR(3) NULL DEFAULT NULL COMMENT 'Currency Code',
    `order` VARCHAR(50) NULL DEFAULT NULL COMMENT 'Txn Order',
    `rrn` VARCHAR(50) NULL DEFAULT NULL COMMENT 'Txn RRN',
    `int_ref` VARCHAR(50) NULL DEFAULT NULL COMMENT 'Txn IntRef',
    `responce_message` VARCHAR(255) NULL DEFAULT NULL COMMENT 'Gateway Responce Message',
    `payment_method` VARCHAR(255) NULL DEFAULT NULL COMMENT 'Payment Method',
    `extra_info` TEXT NULL COMMENT 'Additional Information',
    `created_at` TIMESTAMP NULL DEFAULT NULL COMMENT 'Created At',
    PRIMARY KEY (`transaction_id`),
    INDEX `IDX_BTPAY_TRANSACTION_ORDER_ID` (`order_id`),
    INDEX `IDX_BTPAY_TRANSACTION_ORDER_INCREMENT_ID` (`order_increment_id`),
    INDEX `IDX_BTPAY_TRANSACTION_TRANSACTION_TYPE` (`transaction_type`),
    CONSTRAINT `FK_BTPAY_TRANSACTION_ORDER_ID_SALES_FLAT_ORDER_ENTITY_ID` FOREIGN KEY (`order_id`) REFERENCES `sales_flat_order` (`entity_id`) ON UPDATE CASCADE ON DELETE CASCADE
)
COMMENT='BTPay Payment Transaction'
COLLATE='utf8_general_ci'
ENGINE=InnoDB;
");

$installer->endSetup();