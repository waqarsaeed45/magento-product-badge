<?php
declare(strict_types=1);

namespace Acme\ProductBadge\Plugin;

use Magento\Catalog\Model\Product;

class ProductNamePlugin
{
    public function afterGetName(Product $product, $name)
    {
        if (!is_string($name) || $name === '') {
            return $name;
        }

        $badge = $product->getAttributeText('badge');
        if (!is_string($badge) || $badge === '') {
            return $name;
        }

        return $name . ' [' . $badge . ']';
    }
}
