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
 * @class Vconnect_AllInOne_Model_Api
 */
class Vconnect_AllInOne_Model_Apiclient extends Varien_Object
{
    const POSTNORD_API_URL = "https://api2.postnord.com/rest/";
    
    /**
     * 
     * @param string $apiKey
     * @param int $postCode
     * @param string $countryCode
     * @param int $limit
     * @param string $street
     * @param string $locale Allowed values are en, sv, no, da and fi
     * @param bool $gls
     * @return array
     */
    public function findPoints($apiKey,$postCode = 2100, $countryCode='DK',$limit = 10, $street = null, $locale='en') 
    {
        $start = microtime();
        $params = array(
            'apikey'            => $apiKey,
            'countryCode'           => strtoupper($countryCode),
            'postalCode'            => $postCode,
            'streetName'            => $street,
            'numberOfServicePoints' => $limit,
            'locale'                => $locale
        );

        $url = self::POSTNORD_API_URL . 'businesslocation/v1/servicepoint/findNearestByAddress.json';
        $client = new Zend_Http_Client($url);

//        $client->
        $client->setParameterGet($params);
//        $client->setHeaders('Accept', 'application/json');
        $result['postnord'] = array();
        try {
            $response = $client->request();
//            Mage::log($client->getLastRequest());
//            Mage::log($client->getLastResponse()->asString());
            if($response->getStatus() == 200){
                $body = $response->getBody();
                $res = Mage::helper('core')->jsonDecode($body);
                $result['postnord'] = $res;
                
            }else{
                $result['postnord']['error'] = true;
                $result['postnord']['message'] = "API returned error with code {$response->getStatus()}";
            }
        } catch (Exception $ex) {
             $result['postnord']['error'] = true;
             $result['postnord']['message'] = $ex->getMessage();
             
        }
        
        
        if(!$this->getGls() || !class_exists('SoapClient')){
            $result['time'] = microtime() - $start;
            return $result;
        }
        
        $soap_client = new SoapClient("http://www.gls.dk/webservices_v2/wsPakkeshop.asmx?WSDL", array(
                    'encoding' => 'UTF-8',
                    'default_socket_timeout' => 5
                    ));
        
        $result['gls'] = array();
        try
        {
                $shops = $soap_client->SearchNearestParcelShops(array('street' => $street, 'zipcode' => $postCode, 'Amount' => $limit));
                $result['gls'] = (array)$shops->SearchNearestParcelShopsResult->parcelshops->PakkeshopData;
                $result['gls']['error'] = false;
        }
        catch(Exception $e)
        {
                $result['gls']['error'] = true;
                $result['gls']['message'] = $e->getMessage();
                $this->error = __METHOD__ . ' : ' . $e->getMessage();
                
        }		
        $result['time'] = microtime()- $start;
        return $result;
        
    }

    /**
     * 
     * @param string $apiKey
     * @param int $postCode
     * @param string $countryCode
     * @param int $limit
     * @param string $street
     * @param string $locale Allowed values are en, sv, no, da and fi
     * @param bool $gls
     * @return array
     */
    public function getTransitTimeInformation($apiKey, $dataParams = array()) 
    {
        $start = microtime();
        $params = array(
            'apikey'                     => $apiKey,
            'serviceCode'                => $dataParams['serviceCode'],
            'serviceGroupCode'           => strtoupper($dataParams['serviceGroupCode']),
            'fromAddressPostalCode'      => $dataParams['fromAddressPostalCode'],
            'fromAddressCountryCode'     => strtoupper($dataParams['fromAddressCountryCode']),
            'toAddressPostalCode'        => $dataParams['toAddressPostalCode'],
            'toAddressCountryCode'       => strtoupper($dataParams['toAddressCountryCode'])
        );

        $url = self::POSTNORD_API_URL . 'transport/v1/transittime/getTransitTimeInformation.json';
        $client = new Zend_Http_Client($url);

        $client->setParameterGet($params);

        $result['postnord'] = array();

        try {
            $response = $client->request();

            if($response->getStatus() == 200){
                $body = $response->getBody();
                $res = Mage::helper('core')->jsonDecode($body);
                $result['postnord'] = $res;
                
            }else{
                $result['postnord']['error'] = true;
                $result['postnord']['message'] = "API returned error with code {$response->getStatus()}";
            }
        } catch (Exception $ex) {
             $result['postnord']['error'] = true;
             $result['postnord']['message'] = $ex->getMessage();
        }

        $result['time'] = microtime()- $start;
        return $result;
    }
    
}
