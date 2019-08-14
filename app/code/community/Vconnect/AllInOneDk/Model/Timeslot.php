<?php
class Vconnect_AllInOneDk_Model_Timeslot extends Varien_Object
{
    
    private $_normalized = false;
    
    protected $_idFieldName = 'timeslot_id';
    
    /**
     * Check if shipping order is still available for this timeslot
     * @return bool
     */
    public function isActive()
    {
        $deadline = $this->getData('deadline_ts');
        /* @var $deadline Zend_Date */
        if(!$deadline instanceof Zend_Date){
            Mage::throwException('Timeslot deadline _ts is not instance of Zend_Date');
        }
        return $deadline->getTimestamp() > Mage::getModel('core/date')
                                                            ->gmtTimestamp();
    }
    
    /**
     * 
     */
    public function _construct() {
        parent::_construct();
        $this->normalize();
    }

    public function __toArray(array $arrAttributes = array()) {
        $arrData = parent::__toArray($arrAttributes);
        foreach ($arrData as $key=>$value){
            if(in_array($key, array('begin_ts','end_ts','deadline_ts')) 
                && $value instanceof Zend_Date)
            {
                $arrData[$key] = (int)$value->getTimestamp() *1000;
            }
        }
        $arrData['description'] = $this->__toString();
        return $arrData;
    }

    /**
     * 
     * @return bool
     */
    public function isFree(){
        return (bool)$this->getOrderCount();
    }
    
    /**
     * 
     * @return string
     */
    public function __toString() {
        $bdt    = $this->getBeginTs()->toString( Zend_Date::DATE_FULL );
        $bt     =  $this->getBeginTs()->toString( Zend_Date::TIME_SHORT );
        $et     =  $this->getEndTs()->toString( Zend_Date::TIME_SHORT );
        return Mage::helper('vc_alpha')
                    ->__("Expected delivery at %s between %s and %s",
                            $bdt,$bt,$et);
    }

    /**
     * 
     * @return Vconnect_Pdkalpha_Model_Timeslot
     */
    public function normalize()
    {
        if($this->_normalized){
            return $this;
        }
        
        $start  = $this->getData('beginTs');
        $end    = $this->getData('endTs');
        if($start &&  $end){
            $diff = (int)substr( $end, 0,10)  - (int)substr( $start, 0,10) ;
            $type = $diff/3600;
            $this->setData('type',$type);
        }
        
        $filter = new Zend_Filter_Word_CamelCaseToUnderscore();
        foreach ( $this->getData() as $key => $value ){
            $_key = strtolower($filter->filter($key));
            
            $this->unsetData($key);
            
            if($_key == 'status'){
                $value = strtolower( $value?:'GREEN' );
            }
            
            $this->setData( $_key, $value );
            
            if( in_array($_key, array('begin_ts','end_ts','deadline_ts') ) 
                && !$value instanceof Zend_Date)
            {
                $value = Mage::app()->getLocale()
                                                ->date( substr( $value, 0,10) );
                $newKey = str_replace('_ts', '_time',$_key );
                $time = $value->toString(Zend_Date::TIME_SHORT);
                $this->setData($newKey,$time);
                $this->setData($_key,$value);
            }
        }
        
        $prices = Mage::helper('vc_alpha')->getPricesFormated();
        if(isset($prices[$this->getStatus()]['prices'][$this->getType()])){
            $price = $prices[$this->getStatus()]['prices'][$this->getType()];
        }else{
            $price = 0;
        }
        if(isset($prices[$this->getStatus()]['color'])){
            $this->setColor('#'.$prices[$this->getStatus()]['color']);
        }else{
            $this->setColor('#a3ccea');
        }
        
        $this->setPrice($price);
        $this->setPriceFormated( Mage::helper('core')
                                            ->currency( $price, true, false ));
        $this->setIsFree( (bool) $this->getOrderCount());
        $this->_normalized = true;
        return $this;
    }
}
