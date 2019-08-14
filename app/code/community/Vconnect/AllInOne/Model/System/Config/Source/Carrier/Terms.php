<?php

class Vconnect_AllInOne_Model_System_Config_Source_Carrier_Terms
{
    protected $_options;

    public function toOptionArray()
    {
        if (!$this->_options) {
            $this->_options = array(
                array(
                    'label' => Mage::helper('adminhtml')->__('Normal'),
                    'value' => 'http://www.postdanmark.dk/da/Documents/Forretningsbetingelser/brugervilkaar-smartlevering-privat-pakker.pdf'
                ),
                array(
                    'label' => Mage::helper('adminhtml')->__('Daily products'),
                    'value' => 'http://www.postdanmark.dk/da/Documents/Forretningsbetingelser/brugervilkaar-smartlevering-servicelogistik.pdf'
                ),
            );
        }
        return $this->_options;
    }
}