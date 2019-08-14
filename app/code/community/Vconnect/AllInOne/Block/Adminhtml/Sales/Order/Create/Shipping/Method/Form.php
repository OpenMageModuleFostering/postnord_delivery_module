<?php
class Vconnect_AllInOne_Block_Adminhtml_Sales_Order_Create_Shipping_Method_Form extends Mage_Adminhtml_Block_Sales_Order_Create_Shipping_Method_Form
{
    protected function _toHtml(){
        $this->setTemplate('vconnect/sales/order/create/shipping/method/form.phtml');
        return parent::_toHtml();
    }
}
?>