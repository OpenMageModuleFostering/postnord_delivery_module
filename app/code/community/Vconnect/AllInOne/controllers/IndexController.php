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
 * @class Vconnect_AllInOne_IndexController
 */
class Vconnect_AllInOne_IndexController extends Mage_Core_Controller_Front_Action
{
    public function indexAction()
    {
        $api = Mage::getModel('vc_aio/apiclient');
        /* @var $api Vconnect_AllInOne_Model_ApiClient */
//        if(!$this->getRequest()->isAjax()){
//            
//        }
        $this->getResponse()->setHeader('Content-Type', 'application/json');
        if(!Mage::getStoreConfigFlag('carriers/vconnect_postnord_pickup/active')){
            $this->_redirect('home');
        }
        
        $quote = Mage::getSingleton('checkout/session')->getQuote();
        /* @var $quote Mage_Sales_Model_Quote */
        
        $shippingAddress = $quote->getShippingAddress();
        
        $apiKey = Mage::getStoreConfig('carriers/vconnect_postnord_pickup/api_key');
        $postcode = $this->getRequest()->getQuery('postcode', $shippingAddress->getPostcode());
        $countryId = $this->getRequest()->getQuery('country_id', $shippingAddress->getCountryId());
        $street = $this->getRequest()->getQuery('street', $shippingAddress->getStreetFull());
        $api->setGls(Mage::getStoreConfigFlag('carriers/vconnect_postnord_pickup/gls'));
        
        $response = $api->findPoints($apiKey, $postcode, $countryId, 10,$street);
        $this->getResponse()->setBody(Mage::helper('core')->jsonEncode($response));
        
        
        
    }


    public function transitInformationAction()
    {
        $api = Mage::getModel('vc_aio/apiclient');

        $this->getResponse()->setHeader('Content-Type', 'application/json');
        if(!Mage::getStoreConfigFlag('carriers/vconnect_postnord_pickup/active')){
            $this->_redirect('home');
        }
        
        $quote = Mage::getSingleton('checkout/session')->getQuote();
        /* @var $quote Mage_Sales_Model_Quote */
        
        $shippingAddress = $quote->getShippingAddress();

        $apiKey = Mage::getStoreConfig('carriers/vconnect_postnord_pickup/api_key');
        $postcode = $this->getRequest()->getQuery('postcode', $shippingAddress->getPostcode());
        $countryId = $this->getRequest()->getQuery('country_id', $shippingAddress->getCountryId());
        $serviceCode = trim($this->getRequest()->getPost('service_code', false));

        $storeCountryId = Mage::getStoreConfig('shipping/origin/country_id');
        $storePostcode = Mage::getStoreConfig('shipping/origin/postcode');

        if (empty($storeCountryId) || empty($storePostcode) || empty($countryId) || empty($postcode)) {
            $response = array();
            $response['postnord']['error'] = true;
            $response['postnord']['message'] = 'Invalid transittime input data';
            $this->getResponse()->setBody(Mage::helper('core')->jsonEncode($response));
        } else {
            $dataParams = array(
                'serviceCode'                 => !empty($serviceCode) ? $serviceCode : 32,
                'serviceGroupCode'            => $storeCountryId,
                'fromAddressPostalCode'       => $storePostcode,
                'fromAddressCountryCode'      => $storeCountryId,
                'toAddressPostalCode'         => $postcode,
                'toAddressCountryCode'        => $countryId,
            );

            $response = $api->getTransitTimeInformation($apiKey, $dataParams);
        }

        $this->getResponse()->setBody(Mage::helper('core')->jsonEncode($response));
    }
}