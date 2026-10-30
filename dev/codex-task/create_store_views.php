<?php
declare(strict_types=1);

use Magento\Framework\App\Bootstrap;
use Magento\Store\Model\StoreFactory;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\App\Config\Storage\WriterInterface;

require __DIR__ . '/../../app/bootstrap.php';

$bootstrap = Bootstrap::create(BP, $_SERVER);
$objectManager = $bootstrap->getObjectManager();
$storeManager = $objectManager->get(StoreManagerInterface::class);
$storeFactory = $objectManager->get(StoreFactory::class);
$configWriter = $objectManager->get(WriterInterface::class);

$website = $storeManager->getWebsite();
$storeGroupId = (int)$website->getDefaultGroupId();
$storeViews = [
    'en' => ['name' => 'English', 'locale' => 'en_US', 'currency' => 'GBP'],
    'fr' => ['name' => 'French', 'locale' => 'fr_FR', 'currency' => 'EUR'],
    'de' => ['name' => 'German', 'locale' => 'de_DE', 'currency' => 'EUR'],
];

foreach ($storeViews as $code => $settings) {
    try {
        $store = $storeManager->getStore($code);
        echo "Store view {$code} already exists.\n";
    } catch (\Magento\Framework\Exception\NoSuchEntityException $exception) {
        $store = $storeFactory->create();
        $store->setCode($code);
        $store->setName($settings['name']);
        $store->setWebsiteId((int)$website->getId());
        $store->setGroupId($storeGroupId);
        $store->setIsActive(1);
        $store->save();
        echo "Created store view {$code}.\n";
    }

    $configWriter->save('general/locale/code', $settings['locale'], 'stores', (int)$store->getId());
    $configWriter->save('currency/options/default', $settings['currency'], 'stores', (int)$store->getId());
}

$configWriter->delete('general/locale/code', 'stores', 0);
$configWriter->delete('currency/options/default', 'stores', 0);
$configWriter->delete('currency/options/base', 'websites', 0);
$configWriter->delete('currency/options/base', 'websites', (int)$website->getId());
$configWriter->delete('currency/options/allow', 'websites', 0);
$configWriter->delete('currency/options/allow', 'websites', (int)$website->getId());
$configWriter->save('currency/options/base', 'GBP');
$configWriter->save('currency/options/default', 'GBP');
$configWriter->save('currency/options/allow', 'GBP,EUR');

$connection = $objectManager->get(\Magento\Framework\App\ResourceConnection::class)->getConnection();
$rateTable = $connection->getTableName('directory_currency_rate');
$connection->insertOnDuplicate(
    $rateTable,
    ['currency_from' => 'GBP', 'currency_to' => 'EUR', 'rate' => 1.17],
    ['rate']
);

$storeManager->reinitStores();
echo "Configured locales and currencies. The demo GBP to EUR rate is 1.17.\n";
