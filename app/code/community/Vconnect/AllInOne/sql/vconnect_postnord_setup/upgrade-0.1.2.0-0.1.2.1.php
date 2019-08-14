<?php
$installer = $this;
/* @var $installer Mage_Sales_Model_Resource_Setup */
$installer->startSetup();
//quote_address_shipping_rate
//$quote_address_shipping_rate

$installer->getConnection()
->addColumn($installer->getTable('sales/quote_address_shipping_rate'),'vc_method_data', array(
    'type'      => Varien_Db_Ddl_Table::TYPE_TEXT,
    'nullable'  => true,
    'length'    => null,
    'after'     => null, // column name to insert new column after
    'comment'   => 'Postnord data'
    )); 

$installer->endSetup();

