<?php
class Vconnect_AllInOne_Model_System_Config_Source_Carrier_Orderstate extends Mage_Eav_Model_Entity_Attribute_Source_Table
{
    protected $_options;
    /**
     * 
     * @return array
     */
    public function toOptionArray()
    {
        if(!$this->_options){
            $this->_options =  Mage::getResourceModel('sales/order_status_collection')
                    ->addStateFilter(Mage_Sales_Model_Order::STATE_PROCESSING)
                    ->toOptionHash();
            array_unshift($this->_options, array('value'=>'',
                'label'=>Mage::helper('adminhtml')->__('--Please Select--')));
        }
        return $this->_options;
    }
    
    /**
     * 
     * @return array
     */
    public function toArray()
    {
        return $this->toOptionArray();
    }
}


