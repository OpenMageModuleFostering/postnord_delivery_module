<?php
/* 
 * The MIT License
 *
 * Copyright 2016 vConnect.dk
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 * THE SOFTWARE.
 * @category Magento
 * @package Vconnect_AllInOne
 * @author vConnect
 * @email kontakt@vconnect.dk
 * @class Vconnect_AllInOne_Block_Adminhtml_Shipping_Table
 */
class Vconnect_AllInOne_Block_Adminhtml_Shipping_Table extends Mage_Adminhtml_Block_System_Config_Form_Field_Array_Abstract {

    public function __construct() {      // create columns for the table rate for the carriers(other than pickup) system config 
        $this->addColumn('orderminprice', array(
            'label' => $this->__('Min Price'),
            'size' => 6,
            'style' => 'width: 95%;',
        ));
        $this->addColumn('ordermaxprice', array(
            'label' => $this->__('Max Price'),
            'size' => 6,
            'style' => 'width: 95%;',
        ));
        $this->addColumn('orderminweight', array(
            'label' => $this->__('Min Weight'),
            'size' => 6,
            'style' => 'width: 95%;',
        ));
        $this->addColumn('ordermaxweight', array(
            'label' => $this->__('Max Weight'),
            'size' => 6,
            'style' => 'width: 95%;',
        ));
        $this->addColumn('price', array(
            'label' => $this->__('Shipping Fee'),
            'size' => 6,
            'style' => 'width: 95%;',
        ));


        $this->_addAfter = false;
        $this->_addButtonLabel = $this->__('Add Rate');

        parent::__construct();
        $this->setTemplate('vconnect/aio/methods_select.phtml'); 
    }

    

}
