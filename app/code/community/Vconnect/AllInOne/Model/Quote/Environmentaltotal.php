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
 * @class Vconnect_AllInOne_Model_Quote_Environmentaltotal
 */
class Vconnect_AllInOne_Model_Quote_Environmentaltotal extends Mage_Sales_Model_Quote_Address_Total_Abstract
{
    protected $address;
    protected $paymentMethod;

    public function __construct()
    {
        $this->setCode('vc_aio_environmentalfee');
    }

    public function collect(Mage_Sales_Model_Quote_Address $address)
    {
        if ($address->getAddressType() != 'shipping') {
            return $this;
        }

        $this->address = $address;

        if ($this->address->getQuote()->getId() == null) {
            return $this;
        }

        $items = $this->address->getAllItems();
        if (!count($items)) {
            return $this;
        }

        if (Mage::app()->getRequest()->getControllerModule() != 'Avenla_KlarnaCheckout') {
            $payment = $this->address->getQuote()->getPayment();

            try {
                $this->paymentMethod = $payment->getMethodInstance();
            } catch (Mage_Core_Exception $e) {
                return $this;
            }

            if (!$this->paymentMethod instanceof Mage_Payment_Model_Method_Abstract) {
                return $this;
            }
        }

        if (!$this->address->getQuote()->getVconnectPostnordData()) {
            return $this;
        }

        $fee = 0;
        $fee_base = 0;

        $data = Mage::helper('core')->jsonDecode($this->address->getQuote()->getVconnectPostnordData(),true);
        if (!empty($data['environmental_fee'])) {
            $fee = 10;
            $fee_base = 10;
        }

        $baseCurrencyCode = Mage::app()->getStore()->getBaseCurrencyCode();
        $currentCurrencyCode = Mage::app()->getStore()->getCurrentCurrencyCode();

        if ($baseCurrencyCode != $currentCurrencyCode) {
            $fee = Mage::helper('directory')->currencyConvert($fee, $baseCurrencyCode, $currentCurrencyCode);
        }

        if ($fee > 0) {
            if (Mage::app()->getRequest()->getControllerModule() == 'Avenla_KlarnaCheckout') {
                $address->setBaseShippingInclTax($address->getBaseShippingInclTax() + $fee_base);
                $address->setShippingInclTax($address->getShippingInclTax() + $fee);
            }

            $address->setBaseGrandTotal($address->getBaseGrandTotal() + $fee_base);
            $address->setGrandTotal($address->getGrandTotal() + $fee);
        }

        return $this;
    }

    public function fetch(Mage_Sales_Model_Quote_Address $address)
    {
        if ($address->getAddressType() != 'shipping' || !$address->getQuote()->getVconnectPostnordData()) {
            return $this;
        }

        $fee = 0;

        $data = Mage::helper('core')->jsonDecode($address->getQuote()->getVconnectPostnordData(),true);
        if (!empty($data['environmental_fee'])) {
            $fee = 10;
        }

        $baseCurrencyCode = Mage::app()->getStore()->getBaseCurrencyCode();
        $currentCurrencyCode = Mage::app()->getStore()->getCurrentCurrencyCode();

        if ($baseCurrencyCode != $currentCurrencyCode) {
            $fee = Mage::helper('directory')->currencyConvert($fee, $baseCurrencyCode, $currentCurrencyCode);
        }

        if ($fee > 0) {
            $total = array(
                'code'  => $this->getCode(),
                'title' => Mage::helper('vc_aio')->__('Environmental Fee'),
                'value' => $fee,
            );
            $address->addTotal($total);
        }

        return $this;
    }
}
