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
 * @class Vconnect_AllInOne_Model_Invoice_Pdf_Total
 */
class Vconnect_AllInOne_Model_Invoice_Pdf_Total extends Mage_Sales_Model_Order_Pdf_Total_Default
{
    public function getTotalsForDisplay()
    {
        $fee = $this->getAmount();
        $order = $this->getOrder();
        $incl = $order->formatPriceTxt($fee['incl']);
        $excl = $order->formatPriceTxt($fee['excl']);
        if ($this->getAmountPrefix()) {
            $incl = $this->getAmountPrefix() . $incl;
            $excl = $this->getAmountPrefix() . $excl;
        }

        $label = Mage::helper('sales')->__($this->getTitle()) . ':';

        if ($this->canDisplay() && $order->getVconnectPostnordData()) {
            $data = Mage::helper('core')->jsonDecode($order->getVconnectPostnordData(),true);
            $label = $data['additional_fee_label'];
        }

        $storeId = Mage::app()->getStore()->getId();
        $vatOption = Mage::getStoreConfig('tax/sales_display/price', $storeId);
        $fontSize = $this->getFontSize() ? $this->getFontSize() : 7;
        
        $totals = array();
        /**
         * 1 : Show exluding tax
         * 2 : Show including tax
         * 3 : Show both
         */
        if ($vatOption == '1' || $vatOption == '3') {
            $total = array(
                'amount'    => $excl,
                'font_size' => $fontSize
            );
            $exclLabel = $label;
            if ($vatOption == '3') {
                $exclLabel .= ' (Excl.Tax)';
            }
            $total['label'] = $exclLabel;
            $totals[] = $total;
        }
        if ($vatOption == '2' || $vatOption == '3') {
            $total = array(
                'amount'    => $incl,
                'font_size' => $fontSize
            );
            $inclLabel = $label;
            if ($vatOption == '3') {
                $inclLabel .= ' (Incl.Tax)';
            }
            $total['label'] = $inclLabel;
            $totals[] = $total;
        }

        return $totals;
    }

    public function canDisplay()
    {
        $amount = $this->getAmount();
        return ($amount['incl'] !== 0);
    }

    public function getAmount()
    {
        $fee = 0;
        $fee_base = 0;

        if (!$this->getOrder()->getVconnectPostnordData()) {
            return array(
                'incl' => $fee,
                'excl' => $fee
            );
        }

        $data = Mage::helper('core')->jsonDecode($this->getOrder()->getVconnectPostnordData(),true);
        if (!empty($data['additional_fee_amount'])) {
            $fee = $data['additional_fee_amount'];
            $fee_base = $data['additional_fee_amount'];
        }

        $baseCurrencyCode = Mage::app()->getStore($this->getOrder()->getStoreId())->getBaseCurrencyCode();
        $orderCurrencyCode = $this->getOrder()->getOrderCurrencyCode();

        if ($baseCurrencyCode != $orderCurrencyCode) {
            $fee = Mage::helper('directory')->currencyConvert($fee, $baseCurrencyCode, $orderCurrencyCode);
        }

        return array(
            'incl' => $fee,
            'excl' => $fee
        );
    }

}
