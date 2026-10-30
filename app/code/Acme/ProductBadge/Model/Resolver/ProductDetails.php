<?php
declare(strict_types=1);

namespace Acme\ProductBadge\Model\Resolver;

use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Magento\Framework\Pricing\PriceCurrencyInterface;
use Magento\Store\Model\StoreManagerInterface;

class ProductDetails implements ResolverInterface
{
    private ProductRepositoryInterface $productRepository;
    private StoreManagerInterface $storeManager;
    private PriceCurrencyInterface $priceCurrency;

    public function __construct(
        ProductRepositoryInterface $productRepository,
        StoreManagerInterface $storeManager,
        PriceCurrencyInterface $priceCurrency
    ) {
        $this->productRepository = $productRepository;
        $this->storeManager = $storeManager;
        $this->priceCurrency = $priceCurrency;
    }

    public function resolve(
        Field $field,
        $context,
        ResolveInfo $info,
        ?array $value = null,
        ?array $args = null
    ) {
        $sku = trim((string)($args['sku'] ?? ''));
        if ($sku === '') {
            return null;
        }

        $store = $this->storeManager->getStore();

        try {
            $product = $this->productRepository->get($sku, false, (int)$store->getId(), true);
        } catch (NoSuchEntityException $exception) {
            return null;
        }

        $badge = $product->getAttributeText('badge');
        if (!is_string($badge) || $badge === '') {
            $badge = null;
        }

        return [
            'sku' => $product->getSku(),
            'name' => $product->getName(),
            'price' => (float)$this->priceCurrency->convert($product->getFinalPrice(), $store),
            'currency' => $store->getCurrentCurrencyCode(),
            'stock_status' => $product->isSalable() ? 'IN_STOCK' : 'OUT_OF_STOCK',
            'badge' => $badge,
        ];
    }
}
