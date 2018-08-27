<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\QuoteGraphQl\Model\Hydrator;

use Magento\Quote\Api\Data\CartInterface;
use Magento\Quote\Model\Quote;

/**
 * {@inheritdoc}
 */
class CartHydrator
{
    /**
     * @param CartInterface|Quote $cart
     *
     * @return array
     */
    public function hydrate(CartInterface $cart): array
    {
        $items = [];

        foreach ($cart->getItems() as $cartItem) {
            $items[] = [
                'id' => $cartItem->getItemId(),
                'qty' => $cartItem->getQty(),
                'product' => $cartItem->getSku(),
            ];
        }

        return [
            'items' => $items,
        ];
    }
}