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
 * @class Vconnect_AllInOne_Helper_Data
 */
class Vconnect_AllInOne_Helper_Data extends Mage_Core_Helper_Abstract
{
    /**
     *
     * @var array 
     */
    protected $_rates;
    
    /**
     * Common log for module
     * @param mixed $msg
     * @param int $type
     * @return Vconnect_AllInOne_Helper_Data 
     */
    public function log( $msg, $type = Zend_Log::DEBUG){
        Mage::log($msg, $type, 'all_in_one.log');
        return $this;
    }
    
    /**
     * Whethere a country code is scandianvian one
     * $param $countryCode 2 chars iso code
     * @return bool
     */
    public function isScandinavianCountry($countryCode)
    {
        return in_array($countryCode, $this->getScandinavianCountries());
    }
    
    /**
     * Get scandinavian countries
     * @return array
     */
    public function getScandinavianCountries()
    {
        return Mage::getModel('vc_aio/system_config_source_carrier_countries')->toArray();
    }
    
    /**
     * 
     * @param string $countryCode 2 chars iso code
     * @return bool
     */
    public function isEuCountry($countryCode)
    {
        return in_array(strtolower($countryCode), $this->getEuCountries());
    }
    
    /**
     * Return array of EU countries 2 chars code
     * @return array
     */
    public function getEuCountries()
    {
        $eu_countries = Mage::getStoreConfig('general/country/eu_countries');
        return explode(',',  strtolower($eu_countries));
       
    }
    
    /**
     * 
     * @return array of all countries
     */
    public function getAllCountries()
    {
        return array_map( function($item){
            return strtolower($item['value']);
        },Mage::getModel('directory/country')->getResourceCollection()
                                                  ->loadByStore()
                                                  ->toOptionArray(false));
    }
    
    /**
     * 
     * @param Mage_Sales_Model_Quote $quote
     * @return array
     */
    public function getRatesForQuote(Mage_Sales_Model_Quote $quote)
    {
        if(!$this->_rates){
            $this->_rates = array();
            $_rates  = $quote->getShippingAddress()->getShippingRatesCollection()->toArray();
            $_rates = array_slice($_rates['items'],count($_rates['items']) - $_rates['totalRecords']);
            foreach ($_rates as $rate){
                if(stripos($rate['code'],'vconnect_postnord') === false){
                    continue;
                }
                $config = Mage::helper('core')->jsonDecode($rate['vc_method_data']);
                
                $rate['sort_order'] = Mage::getStoreConfig("carriers/{$config['system_path']}/sort_order");
                $rate['price_formated'] = $this->getPriceFromated($quote,$rate['price']);
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
     * @param Mage_Sales_Model_Quote $quote
     * @param deciaml $price
     * @return string
     */
    public function getPriceFromated( Mage_Sales_Model_Quote $quote, $price )
    {
        $displayTax = Mage::helper('tax')->displayShippingPriceIncludingTax();
        $_price = Mage::helper('tax')->getShippingPrice($price,$displayTax,$quote->getShippingAddress());
        return $quote->getStore()->convertPrice($_price, true);
    }
    
    /**
     * 
     * @param Mage_Sales_Model_Quote $quote
     * @param string $code
     * @return array|null
     */
    public function getRateDetailsByMethodCode( Mage_Sales_Model_Quote $quote, $code)
    {
        
        $rates = $this->getRatesForQuote($quote);
        $result = array_filter( $rates,function($item) use($code){
            return $code == $item['code'];
        });
        return array_shift($result);
    }
    
    /**
     * 
     * @param string $code
     * @return string json string
     */
    public function getFelxDeliveryByMethodCode( $code)
    {
        $content = $this->getRateDetailsByMethodCode(Mage::getSingleton('checkout/session')->getQuote(), $code);
        if(!isset($content['vc_method_data'])){
            return array();
        }
        $data = Mage::helper('core')->jsonDecode($content['vc_method_data']);
       
        return isset($data['delivery']['Flex Delivery'])?$data['delivery']['Flex Delivery']:array();
    }

    /**
     * 
     * @param string $system_path
     * @return string array
     */
    public function getAdditionalFeeData($system_path)
    {
        $data = array();

        if (Mage::getStoreConfigFlag("carriers/{$system_path}/additional_fee_active")) {
            $data = array(
                'label'            => Mage::getStoreConfig("carriers/{$system_path}/additional_fee_label"),
                'label_with_price' => Mage::getStoreConfig("carriers/{$system_path}/additional_fee_label") . ' +' . Mage::getSingleton('checkout/session')->getQuote()->getStore()->convertPrice((float)Mage::getStoreConfig("carriers/{$system_path}/additional_fee_amount"), true, false),
                'price_base'       => (float)Mage::getStoreConfig("carriers/{$system_path}/additional_fee_amount"),
                'price'            => Mage::getSingleton('checkout/session')->getQuote()->getStore()->convertPrice((float)Mage::getStoreConfig("carriers/{$system_path}/additional_fee_amount"), false, false),
            );
        }

        return $data;
    }
    
    
    
}
