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
 * @class Vconnect_AllInOne_Model_Creditmemo_Total
 */
class Vconnect_AllInOne_Model_Creditmemo_Total extends Mage_Sales_Model_Order_Creditmemo_Total_Abstract
{
    public function collect(Mage_Sales_Model_Order_Creditmemo $creditmemo)
    {
        if ($creditmemo->getOrder()->hasCreditmemos() != 0 || !$creditmemo->getOrder()->getVconnectPostnordData()) {
            return $this;
        }

        $fee = 0;
        $fee_base = 0;

        $data = Mage::helper('core')->jsonDecode($creditmemo->getOrder()->getVconnectPostnordData(),true);
        if (!empty($data['additional_fee_amount'])) {
            $fee = $data['additional_fee_amount'];
            $fee_base = $data['additional_fee_amount'];
        }

        $baseCurrencyCode = Mage::app()->getStore($creditmemo->getOrder()->getStoreId())->getBaseCurrencyCode();
        $orderCurrencyCode = $creditmemo->getOrder()->getOrderCurrencyCode();

        if ($baseCurrencyCode != $orderCurrencyCode) {
            $fee = Mage::helper('directory')->currencyConvert($fee, $baseCurrencyCode, $orderCurrencyCode);
        }

        if (!$fee) {
            return $this;
        }

        $creditmemo->setBaseGrandTotal($creditmemo->getBaseGrandTotal() + $fee_base);
        $creditmemo->setGrandTotal($creditmemo->getGrandTotal() + $fee);

        return $this;
    }
}
