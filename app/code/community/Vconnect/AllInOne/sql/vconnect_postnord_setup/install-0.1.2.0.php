<?php
$installer = $this;
/* @var $installer Mage_Sales_Model_Resource_Setup */
$installer->startSetup();

$installer->addAttribute('order', 'vconnect_postnord_data', array(
    'type'=>'text'
));
$installer->addAttribute('quote', 'vconnect_postnord_data', array(
    'type'=>'text'
));

$installer->endSetup();

