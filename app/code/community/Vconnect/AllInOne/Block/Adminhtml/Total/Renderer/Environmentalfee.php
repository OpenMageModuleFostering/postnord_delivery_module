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
 * @class Vconnect_AllInOne_Block_Adminhtml_Total_Renderer_Environmentalfee
 */
class Vconnect_AllInOne_Block_Adminhtml_Total_Renderer_Environmentalfee extends Mage_Adminhtml_Block_Sales_Order_Totals
{
    public function initTotals()
    {
        $order = $this->getParentBlock()
            ->getOrder();

        if (!$order->getVconnectPostnordData()) {
            return $this;
        }

        $fee = 0;
        $fee_base = 0;

        $data = Mage::helper('core')->jsonDecode($order->getVconnectPostnordData(),true);
        if (!empty($data['environmental_fee'])) {
            $fee = 10;
            $fee_base = 10;
        }

        $baseCurrencyCode = Mage::app()->getStore($order->getStoreId())->getBaseCurrencyCode();
        $orderCurrencyCode = $order->getOrderCurrencyCode();

        if ($baseCurrencyCode != $orderCurrencyCode) {
            $fee = Mage::helper('directory')->currencyConvert($fee, $baseCurrencyCode, $orderCurrencyCode);
        }

        if ($fee > 0) {
            $total = new Varien_Object(array(
                'code'       => 'vc_aio_environmentalfee',
                'value'      => $fee,
                'base_value' => $fee_base,
                'label'      => Mage::helper('vc_aio')->__('Environmental Fee'),
            ));

            $this->getParentBlock()->addTotalBefore($total, 'shipping');
        }

        return $this;
    }
}