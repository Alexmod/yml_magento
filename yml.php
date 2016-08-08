<?php
require_once '/home/magento/www/app/Mage.php';

Varien_Profiler::enable();
Mage::setIsDeveloperMode(true);
ini_set('display_errors', 1);

umask(0);
Mage::app();

#-------------------------------------------------------------------------------
#  Шапка xml файла с названием магазина, категориями и прочим
#-------------------------------------------------------------------------------

$currentTimestamp = Mage::getModel('core/date')->timestamp(time()); 
$date = date('Y-m-d H:i', $currentTimestamp); 
$xmlheader = sprintf('<?xml version="1.0" encoding="utf-8"?><yml_catalog date="%s"></yml_catalog>', $date);
$xml = new SimpleXMLElement($xmlheader);
$shop = $xml->addChild('shop');
$shop->addChild('name', 'Shop name'); 
$shop->addChild('company', 'Company name');
$shop->addChild('url', Mage::getBaseUrl(Mage_Core_Model_Store::URL_TYPE_WEB));
$currencies = $shop->addChild('currencies');
$currency = $currencies->addChild('currency');
$currency->addAttribute('id', 'RUR');
$currency->addAttribute('rate', '1');
$categories = $shop->addChild('categories');


$categoriesCollection = Mage::getModel('catalog/category')->getCollection()->addAttributeToSelect('*')->load();
$rootCategoryId = Mage::app()->getStore()->getRootCategoryId();

foreach ($categoriesCollection as $categoryInfo) {
        if ($categoryInfo->getName() and $categoryInfo->getId() > $rootCategoryId){
                $category = $categories->addChild('category', $categoryInfo->getName());
                $category->addAttribute('id', $categoryInfo->getId());
                if ($categoryInfo->getParentId() > $rootCategoryId){
                        $category->addAttribute('parentId', $categoryInfo->getParentId());
                }
        }
}

#-------------------------------------------------------------------------------
#  Формируем товары
#-------------------------------------------------------------------------------

$offers = $shop->addChild('offers');
$products = Mage::getModel('catalog/product')->getCollection()->addAttributeToSelect('*')->addAttributeToFilter('status', Mage_Catalog_Model_Product_Status::STATUS_ENABLED);


foreach ( $products as $offerInfo) {
        $offer = $offers->addChild('offer');
        $offer->addAttribute('id', $offerInfo->getId());
        $offer->addAttribute('available', 'true');
        $offer->addAttribute('bid', '21');

        $offer->addChild('url', Mage::getBaseUrl(Mage_Core_Model_Store::URL_TYPE_WEB).$offerInfo->getUrlPath());
        $offer->addChild('price', $offerInfo->getPrice());
        $offer->addChild('currencyId', 'RUR');
        $offer->addChild('categoryId', end($offerInfo->getCategoryIds()));
        if ($offerInfo->getImage()) {
                $offer->addChild('picture', Mage::getBaseUrl('media').'catalog/product'.$offerInfo->getImage());
        } else {
                $offer->addChild('picture', "");
        }
        $offer->addChild('store', 'true');
        $offer->addChild('pickup', 'true');
        $offer->addChild('delivery', 'true');
        $offer->addChild('name', $offerInfo->getName());
        $offer->addChild('vendor', '');
        $offer->addChild('description', htmlspecialchars($offerInfo->getDescription()));
        $offer->addChild('manufacturer_warranty', 'false');
        $offer->addChild('country_of_origin', 'USA');
}


#-------------------------------------------------------------------------------
#  Красивый вывод с форматированием, по умолчанию $xml->asXML() - в одну строку
#-------------------------------------------------------------------------------

$dom = new DOMDocument;
$dom->preserveWhiteSpace = false;
$dom->formatOutput = true;
$dom->loadXML($xml->asXML());
echo $dom->saveXML();