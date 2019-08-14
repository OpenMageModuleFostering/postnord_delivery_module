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
 * @class Vconnect_AllInOne_Model_Carrier_Postnord
 */
class Vconnect_AllInOne_Model_Carrier_Postnord
    extends Mage_Shipping_Model_Carrier_Abstract
    implements Mage_Shipping_Model_Carrier_Interface
{

    /**
     * code name
     *
     * @var string
     */
    protected $_code = 'vconnect_postnord';
    
    /**
     * boolean isFixed
     *
     * @var boolean
     */
    protected $_isFixed = true;

    /**
     * 
     * @return Vconnect_AllInOne_Helper_Data
     */
    public function helper()
    {
        return Mage::helper('vc_aio');
    }

    /**
     * Collect and get rates
     *
     * @param Mage_Shipping_Model_Rate_Request $request
     * @return Mage_Shipping_Model_Rate_Result
     */
    public function collectRates(Mage_Shipping_Model_Rate_Request $request)
    {
        if (!$this->getConfigFlag('active') || !$this->getConfigFlag('license_status')) {
            return false;
        }
        if(!$request->getCountryId()){
            return Mage::log('PostNord: No origin country');
        }
        // exclude Virtual products price from Package value if pre-configured
        if (!$this->getConfigFlag('include_virtual_price') && $request->getAllItems()) {
            foreach ($request->getAllItems() as $item) {
                if ($item->getParentItem()) {
                    continue;
                }
                if ($item->getHasChildren() && $item->isShipSeparately()) {
                    foreach ($item->getChildren() as $child) {
                        if ($child->getProduct()->isVirtual()) {
                            $request->setPackageValue($request->getPackageValueWithDiscount() - $child->getBaseRowTotal());
                        }
                    }
                } elseif ($item->getProduct()->isVirtual()) {
                    $request->setPackageValue($request->getPackageValueWithDiscount() - $item->getBaseRowTotal());
                }
            }
        }

        // Free shipping by qty
        $freeQty = 0;
        if ($request->getAllItems()) {
            foreach ($request->getAllItems() as $item) {
                if ($item->getProduct()->isVirtual() || $item->getParentItem()) {
                    continue;
                }

                if ($item->getHasChildren() && $item->isShipSeparately()) {
                    foreach ($item->getChildren() as $child) {
                        if ($child->getFreeShipping() && !$child->getProduct()->isVirtual()) {
                            $freeQty += $item->getQty() * ($child->getQty() - (is_numeric($child->getFreeShipping()) ? $child->getFreeShipping() : 0));
                        }
                    }
                } elseif ($item->getFreeShipping()) {
                    $freeQty += ($item->getQty() - (is_numeric($item->getFreeShipping()) ? $item->getFreeShipping() : 0));
                }
            }
        }
        
         // Package weight and qty free shipping
        $oldWeight = $request->getPackageWeight();
        $oldQty = $request->getPackageQty();

        $request->setPackageWeight($request->getFreeMethodWeight());
        $request->setPackageQty($oldQty - $freeQty);

        $request->setPackageWeight($oldWeight);
        $request->setPackageQty($oldQty);
        
        $result = Mage::getModel('shipping/rate_result');
        /* @var $result  Mage_Shipping_Model_Rate_Result */
        $methods = new Varien_Data_Collection();
        Mage::dispatchEvent('vconnect_postnord_collect_shipping_methods',array(
            'request'       => $request,
            'methods'       => $methods,
        ));
        if(!$methods->count()){
            return false;
        }

        $items = $methods->getItems();
        usort($items, function($a,$b){
            if (isset($a['system_path']) && isset($b['system_path'])) {
                $a['sort_order'] = (int)Mage::getStoreConfig("carriers/{$a['system_path']}/sort_order");
                $b['sort_order'] = (int)Mage::getStoreConfig("carriers/{$b['system_path']}/sort_order");
                if ($a['sort_order'] == $b['sort_order']) {
                    return 0;
                }
                return ($a['sort_order'] < $b['sort_order']) ? -1 : 1;
            } else {
                return 0;
            }
        });

        foreach ( $items as $_method ){
            $method = $this->_createShippingMethodByCode($request,$freeQty,$_method );
            if( !$method ) {
                continue;
            }
            $result->append($method);
        }
        
        return $result;
    }
    /**
     * Get price rate for order with specific weight and subtotal
     * @param float $orderPrice
     * @param float $orderWeight
     * @param Varien_Object $config method configuration
     * @return float
     */
    public function getRate($orderPrice, $orderWeight, Varien_Object $config ) 
    { 
        $code = $config->getSystemPath();
        Mage::log("destination coutnry {$config->getDestCountry()}");
        if($config->getMultiprices()){
            $ratePath = sprintf('carriers/%s/price_%s',$code, $config->getDestCountry());
        }else{
            $ratePath = sprintf('carriers/%s/%s',$code, $config->getPriceCode());
        }
        
        $result = Mage::getStoreConfig($ratePath);
        if(!$result){
            Mage::log("no rate for code $code and path $ratePath" );
            
            return FALSE;
        }
        $pickupShippingRates = unserialize($result); 
        if (is_array($pickupShippingRates) && !empty($pickupShippingRates)) {
            foreach ($pickupShippingRates as $pickupShippingRate) {
                if( (float)$pickupShippingRate['orderminprice'] <= (float)$orderPrice
                        && ( (float)$pickupShippingRate['ordermaxprice'] >= (float)$orderPrice || (float)$pickupShippingRate['ordermaxprice'] == 0)
                        && (float)$pickupShippingRate['orderminweight'] <= (float)$orderWeight
                        && ( (float)$pickupShippingRate['ordermaxweight'] >= (float)$orderWeight || (float)$pickupShippingRate['ordermaxweight'] == 0)
                        ) {
                    return $pickupShippingRate['price'];
                }
            }
        }
    }
  
    /**
     * 
     * @param Mage_Shipping_Model_Rate_Request $request
     * @param string $code
     * @param float $freeQty
     * @param array $data Method data
     * @param Vconnect_AllInOne_Model_Carrier_Postnord $carrier carrier object
     * @return Mage_Shipping_Model_Rate_Result_Method|Bool
     */
    protected function _createShippingMethodByCode(Mage_Shipping_Model_Rate_Request $request,
                                                            $freeQty , Varien_Object $data )
    {
        $code       = $data->getSystemPath();
        $methodCode =  $data->getMethod();
        
        Mage::log( ("$code is active:") . ( Mage::getStoreConfig("carriers/$code/active")?'yes':'no' ) );
        if( !Mage::getStoreConfig("carriers/$code/active") ){
            return false;
        }
        $data->setCountry(strtolower($request->getCountryId()));
        $data->setDestCountry(strtolower($request->getDestCountryId()));
        $data->setDeliveryTime(Mage::getStoreConfig("carriers/$code/transit_time")?:false);
        $rate = $this->getRate($request->getPackageValueWithDiscount(), $request->getPackageWeight(), $data);
        if(!$rate) {
            Mage::log("No price rate for $code");
            return false;
        }
        $method = Mage::getModel('shipping/rate_result_method');
        /* @var $method Mage_Shipping_Model_Rate_Result_Method */
        $method->setCarrier($this->getCarrierCode())->setCarrierTitle($this->getConfigData('title'));
        $method->setVcMethodData($data->toJson());
        
        $method->setMethod($methodCode)->setMethodTitle(Mage::getStoreConfig("carriers/$code/name"));
        
        if ($request->getFreeShipping() === true || ($request->getPackageQty() == $freeQty)) {
            $shippingPrice = 0;
        } else {
            $shippingPrice = $this->getFinalPriceWithHandlingFee($rate);
        }
        
        $method->setPrice($shippingPrice)->setCost($rate);
        
        return $method;
    }
    /**
     * Get allowed shipping methods
     *
     * @return array
     */
    public function getAllowedMethods()
    {
        return array(
            $this->_code . '_private'       => Mage::getStoreConfig('carrier/vconnect_postnord_home/name'),
            $this->_code . '_commercial'    => Mage::getStoreConfig('carrier/vconnect_postnord_business/name'),
            $this->_code . '_pickup'        => Mage::getStoreConfig('carrier/vconnect_postnord_pickup/name'),
            $this->_code . '_mailbox'        => Mage::getStoreConfig('carrier/vconnect_postnord_mailbox/name'),
        );
    }

}
