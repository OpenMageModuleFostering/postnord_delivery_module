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
 * @class Vconnect_AllInOne_Model_System_Config_Backend_Shipping_License
 */

class Vconnect_AllInOne_Model_System_Config_Backend_Shipping_License extends Mage_Core_Model_Config_Data
{
    public function _beforeSave()
    {
        
            if (strlen($this->getValue()) == '20') {
                try {
                    $client = new Zend_Http_Client('http://api.vconnect.systems/v1/licenses/activate');

                    
//                    $client = new Zend_Http_Client('http://postdk-api.dev/v1/licenses/activate');
                    $client->setParameterGet(array(
                        'license_key'   => $this->getValue(),
                        'email'         => Mage::getStoreConfig('trans_email/ident_general/email'),
                        'ip'            => Mage::helper('core/http')->getRemoteAddr(),
                        'domain'        => Mage::helper('core/http')->getHttpHost(),
                        'client'      => 'Magento ' . Mage::getVersion(),
                    ));
                    $response = $client->request();
                    Mage::log($client->getLastRequest());
                    Mage::log($client->getLastResponse()->asString());
                    $data = json_decode($response->getBody());

                    if ($data) {
                        if ($data->error) {
                            if ($data->error == '2000' || $data->error == '4003' || $data->error == '4001') {
                                Mage::getModel('core/config')->saveConfig('carriers/vconnect_postnord/license_status', 1);
                                Mage::getSingleton('adminhtml/session')->addSuccess('PostNord: Validation success');
                            } else {
                                Mage::getModel('core/config')->saveConfig('carriers/vconnect_postnord/license_status', 0);
                                Mage::getSingleton('adminhtml/session')->addError("PostNord: $data->description");
                            }
                        } else {
                            Mage::getModel('core/config')->saveConfig('carriers/vconnect_postnord/license_status', 1);
                            Mage::getSingleton('adminhtml/session')->addSuccess('PostNord: Validation success');
                        }
                    } else {
                        Mage::getSingleton('adminhtml/session')->addError('PostNord: System error');
                    }
                }  catch(Exception $e) {
                    Mage::getSingleton('adminhtml/session')->addError('PostNord: System error');
                }
            } else {
                Mage::getModel('core/config')->saveConfig('carriers/vconnect_postnord/license_status', 0);
                Mage::getSingleton('adminhtml/session')->addError('PostNord: Invalid license key');
            }
    }
}