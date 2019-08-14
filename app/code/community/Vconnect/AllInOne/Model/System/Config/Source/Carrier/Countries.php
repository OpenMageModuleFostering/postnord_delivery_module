<?php

class Vconnect_AllInOne_Model_System_Config_Source_Carrier_Countries
{
    protected $_options;

    public function toOptionArray()
    {
        if (!$this->_options) {
            $this->_options = array(
                array(
                    'label' => Mage::helper('adminhtml')->__('Denmark'),
                    'value' => 'DK'
                ),
                
                array(
                    'label' => Mage::helper('adminhtml')->__('Sweden'),
                    'value' => 'SE'
                ),
                
                array(
                    'label' => Mage::helper('adminhtml')->__('Norway'),
                    'value' => 'NO'
                ),
                
                array(
                    'label' => Mage::helper('adminhtml')->__('Finland'),
                    'value' => 'FI'
                ),
                
            );
        }
        return $this->_options;
    }
    
    public function toArray()
    {
        return  array_map(function($item)
                            {
                                return strtolower($item['value']);
                            }
                        , $this->toOptionArray()
                );
    }
}