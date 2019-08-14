<?php
Mage::getConfig()->deleteConfig('carriers/vconnect_postnord/sallowspecific');
Mage::getConfig()->deleteConfig('carriers/vconnect_postnord/specificcountry');
Mage::getConfig()->deleteConfig('carriers/vconnect_postnord/handling_fee');

$storeCountryId = Mage::getStoreConfig('shipping/origin/country_id');
if ($storeCountryId == 'SE') {
    Mage::getConfig()->saveConfig('carriers/vconnect_postnord/button_label', 'Klicka här för att välja leveransalternativ');
    Mage::getConfig()->saveConfig('carriers/vconnect_postnord/button_label_active', 'Klicka här för att välja annat leveransalternativ');
    Mage::getConfig()->saveConfig('carriers/vconnect_postnord/title', 'Leverans med PostNord');

    Mage::getConfig()->saveConfig('carriers/vconnect_postnord_business/name', 'Till företag');
    Mage::getConfig()->saveConfig('carriers/vconnect_postnord_business/arrival_option_text', '08:00 - 16:00');
    Mage::getConfig()->saveConfig('carriers/vconnect_postnord_business/transit_time', '1-3 dagar');
    
    Mage::getConfig()->saveConfig('carriers/vconnect_postnord_home/name', 'Till dörren');
    Mage::getConfig()->saveConfig('carriers/vconnect_postnord_home/arrival_option_text', '08:00 - 17:00');
    Mage::getConfig()->saveConfig('carriers/vconnect_postnord_home/transit_time', '1-3 dagar');
    Mage::getConfig()->saveConfig('carriers/vconnect_postnord_home/additional_fee_label', '17:00 - 21:00');

    Mage::getConfig()->saveConfig('carriers/vconnect_postnord_pickup/name', 'Till serviceställe');
    Mage::getConfig()->saveConfig('carriers/vconnect_postnord_pickup/transit_time', '1-3 dagar');

    Mage::getConfig()->saveConfig('carriers/vconnect_postnord_mailbox/name', 'Till postlådan');
    Mage::getConfig()->saveConfig('carriers/vconnect_postnord_mailbox/arrival_option_text', '2-3 dagar');
    Mage::getConfig()->saveConfig('carriers/vconnect_postnord_mailbox/transit_time', '1-3 dagar');
    Mage::getConfig()->saveConfig('carriers/vconnect_postnord_mailbox/additional_fee_label', '1 dag');

    Mage::getConfig()->saveConfig('carriers/vconnect_postnord_eu/name', 'EU');
    Mage::getConfig()->saveConfig('carriers/vconnect_postnord_intl/name', 'To door');

} elseif ($storeCountryId == 'FI') {
    Mage::getConfig()->saveConfig('carriers/vconnect_postnord/button_label', 'Valitse toimitustapa');
    Mage::getConfig()->saveConfig('carriers/vconnect_postnord/button_label_active', 'Valitse toinen toimitustapa');
    Mage::getConfig()->saveConfig('carriers/vconnect_postnord/title', 'PostNord');

    Mage::getConfig()->saveConfig('carriers/vconnect_postnord_business/name', 'Toimitus työpaikalle');
    Mage::getConfig()->saveConfig('carriers/vconnect_postnord_business/arrival_option_text', '1-3 arkipäivää. Toimitus klo 8-17 välisenä aikana.');
    Mage::getConfig()->saveConfig('carriers/vconnect_postnord_business/transit_time', '1-3 päivää');
    
    Mage::getConfig()->saveConfig('carriers/vconnect_postnord_home/name', 'Toimitus kotiin');
    Mage::getConfig()->saveConfig('carriers/vconnect_postnord_home/arrival_option_text', '1-3 arkipäivää. Toimitus klo 8-17 välisenä aikana.');
    Mage::getConfig()->saveConfig('carriers/vconnect_postnord_home/transit_time', '1-3 päivää');

    Mage::getConfig()->saveConfig('carriers/vconnect_postnord_pickup/name', 'Toimitus palvelupisteeseen');
    Mage::getConfig()->saveConfig('carriers/vconnect_postnord_pickup/transit_time', '1-4 päivää');

    Mage::getConfig()->saveConfig('carriers/vconnect_postnord_eu/name', 'EU');
    Mage::getConfig()->saveConfig('carriers/vconnect_postnord_intl/name', 'International');
}

Mage::getConfig()->cleanCache();