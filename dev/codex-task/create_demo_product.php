<?php
declare(strict_types=1);

use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Model\ProductFactory;
use Magento\Catalog\Model\Product\Attribute\Source\Status;
use Magento\Catalog\Model\Product\Visibility;
use Magento\Eav\Model\Config as EavConfig;
use Magento\Framework\App\Bootstrap;
use Magento\Framework\App\State;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Store\Model\StoreManagerInterface;
use Magento\InventoryApi\Api\Data\SourceItemInterface;
use Magento\InventoryApi\Api\Data\SourceItemInterfaceFactory;
use Magento\InventoryApi\Api\SourceItemsSaveInterface;

require __DIR__ . '/../../app/bootstrap.php';

$bootstrap = Bootstrap::create(BP, $_SERVER);
$objectManager = $bootstrap->getObjectManager();
$objectManager->get(State::class)->setAreaCode('adminhtml');
$storeManager = $objectManager->get(StoreManagerInterface::class);
$productRepository = $objectManager->get(ProductRepositoryInterface::class);
$productFactory = $objectManager->get(ProductFactory::class);
$eavConfig = $objectManager->get(EavConfig::class);

try {
    $product = $productRepository->get('24-MB01', false, 0, true);
    echo "Updating demo product 24-MB01.\n";
} catch (NoSuchEntityException $exception) {
    $product = $productFactory->create();
    $product->setSku('24-MB01');
    $product->setTypeId('simple');
    $product->setAttributeSetId(
        (int)$eavConfig->getEntityType('catalog_product')->getDefaultAttributeSetId()
    );
    echo "Created demo product 24-MB01.\n";
}

$badgeAttribute = $eavConfig->getAttribute('catalog_product', 'badge');
if (!$badgeAttribute->getId()) {
    throw new RuntimeException('The badge attribute is missing. Run bin/magento setup:upgrade first.');
}

$newBadgeId = (int)$badgeAttribute->getSource()->getOptionId('New');
$websiteId = (int)$storeManager->getWebsite()->getId();
$product->setStoreId(0);
$product->setName('Example Product');
$product->setPrice(24.99);
$product->setWeight(1);
$product->setStatus(Status::STATUS_ENABLED);
$product->setVisibility(Visibility::VISIBILITY_BOTH);
$product->setTaxClassId(2);
$product->setWebsiteIds([$websiteId]);
$product->setCategoryIds([2]);
$product->setData('badge', $newBadgeId);
$product->setStockData([
    'use_config_manage_stock' => 0,
    'manage_stock' => 1,
    'qty' => 10,
    'is_in_stock' => 1,
]);

$sourceImagePath = __DIR__ . '/example-product.png';
$imagePath = BP . '/pub/media/import/example-product.png';
if (is_file($sourceImagePath) && in_array($product->getImage(), [null, '', 'no_selection'], true)) {
    if (!is_dir(dirname($imagePath))) {
        mkdir(dirname($imagePath), 0775, true);
    }
    copy($sourceImagePath, $imagePath);
    $product->addImageToMediaGallery(
        $imagePath,
        ['image', 'small_image', 'thumbnail'],
        false,
        false
    );
}

$productRepository->save($product);

$sourceItem = $objectManager->get(SourceItemInterfaceFactory::class)->create();
$sourceItem->setSourceCode('default');
$sourceItem->setSku('24-MB01');
$sourceItem->setQuantity(10);
$sourceItem->setStatus(SourceItemInterface::STATUS_IN_STOCK);
$objectManager->get(SourceItemsSaveInterface::class)->execute([$sourceItem]);

echo "Demo product is enabled with the New badge.\n";
