<?php
declare(strict_types=1);

namespace Acme\ProductBadge\Setup\Patch\Data;

use Magento\Catalog\Model\Product;
use Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface;
use Magento\Eav\Setup\EavSetupFactory;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;
use Magento\Store\Model\StoreManagerInterface;

class AddBadgeAttribute implements DataPatchInterface
{
    private ModuleDataSetupInterface $moduleDataSetup;
    private EavSetupFactory $eavSetupFactory;
    private StoreManagerInterface $storeManager;

    public function __construct(
        ModuleDataSetupInterface $moduleDataSetup,
        EavSetupFactory $eavSetupFactory,
        StoreManagerInterface $storeManager
    ) {
        $this->moduleDataSetup = $moduleDataSetup;
        $this->eavSetupFactory = $eavSetupFactory;
        $this->storeManager = $storeManager;
    }

    public function apply()
    {
        $this->moduleDataSetup->getConnection()->startSetup();

        $eavSetup = $this->eavSetupFactory->create(['setup' => $this->moduleDataSetup]);
        $eavSetup->addAttribute(Product::ENTITY, 'badge', [
            'type' => 'int',
            'label' => 'Badge',
            'input' => 'select',
            'source' => \Magento\Eav\Model\Entity\Attribute\Source\Table::class,
            'required' => false,
            'user_defined' => true,
            'global' => ScopedAttributeInterface::SCOPE_STORE,
            'visible' => true,
            'visible_on_front' => true,
            'used_in_product_listing' => true,
            'searchable' => false,
            'filterable' => false,
            'comparable' => false,
            'group' => 'Product Details',
            'sort_order' => 80,
            'option' => [
                'value' => [
                    0 => $this->getOptionLabels('New', 'New', 'Nouveau', 'Neu'),
                    1 => $this->getOptionLabels(
                        'Best Seller',
                        'Best Seller',
                        'Meilleure vente',
                        'Bestseller'
                    ),
                    2 => $this->getOptionLabels('Sale', 'Sale', 'Promo', 'Angebot'),
                ],
            ],
        ]);

        $this->moduleDataSetup->getConnection()->endSetup();

        return $this;
    }

    private function getStoreId(string $code): int
    {
        try {
            return (int)$this->storeManager->getStore($code)->getId();
        } catch (NoSuchEntityException $exception) {
            return 0;
        }
    }

    private function getOptionLabels(string $default, string $english, string $french, string $german): array
    {
        $labels = [0 => $default];
        foreach (['en' => $english, 'fr' => $french, 'de' => $german] as $code => $label) {
            $storeId = $this->getStoreId($code);
            if ($storeId > 0) {
                $labels[$storeId] = $label;
            }
        }

        return $labels;
    }

    public static function getDependencies(): array
    {
        return [];
    }

    public function getAliases(): array
    {
        return [];
    }
}
