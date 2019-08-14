<?php

class Vconnect_AllInOne_Model_System_Config_Source_Carrier_Theme
{
    protected $_options;

    public function toOptionArray()
    {
        if (!$this->_options) {
            $this->_options = array(
                array(
                    'label' => Mage::helper('adminhtml')->__('Red'),
                    'value' => 'red'
                ),
                array(
                    'label' => Mage::helper('adminhtml')->__('Blue'),
                    'value' => 'blue'
                ),
            );
        }
        return $this->_options;
    }
}