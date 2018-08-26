<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);


namespace Magento\BundleGraphQl\Model\Resolver\Product\Fields;

use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Magento\Bundle\Model\Product\Type as Bundle;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Query\EnumLookup;
use Magento\Framework\GraphQl\Query\Resolver\Value;
use Magento\Framework\GraphQl\Query\ResolverInterface;

/**
 * {@inheritdoc}
 */
class ShipBundleItems implements ResolverInterface
{
    /**
     * @var EnumLookup
     */
    private $enumLookup;

    /**
     * @param EnumLookup $enumLookup
     */
    public function __construct(EnumLookup $enumLookup)
    {
        $this->enumLookup = $enumLookup;
    }

    /**
     * {@inheritdoc}
     */
    public function resolve(
        Field $field,
        $context,
        ResolveInfo $info,
        array $value = null,
        array $args = null
    ) {
        $result = function () {
            return null;
        };
        if ($value['type_id'] === Bundle::TYPE_CODE) {
            $result = isset($value['shipment_type'])
                ? $this->enumLookup->getEnumValueFromField('ShipBundleItemsEnum', $value['shipment_type']) : null;
        }

        return $result;
    }
}
