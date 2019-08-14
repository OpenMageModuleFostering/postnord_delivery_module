<?php

class Vconnect_AllInOneDk_Model_Carrier_SmartDelivery
    extends Mage_Shipping_Model_Carrier_Abstract
    implements Mage_Shipping_Model_Carrier_Interface
{

    /**
     * code name
     *
     * @var string
     */
    protected $_code = 'vconnect_pdkalpha';

    /**
     * boolean isFixed
     *
     * @var boolean
     */
    protected $_isFixed = true;

    /**d
     * Default condition name
     *
     * @var string
     */
    protected $_default_condition_name = 'package_weight';

    /**
     * Condition names
     *
     * @var array
     */
    protected $_conditionNames = array();

    /*
     * Constructor
     */
    public function __construct()
    {
        parent::__construct();
        foreach ($this->getCode('condition_name') as $k=>$v) {
            $this->_conditionNames[] = $k;
        }
    }

    /**
     * Collect and get rates
     *
     * @param Mage_Shipping_Model_Rate_Request $request
     * @return Mage_Shipping_Model_Rate_Result
     */
    public function collectRates(Mage_Shipping_Model_Rate_Request $request)
    {
        if (!$this->getConfigFlag('active')) {
            return false;
        }
        $log = array();
        $dataToLog = $request->getData();
        foreach ($dataToLog as $key=>$value){
            if(is_object($value)){
               $log[$key] = get_class($value) ;
            }
            elseif(is_array($value)){
                $log[$key] = array_keys($value) ;
            }
            else{
                $log[$key] = $value ;
            }
        }
//        Mage::log($log);

        // exclude Virtual products price from Package value if pre-configured
        if (!$this->getConfigFlag('include_virtual_price') && $request->getAllItems()) {
            foreach ($request->getAllItems() as $item) {
                if ($item->getParentItem()) {
                    continue;
                }
                if ($item->getHasChildren() && $item->isShipSeparately()) {
                    foreach ($item->getChildren() as $child) {
                        if ($child->getProduct()->isVirtual()) {
                            $request->setPackageValue($request->getPackageValue() - $child->getBaseRowTotal());
                        }
                    }
                } elseif ($item->getProduct()->isVirtual()) {
                    $request->setPackageValue($request->getPackageValue() - $item->getBaseRowTotal());
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

        //if (!$request->getConditionName()) {
            $request->setConditionName($this->getConfigData('condition_name') ? $this->getConfigData('condition_name') : $this->_default_condition_name);
        //}

         // Package weight and qty free shipping
        $oldWeight = $request->getPackageWeight();
        $oldQty = $request->getPackageQty();

        $request->setPackageWeight($request->getFreeMethodWeight());
        $request->setPackageQty($oldQty - $freeQty);

        $result = Mage::getModel('shipping/rate_result');
        

        $request->setPackageWeight($oldWeight);
        $request->setPackageQty($oldQty);

            
        foreach ($this->getAllowedMethods() as $code=>$methodName){
            $rate = $this->getRate($code);
            if (!empty($rate) && $rate['price'] >= 0){
                $method = Mage::getModel('shipping/rate_result_method');

                $method->setCarrier($this->_code);
                $method->setCarrierTitle($this->getConfigData('title'));

                $method->setMethod($code);
                $method->setMethodTitle($methodName);

                if ($request->getFreeShipping() === true || ($request->getPackageQty() == $freeQty)) {
                    $shippingPrice = 0;
                } else {
                    $shippingPrice = $this->getFinalPriceWithHandlingFee($rate['price']);
                }

                $method->setPrice($shippingPrice);
                $method->setCost($rate['cost']);

                $result->append($method);
            }

        }
            
            

        return $result;
    }

    public function getRate($code)
    {
        $rates = $this->getPrices();
        if(isset($rates[$code])){
            return array(
                'price' => $rates[$code],
                'cost'  => 0
            );
        }
        return array();
    }
    protected function getPrices(){
        $_prices = unserialize($this->getConfigData('prices'));
        $prices['green_1'] =   $_prices[1];
        $prices['green_2'] =   $_prices[2];
        $prices['green_4'] =   $_prices[3];
        $prices['green_8'] =   $_prices[4];
        $prices['yellow_1'] =   $_prices[6];
        $prices['yellow_2'] =   $_prices[7];
        $prices['yellow_4'] =   $_prices[8];
        $prices['yellow_8'] =   $_prices[9];
        $prices['red_1'] =   $_prices[11];
        $prices['red_2'] =   $_prices[12];
        $prices['red_4'] =   $_prices[13];
        $prices['red_8'] =   $_prices[14];
        return $prices;
    }

    public function getCode($type, $code='')
    {
        $codes = array(

            'condition_name'=>array(
                'package_weight' => Mage::helper('shipping')->__('Weight vs. Destination'),
                'package_value'  => Mage::helper('shipping')->__('Price vs. Destination'),
                'package_qty'    => Mage::helper('shipping')->__('# of Items vs. Destination'),
            ),

            'condition_name_short'=>array(
                'package_weight' => Mage::helper('shipping')->__('Weight (and above)'),
                'package_value'  => Mage::helper('shipping')->__('Order Subtotal (and above)'),
                'package_qty'    => Mage::helper('shipping')->__('# of Items (and above)'),
            ),

        );

        if (!isset($codes[$type])) {
            throw Mage::exception('Mage_Shipping', Mage::helper('shipping')->__('Invalid Table Rate code type: %s', $type));
        }

        if (''===$code) {
            return $codes[$type];
        }

        if (!isset($codes[$type][$code])) {
            throw Mage::exception('Mage_Shipping', Mage::helper('shipping')->__('Invalid Table Rate code for type %s: %s', $type, $code));
        }
        return $codes[$type][$code];
    }

    /**
     * Get allowed shipping methods
     *
     * @return array
     */
    public function getAllowedMethods()
    {
        $prices = $this->getPrices();
        $return = array();
        foreach ($prices as $key=>$price){
            $parts = explode('_', $key);
            $title = strtoupper($parts[0]);
            $return[$key] = "$title {$parts[1]} hr/s";
        }
        return $return;
    }

}
