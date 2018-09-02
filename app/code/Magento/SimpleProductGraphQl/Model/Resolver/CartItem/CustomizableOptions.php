<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\SimpleProductGraphQl\Model\Resolver\CartItem;

use Magento\Authorization\Model\UserContextInterface;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Query\Resolver\Value;
use Magento\Framework\GraphQl\Query\Resolver\ValueFactory;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Magento\Quote\Model\Quote\Item as QuoteItem;

/**
 * {@inheritdoc}
 */
class CustomizableOptions implements ResolverInterface
{
    /**
     * @var ValueFactory
     */
    private $valueFactory;

    /**
     * @var UserContextInterface
     */
    private $userContext;

    /**
     * @param ValueFactory $valueFactory
     * @param UserContextInterface $userContext
     */
    public function __construct(
        ValueFactory $valueFactory,
        UserContextInterface $userContext
    ) {
        $this->valueFactory = $valueFactory;
        $this->userContext = $userContext;
    }

    /**
     * {@inheritDoc}
     */
    public function resolve(Field $field, $context, ResolveInfo $info, array $value = null, array $args = null) : Value
    {
        if (!isset($value['model'])) {
            return $this->valueFactory->create(function () {
                return [];
            });
        }

        /** @var QuoteItem $cartItem */
        $cartItem = $value['model'];
        $optionIds = $cartItem->getOptionByCode('option_ids');

        if (!$optionIds) {
            return $this->valueFactory->create(function () {
                return [];
            });
        }

        $customOptions = [];
        $product = $cartItem->getProduct();
        $customOptionIds = explode(',', $optionIds->getValue());

        foreach ($customOptionIds as $optionId) {
            $option = $product->getOptionById($optionId);

            if (!$option) {
                continue;
            }

            $itemOption = $cartItem->getOptionByCode('option_' . $option->getId());
            /** @var \Magento\Catalog\Model\Product\Option\Type\DefaultType $group */
            $group = $option->groupFactory($option->getType())
                ->setOption($option)
                ->setConfigurationItem($cartItem)
                ->setConfigurationItemOption($itemOption);

            if ('file' == $option->getType()) {
                $downloadParams = $cartItem->getFileDownloadParams();
                if ($downloadParams) {
                    $url = $downloadParams->getUrl();
                    if ($url) {
                        $group->setCustomOptionDownloadUrl($url);
                    }
                    $urlParams = $downloadParams->getUrlParams();
                    if ($urlParams) {
                        $group->setCustomOptionUrlParams($urlParams);
                    }
                }
            }

            $optionValue = $option->getValueById($itemOption->getValue());

            $customOptions[] = [
                'id' => $option->getId(),
                'label' => $option->getTitle(),
                'type' => $option->getType(),
                'values' => [
                    [
                        'id' => $optionValue->getId(),
                        'label' => $optionValue->getTitle(),
                        'sort_order' => $optionValue->getSortOrder(),
                        'price' => [
                            'type' => strtoupper($optionValue->getPriceType()),
                            'units' => '$',
                            'value' => $optionValue->getPrice(),
                        ]
                    ]
                ],
                'sort_order' => $option->getSortOrder(),
            ];
        }

        $result = function () use ($customOptions) {
            return $customOptions;
        };

        return $this->valueFactory->create($result);
    }
}