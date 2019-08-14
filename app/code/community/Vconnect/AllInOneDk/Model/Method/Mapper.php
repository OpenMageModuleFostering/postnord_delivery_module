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
 * @author Ivaylo Alexandrov
 * @email ivaylo@vconnect.dk
 * @class Vconnect_Postnord_Model_Method_Mapper
 */
/*
@author Ivaylo Alexandrov ivaylo@vconnect.dk
*/
class Vconnect_Postnord_Model_Method_Mapper extends Mage_Core_Model_Abstract
{
    
    public function mapBlocksForMethods(Vconnect_AllInOne_Block_Shipping_Dialog $block)
    {
        $layout = Mage::app()->getLayout();
        foreach ($block->getRates() as $code => $rate)
        {
            if(isset($rate['price'])){
                
            }
        }
    }
    
    public function getConfigForMethod($code)
    {
        return array(
            array(
                'private' => array(
                    'delivery_type' => array(

                    ),
                    'type'          => array(

                    ),
                    
                )
            ),
            array(
                'commercial' => array(
                    'delivery_type' => array(

                    ),
                    'type'          => array(

                    ),
                   
                )
            ),
            array(
                'pickup' => array(
                    'delivery_type' => array(
                        
                    ),
                    'type'          => array(

                    ),
                    
                )
            ),
        );
    }
    
    
}