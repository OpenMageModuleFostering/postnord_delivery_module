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
 * @class Vconnect_AllInOne_Model_Observer
 */
class Vconnect_AllInOne_Model_Observer
{
    /**
     * 
     * @param Varien_Event_Observer $observer
     */
    public function injectPopupCode(Varien_Event_Observer $observer) 
    {
        $block = $observer->getBlock();
        if ($block instanceof Mage_Checkout_Block_Onepage_Shipping_Method_Available || $block instanceof Vconnect_AllInOne_Block_Adminhtml_Sales_Order_Create_Shipping_Method_Form || (Mage::app()->getRequest()->getControllerModule() == 'Avenla_KlarnaCheckout' && $block instanceof Mage_Checkout_Block_Cart_Shipping)) {
            $transport = $observer->getTransport();
            $html = $transport->getHtml();
            if( stripos($html, 'vconnect_') !== false ){
                $_block = Mage::app()->getLayout()->createBlock('vc_aio/shipping_dialog');
                $color = strtolower(Mage::getStoreConfig('shipping/origin/country_id'))=='dk'?'red':'blue';
                $_block->setColor($color);
                /* @var $_block Vconnect_AllInOne_Block_Shipping_Dialog */
                Mage::dispatchEvent('vconnect_shipping_methods_ui_collect', array(
                    'block'=>$_block));
                $newHtml = $_block->toHtml();
                $html .= $newHtml;
            }
            $transport->setHtml($html);
        }
    }
    
    /**
     * 
     * @param Varien_Event_Observer $observer
     */
    public function shippingMethodsUiCollect(Varien_Event_Observer $observer) {
        $block = $observer->getEvent()->getBlock();
        /* @var $block Vconnect_AllInOne_Block_Shipping_Dialog */
        
        if(!$block instanceof Vconnect_AllInOne_Block_Shipping_Dialog){
            return;
        }
        $rates = $block->getRates();
        foreach ($rates as $data){
            if(!isset($data['vc_method_data']) || !$data['vc_method_data']){
                continue;
            }
            $_data = json_decode($data['vc_method_data'],true);
            $countryCode    = strtolower($_data['country']);
            $methodCode     = $_data['method'];
            $templatePath   = $_data['template'];
            
            foreach (array('tabHeader','tabScripts') as $template){
                $b = Mage::app()->getLayout()->createBlock('core/template');
                $b->setData($data);
                $b->setConfig($_data);
                $templateTypePath = Mage::getStoreConfigFlag('carriers/vconnect_postnord/popup')?'aio':'aio_base';
                if (Mage::app()->getStore()->isAdmin()) {
                   $templateTypePath = 'aio_base_createorder';
                }
                if (!Mage::getStoreConfigFlag('carriers/vconnect_postnord/popup') && Mage::app()->getRequest()->getControllerModule() == 'Avenla_KlarnaCheckout') {
                    $templateTypePath = 'klarnacheckout/aio_base';
                }
                $b->setTemplate("vconnect/$templateTypePath/$templatePath/$template.phtml");
                $templateFile = Mage::getBaseDir('design') . DS . $b->getTemplateFile();
                if (! file_exists($templateFile)) {
                    Mage::log("Template file doesn't exists :$countryCode - $methodCode - $templateFile");
                    continue;
                }
                $action = sprintf('add%s',  ucfirst($template));
                if(is_callable(array($block,$action))){
                    $block->$action($b);
                }
                
            }
        }
    }

    /**
    * @param Varien_Event_Observer $observer
    */
    public function saveQuoteOnestepcheckout(Varien_Event_Observer $observer) 
    {
        $post = Mage::app()->getRequest()->getPost();

        // Fix for KlarnaCheckout
        if ($observer->getEvent()->hasQuote() && Mage::app()->getRequest()->getControllerModule() == 'Avenla_KlarnaCheckout' && !empty($post) && !empty($post['payment_ain_vconnect_postnord_data']) && !empty($post['estimate_method'])) {
            $quote = $observer->getEvent()->getQuote();

            if (strpos($post['estimate_method'], 'vconnect_postnord_') === false || empty($post['payment_ain_vconnect_postnord_data'])) {
                $quote->setData('vconnect_postnord_data', null);
                $quote->save();
                return;
            }

            $data = $post['payment_ain_vconnect_postnord_data'];
            $rate = Mage::helper('vc_aio')->getRateDetailsByMethodCode($quote,$post['estimate_method']);
            if (isset($rate['vc_method_data']) && !empty($data)) {
                $_data = json_decode($rate['vc_method_data'],true);
                if(isset($_data['system_path'])) {
                    $data = json_decode($data,true);

                    $additional_fee_data = Mage::helper('vc_aio')->getAdditionalFeeData($_data['system_path']);
                    if ($additional_fee_data['label_with_price'] == $data['arrival']) {
                        $data['additional_fee_amount'] = $additional_fee_data['price_base'];
                        $data['additional_fee_label'] = $additional_fee_data['label'];
                    }

                    $data = json_encode($data);
                }
            }

            if ($rate && !empty($rate['method_title'])) {
                $data = json_decode($data,true);
                $data['description'] = $rate['method_title'] . ': ' . $data['description'];
                $data = json_encode($data);
            }

            Mage::getSingleton('checkout/session')->setVconnectPostnordSessionData(json_decode($data,true));

            $quote->setData('vconnect_postnord_data', $data);
            $quote->save();

        } // Fix for Onestepcheckout
        else if (!(!$observer->getEvent()->hasQuote() || Mage::app()->getRequest()->getModuleName() != 'onestepcheckout' || empty($post) || !empty($post['vconnect_postnord_data']) || empty($post['shipping_method']))) {
            $quote = $observer->getEvent()->getQuote();

            if (strpos($post['shipping_method'], 'vconnect_postnord_') === false || empty($post['payment_ain_vconnect_postnord_data'])) {
                $quote->setData('vconnect_postnord_data', null);
                $quote->save();
                return;
            }

            $arrival = Mage::app()->getRequest()->getPost($post['shipping_method'].'_arrival');
            $deliver = Mage::app()->getRequest()->getPost($post['shipping_method'].'_delivery');
            $otherTxt = Mage::app()->getRequest()->getPost($post['shipping_method'].'_other');
            if ($arrival || $deliver || $otherTxt) {
                return;
            }

            $data = $post['payment_ain_vconnect_postnord_data'];
            $rate = Mage::helper('vc_aio')->getRateDetailsByMethodCode($quote,$post['shipping_method']);
            if (isset($rate['vc_method_data']) && !empty($data)) {
                $_data = json_decode($rate['vc_method_data'],true);
                if(isset($_data['system_path'])) {
                    $data = json_decode($data,true);

                    $additional_fee_data = Mage::helper('vc_aio')->getAdditionalFeeData($_data['system_path']);
                    if ($additional_fee_data['label_with_price'] == $data['arrival']) {
                        $data['additional_fee_amount'] = $additional_fee_data['price_base'];
                        $data['additional_fee_label'] = $additional_fee_data['label'];
                    }

                    $data = json_encode($data);
                }
            }

            if ($rate && !empty($rate['method_title'])) {
                $data = json_decode($data,true);
                $data['description'] = $rate['method_title'] . ': ' . $data['description'];
                $data = json_encode($data);
            }

            $quote->setData('vconnect_postnord_data', $data);
            $quote->save();
        }
    }

    /**
    * @param Varien_Event_Observer $observer
    */
    public function saveShipping(Varien_Event_Observer $observer) 
    {

        if (!$observer->getEvent()->hasQuote()) {
            return;
        }

        $quote = $observer->getEvent()->getQuote();
        /* @var $quote Mage_Sales_Model_Quote */
        
        Mage::log($observer->getRequest()->getPost());
        $method = $observer->getRequest()->getPost('shipping_method');
        if(strpos($method, 'vconnect_postnord_') === false){
            $quote->setData('vconnect_postnord_data', null);
            $quote->save();
            return;
        }
        if(!Mage::getStoreConfigFlag('carriers/vconnect_postnord/active',$quote->getStoreId())){
            $quote->setData('vconnect_postnord_data', null);
            $quote->save();
            return;
        }
        
        if ($observer->getRequest()->getPost('vconnect_postnord_data', false)) {
            $data = $observer->getRequest()
                    ->getPost('vconnect_postnord_data');

            $rate = Mage::helper('vc_aio')->getRateDetailsByMethodCode($quote,$method);
            if (isset($rate['vc_method_data']) && !empty($data)) {
                $_data = json_decode($rate['vc_method_data'],true);
                if(isset($_data['system_path'])) {
                    $data = json_decode($data,true);

                    $additional_fee_data = Mage::helper('vc_aio')->getAdditionalFeeData($_data['system_path']);
                    if ($additional_fee_data['label_with_price'] == $data['arrival']) {
                        $data['additional_fee_amount'] = $additional_fee_data['price_base'];
                        $data['additional_fee_label'] = $additional_fee_data['label'];
                    }

                    $data = json_encode($data);
                }
            }

            if ($rate && !empty($rate['method_title'])) {
                $data = json_decode($data,true);
                $data['description'] = $rate['method_title'] . ': ' . $data['description'];
                $data = json_encode($data);
            }

            $quote->setData('vconnect_postnord_data', $data);
            $quote->save();
        }
        else {
            $rate = Mage::helper('vc_aio')->getRateDetailsByMethodCode($quote,$method);
            
          
            $arrival = $observer->getRequest()->getPost($method.'_arrival');
            $deliver = $observer->getRequest()->getPost($method.'_delivery');
            $otherTxt = $observer->getRequest()->getPost($method.'_other');

            if ($arrival) {
                if (isset($rate['vc_method_data'])) {
                    $_data = json_decode($rate['vc_method_data'],true);
                    if(isset($_data['system_path'])) {
                        $additional_fee_data = Mage::helper('vc_aio')->getAdditionalFeeData($_data['system_path']);
                        if ($additional_fee_data['label_with_price'] == $arrival) {
                            $additional_fee_amount = $additional_fee_data['price_base'];
                            $additional_fee_label = $additional_fee_data['label'];
                        }
                    }
                }
            }

            $flexDelivery = false;
            if(isset($rate['vc_method_data'])){
                  $_data = json_decode($rate['vc_method_data'],true);
                 
                  if(isset($_data['delivery']['Flex Delivery']) 
                        && in_array($deliver, $_data['delivery']['Flex Delivery'])){
                      $flexDelivery = $otherTxt?:$deliver;
                  }
            }
            
            $description = sprintf('%s:%s%s%s',$rate['method_title'],$arrival,$deliver?" / $deliver":'',$otherTxt?" - $otherTxt":'');
            $data = array(
                'code'          => $method,
                'carrier'       => $rate['carrier_title'],
                'arrival'       => $arrival,
                'delivery'      => $deliver,
                'flexdelivery'  => $flexDelivery,
                'description'   => $description,
            );

            if (isset($additional_fee_amount) && isset($additional_fee_label)) {
                $data['additional_fee_amount'] = $additional_fee_amount;
                $data['additional_fee_label'] = $additional_fee_label;
            }

            if ($observer->getRequest()->getPost('vconnect_postnord_environmental_fee')) {
                $data['environmental_fee'] = 1;
            }

            $quote->setData('vconnect_postnord_data', Mage::helper('core')->jsonEncode($data));
            $quote->save();
        }
    }

    /**
    * @param Varien_Event_Observer $observer
    */
    public function saveAdminShipping(Varien_Event_Observer $observer) 
    {
        $model = $observer->getEvent()->getOrderCreateModel();

        if (!$model->getQuote()->getId()) {
            return;
        }

        $quote = $model->getQuote();
        /* @var $quote Mage_Sales_Model_Quote */

        $post_order = $observer->getRequest('order');

        if (empty($post_order['shipping_method'])) {
            return;
        }

        $method = $post_order['shipping_method'];

        if(strpos($method, 'vconnect_postnord_') === false){
            $quote->setData('vconnect_postnord_data', null);
            $quote->save();
            return;
        }
        if(!Mage::getStoreConfigFlag('carriers/vconnect_postnord/active',$quote->getStoreId())){
            $quote->setData('vconnect_postnord_data', null);
            $quote->save();
            return;
        }
        
        if ($observer->getRequest('payment_ain_vconnect_postnord_data', false)) {
            $data = $observer->getRequest('payment_ain_vconnect_postnord_data');

            $rate = Mage::helper('vc_aio')->getRateDetailsByMethodCode($quote,$method);
            if (isset($rate['vc_method_data']) && !empty($data)) {
                $_data = json_decode($rate['vc_method_data'],true);
                if(isset($_data['system_path'])) {
                    $data = json_decode($data,true);

                    $additional_fee_data = Mage::helper('vc_aio')->getAdditionalFeeData($_data['system_path']);
                    if ($additional_fee_data['label_with_price'] == $data['arrival']) {
                        $data['additional_fee_amount'] = $additional_fee_data['price_base'];
                        $data['additional_fee_label'] = $additional_fee_data['label'];
                    }

                    $data = json_encode($data);
                }
            }

            if ($rate && !empty($rate['method_title'])) {
                $data = json_decode($data,true);
                $data['description'] = $rate['method_title'] . ': ' . $data['description'];
                $data = json_encode($data);
            }

            if ($observer->getRequest('vconnect_postnord_environmental_fee')) {
                $data = json_decode($data,true);
                $data['environmental_fee'] = 1;
                $data = json_encode($data);
            }

            $quote->setData('vconnect_postnord_data', $data);
            $quote->save();
        }
        else {
            $rate = Mage::helper('vc_aio')->getRateDetailsByMethodCode($quote,$method);
            
          
            $arrival = $observer->getRequest($method.'_arrival');
            $deliver = $observer->getRequest($method.'_delivery');
            $otherTxt = $observer->getRequest($method.'_other');

            if ($arrival) {
                if (isset($rate['vc_method_data'])) {
                    $_data = json_decode($rate['vc_method_data'],true);
                    if(isset($_data['system_path'])) {
                        $additional_fee_data = Mage::helper('vc_aio')->getAdditionalFeeData($_data['system_path']);
                        if ($additional_fee_data['label_with_price'] == $arrival) {
                            $additional_fee_amount = $additional_fee_data['price_base'];
                            $additional_fee_label = $additional_fee_data['label'];
                        }
                    }
                }
            }

            $flexDelivery = false;
            if(isset($rate['vc_method_data'])){
                  $_data = json_decode($rate['vc_method_data'],true);
                 
                  if(isset($_data['delivery']['Flex Delivery']) 
                        && in_array($deliver, $_data['delivery']['Flex Delivery'])){
                      $flexDelivery = $otherTxt?:$deliver;
                  }
            }
            
            $description = sprintf('%s:%s%s%s',$rate['method_title'],$arrival,$deliver?" / $deliver":'',$otherTxt?" - $otherTxt":'');
            $data = array(
                'code'          => $method,
                'carrier'       => $rate['carrier_title'],
                'arrival'       => $arrival,
                'delivery'      => $deliver,
                'flexdelivery'  => $flexDelivery,
                'description'   => $description,
            );

            if (isset($additional_fee_amount) && isset($additional_fee_label)) {
                $data['additional_fee_amount'] = $additional_fee_amount;
                $data['additional_fee_label'] = $additional_fee_label;
            }

            if ($observer->getRequest('vconnect_postnord_environmental_fee')) {
                $data['environmental_fee'] = 1;
            }

            $quote->setData('vconnect_postnord_data', Mage::helper('core')->jsonEncode($data));
            $quote->save();
        }
    }
    /**
     * 
     * @param Varien_Event_Observer $observer
     * @return Vconnect_Pdkalpha_Model_Observer
     */
    public function salesOrderPlaceAfter(Varien_Event_Observer $observer) 
    {
        $order = $observer->getEvent()->getOrder();
        /* @var $order Mage_Sales_Model_Order */
        if(!Mage::getStoreConfigFlag('carriers/vconnect_postnord/active',$order->getStoreId())){
            return;
        }
        
        if (stripos($order->getShippingMethod(), 'vconnect_postnord_') === false || !$order->getVconnectPostnordData()) {
            return $this;
        }
        
        $data = Mage::helper('core')->jsonDecode($order->getVconnectPostnordData(),true);
        $order->setShippingDescription("{$data['carrier']} - {$data['description']}");
    }

    public function paypalPrepareLineItems($observer)
    {
        $paypal = $observer->getEvent()->getPaypalCart();
        if (!$paypal->getSalesEntity()->getVconnectPostnordData()) {
            return $this;
        }

        $data = Mage::helper('core')->jsonDecode($paypal->getSalesEntity()->getVconnectPostnordData(),true);
        
        // Environmental fee
        if (!empty($data['environmental_fee'])) {
            $environmental_fee = 10;
            $paypal->addItem(Mage::helper('vc_aio')->__('Environmental Fee'), 1, $environmental_fee);
        }

        // Additional fee
        if (!empty($data['additional_fee_amount'])) {
            $additional_fee_amount = $data['additional_fee_amount'];
            $additional_fee_label = $data['additional_fee_label'];
            $paypal->addItem($additional_fee_label, 1, $additional_fee_amount);
        }

        return $this;
    }

    public function addPaymentFeeTotal($observer)
    {
        $block = $observer->getEvent()->getBlock();

        if (!in_array($block->getNameInLayout(), array('order_totals', 'invoice_totals', 'creditmemo_totals'))) {
            return;
        }

        $renderer = $block->getLayout()->createBlock('vc_aio/adminhtml_total_renderer_additionalfee');
        $block->setChild('vc_aio_total_renderer', $renderer);

        $renderer = $block->getLayout()->createBlock('vc_aio/adminhtml_total_renderer_environmentalfee');
        $block->setChild('vc_aio_total_renderer_environmental', $renderer);
    }
}
