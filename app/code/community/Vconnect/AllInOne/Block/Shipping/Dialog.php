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
 * @class Vconnect_AllInOne_Block_Shipping_Dialog
 */

class Vconnect_AllInOne_Block_Shipping_Dialog extends Mage_Checkout_Block_Onepage_Abstract 
{
    /**
     *
     * @var array 
     */
    protected $_rates;
    
    public function __construct() {
        parent::__construct();
        $templateTypePath = Mage::getStoreConfigFlag('carriers/vconnect_postnord/popup')?'aio':'aio_base';
        if (Mage::app()->getStore()->isAdmin()) {
           $templateTypePath = 'aio_base_createorder';
        }
        if (!Mage::getStoreConfigFlag('carriers/vconnect_postnord/popup') && Mage::app()->getRequest()->getControllerModule() == 'Avenla_KlarnaCheckout') {
            $templateTypePath = 'klarnacheckout/aio_base';
        }
        $this->setTemplate("vconnect/$templateTypePath/base.phtml");
        $this->setChild('tab_headers',Mage::app()->getLayout()->createBlock('core/text_list'));
        $this->setChild('tab_bodies',Mage::app()->getLayout()->createBlock('core/text_list'));
        $this->setChild('body_scripts',Mage::app()->getLayout()->createBlock('core/text_list'));
        $this->setCacheLifetime(null);
    }
    
    public function getShippingInfo()
    {
        return $this->getQuote()->getVconnectPostnordData();
    }

    /**
     * 
     * @param Mage_Core_Block_Template $block
     * @return Vconnect_AllInOne_Block_Shipping_Dialog
     */
    public function addTabHeader(Mage_Core_Block_Template $block)
    {
        $this->getChild('tab_headers')->append($block);
        return $this;
    }
    
    /**
     * 
     * @param Mage_Core_Block_Template $block
     * @return Vconnect_AllInOne_Block_Shipping_Dialog
     */
    public function addTabBody(Mage_Core_Block_Template $block)
    {
        $this->getChild('tab_bodies')->append($block);
        return $this;
    }
    
    /**
     * 
     * @param Mage_Core_Block_Template $block
     * @return Vconnect_AllInOne_Block_Shipping_Dialog
     */
    public function addTabScripts(Mage_Core_Block_Template $block)
    {
        $this->getChild('body_scripts')->append($block);
        return $this;
    }
    
    /**
     * 
     * @return bool
     */
    public function isWeatherOn(){
        return Mage::getStoreConfigFlag('carriers/vconnect_postnord/weather') ;
    }
    
    /**
     * 
     * @return string
     */
    public function getWatherApiKey(){
        return Mage::getStoreConfig('carriers/vconnect_postnord/weather_api_key') ;
    }
    
    /**
     * 
     * @return array
     */
    public function getRates()
    {
        if(!$this->_rates){
            $this->_rates = array();
            if (Mage::app()->getStore()->isAdmin()) {
               $_rates = Mage::getSingleton('adminhtml/session_quote')->getQuote()->getShippingAddress()->getShippingRatesCollection()->toArray();
            } else {
               $_rates = $this->getQuote()->getShippingAddress()->getShippingRatesCollection()->toArray();
            }

            $_rates = array_slice($_rates['items'],count($_rates['items']) - $_rates['totalRecords']);
            foreach ($_rates as $rate){
                if(stripos($rate['code'],'vconnect_postnord') === false){
                    continue;
                }
                $config = Mage::helper('core')->jsonDecode($rate['vc_method_data']);
                
                $rate['sort_order'] = Mage::getStoreConfig("carriers/{$config['system_path']}/sort_order");
                $rate['price_formated'] = $this->_getPriceFromated($rate['price']);
                $this->_rates[$rate['code']] = $rate;
            }
            usort($this->_rates, function($a,$b){
                return (int)$a['sort_order'] - (int)$b['sort_order'];
            });
        }
        return $this->_rates;
    }
    
    /**
     * 
     * @param deciaml $price
     * @return string
     */
    protected function _getPriceFromated($price)
    {
        return $this->getQuote()->getStore()->convertPrice(
            Mage::helper('tax')->getShippingPrice(
                $price,
                $this->helper('tax')->displayShippingPriceIncludingTax(),
                $this->getQuote()->getShippingAddress()), true);
    }
    
}

