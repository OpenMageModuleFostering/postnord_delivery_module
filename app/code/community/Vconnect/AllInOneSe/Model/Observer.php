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
 * @package Vconnect_AllInOneDk
 * @author vConnect
 * @email kontakt@vconnect.dk
 * @class Vconnect_AllInOneSe_Model_Observer
 */
class Vconnect_AllInOneSe_Model_Observer
{
    /**
     * 
     * @return Vconnect_AllInOne_Helper_Data
     */
    public function helper()
    {
        return Mage::helper('vc_aio');
    }

    /**
     * 
     * @param Varien_Event_Observer $observer
     */
    public function collectShippingMethods(Varien_Event_Observer $observer)
    {
        $request = $observer->getEvent()->getRequest();
        /* @var $request  Mage_Shipping_Model_Rate_Request */
       
        if( strtolower($request->getCountryId()) != 'se' )
        {
            return;
        }
         $methods = $observer->getEvent()->getMethods();
        /* @var $methods  Varien_Data_Collection */
       
        
        $destCountryId = strtolower($request->getDestCountryId());
        $shippingMethods = $this->getAvailableMethods();
        
        foreach ($shippingMethods as $data)
        {
            if(in_array($destCountryId, $data['countries']) && !in_array($destCountryId,$data['exclude'])
                    && Mage::getStoreConfigFlag("carriers/{$data['system_path']}/active") ){
                $m = new Varien_Object($data);
                $m->setIdFieldName('method');
                $methods->addItem($m);
            }
            
        }
    }
    
    /**
     * 
     * @return array method configurations
     */
    public function getAvailableMethods()
    {
        return array(
             array(
                'system_path'   => 'vconnect_postnord_mailbox',
                'template'      => 'mailbox',
                'method'        => 'se_valuemail',
                'countries'     => array('se'),
                'exclude'       => array(),
                'arrival'      => array(
                    Mage::getStoreConfig("carriers/vconnect_postnord_mailbox/arrival_option_text") => Mage::getStoreConfig("carriers/vconnect_postnord_mailbox/arrival_option_text")
                ),
                'delivery'      => array(
                    'Nearest pickuplocation' => 'Nearest pickuplocation'
                ),
                'multiprices'   => false,
                'price_code'    => 'price_se',
                'delivery_time' => '2-3 days',
            ),
             array(
                'system_path'   => 'vconnect_postnord_mailbox',
                'template'      => 'mailbox',
                'method'        => 'se_privatehome',
                'countries'     => array('dk'),
                'arrival'      => array(
                    Mage::getStoreConfig("carriers/vconnect_postnord_mailbox/arrival_option_text") => Mage::getStoreConfig("carriers/vconnect_postnord_mailbox/arrival_option_text")
                ),
                'delivery'      => array(),
                'exclude'       => array(),
                'multiprices'   => false,
                'price_code'    => 'price_dk',
                'delivery_time' => '2-3 days',
            ),
            array(
                'system_path'   => 'vconnect_postnord_mailbox',
                'template'      => 'mailbox',
                'method'        => 'se_private',
                'arrival'      => array(
                    Mage::getStoreConfig("carriers/vconnect_postnord_mailbox/arrival_option_text") => Mage::getStoreConfig("carriers/vconnect_postnord_mailbox/arrival_option_text")
                ),
                'delivery'      => array(),
                'countries'     => array('no'),
                'exclude'       => array(),
                'multiprices'   => false,
                'price_code'    => 'price_no',
                'delivery_time' => '2-3 days',
            ),
            array(
                'system_path'   => 'vconnect_postnord_mailbox',
                'template'      => 'mailbox',
                'method'        => 'se_private',
                'arrival'      => array(
                    Mage::getStoreConfig("carriers/vconnect_postnord_mailbox/arrival_option_text") => Mage::getStoreConfig("carriers/vconnect_postnord_mailbox/arrival_option_text")
                ),
                'delivery'      => array(),
                'countries'     => $this->helper()->getEuCountries(),
                'exclude'       => $this->helper()->getScandinavianCountries(),
                'multiprices'   => false,
                'price_code'    => 'price_eu',
                'delivery_time' => '2-3 days',
            ),
             array(
                'system_path'   => 'vconnect_postnord_mailbox',
                'template'      => 'mailbox',
                'method'        => 'se_private',
                'arrival'      => array(
                    Mage::getStoreConfig("carriers/vconnect_postnord_mailbox/arrival_option_text") => Mage::getStoreConfig("carriers/vconnect_postnord_mailbox/arrival_option_text")
                ),
                'delivery'      => array(),
                'countries'     => $this->helper()->getAllCountries(),
                'exclude'       => array_merge($this->helper()->getEuCountries() , $this->helper()->getScandinavianCountries()),
                'multiprices'   => false,
                'price_code'    => 'price_intl',
                 'delivery_time'=> '2-3 days',
            ),
            array(
                'system_path'   => 'vconnect_postnord_home',
                'template'      => 'private',
                'method'        => 'se_privatehome',
                'countries'     => array('se'),
                'exclude'       => array(),
                'arrival'      => array(
                    Mage::getStoreConfig("carriers/vconnect_postnord_home/arrival_option_text") => Mage::getStoreConfig("carriers/vconnect_postnord_home/arrival_option_text")
                ),
                'delivery'      => array(
                    'Med kvittens'=>'Med kvittens',
                    'Utan kvittens (paketet ställs utanför dörren)'=>'Utan kvittens (paketet ställs utanför dörren)',
                ),
                'multiprices'   => false,
                'price_code'    => 'price',
                'delivery_time' => '1-3 days',
            ),
            array(
                'system_path'   => 'vconnect_postnord_pickup',
                'template'      => 'pickup',
                'method'        => 'se_pickup',
                'countries'     => $this->helper()->getScandinavianCountries(),
                'exclude'       => array(),
                'multiprices'   => true,
            ),
            array(
                'system_path'   => 'vconnect_postnord_business',
                'template'      => 'commercial',
                'method'        => 'se_commercial',
                'countries'     => array('se'),
                'exclude'       => array(),
                'multiprices'   => false,
                'price_code'    => 'price',
                'delivery_time' => '1-3 days',
                'arrival'      => array(
                    Mage::getStoreConfig("carriers/vconnect_postnord_business/arrival_option_text") => Mage::getStoreConfig("carriers/vconnect_postnord_business/arrival_option_text")
                ),
                'delivery'      => array(
                    'Personal Delivery'=>'Personal Delivery',
                ),
            ),
            array(
                'system_path'   => 'vconnect_postnord_eu',
                'template'      => 'dpdeu',
                'method'        => 'se_dpdclassic',
                'countries'     => $this->helper()->getEuCountries(),
                'exclude'       => $this->helper()->getScandinavianCountries(),
                'multiprices'   => false,
                'price_code'    => 'price',
            ),
            array(
                'system_path'   => 'vconnect_postnord_intl',
                'template'      => 'dpdinternational',
                'method'        => 'se_dpdclassic',
                'countries'     => $this->helper()->getAllCountries(),
                'exclude'       => array_merge($this->helper()->getEuCountries() , $this->helper()->getScandinavianCountries()),
                'multiprices'   => false,
                'price_code'    => 'price',
            ),
        );
    }
   

}
