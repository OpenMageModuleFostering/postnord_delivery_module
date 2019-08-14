<?php

class Vconnect_AllInOneDk_Model_Adminhtml_System_Config_Source_Orderstate extends Mage_Eav_Model_Entity_Attribute_Source_Table
{
    protected $_statuses;
    /**
     * 
     * @return array
     */
    public function toOptionArray()
    {
       if(!$this->_statuses){
           $this->_statuses =  Mage::getResourceModel('sales/order_status_collection')
                    ->addStateFilter(Mage_Sales_Model_Order::STATE_PROCESSING)
                    ->toOptionHash();
            array_unshift($this->_statuses, array('value'=>'',
                'label'=>Mage::helper('adminhtml')->__('--Please Select--')));
        }
        
        return $this->_statuses;
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


