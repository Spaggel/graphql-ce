<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\SimpleProductGraphQl\Model\Resolver\Product;

use Magento\Authorization\Model\UserContextInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Exception\GraphQlInputException;
use Magento\Framework\GraphQl\Query\Resolver\Value;
use Magento\Framework\GraphQl\Query\Resolver\ValueFactory;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Magento\Framework\Stdlib\ArrayManager;
use Magento\Quote\Model\Quote as QuoteModel;
use Magento\Quote\Model\QuoteRepository;
use Magento\QuoteGraphQl\Model\Hydrator\CartHydrator;

/**
 * {@inheritdoc}
 */
class AddSimpleProductsToCart implements ResolverInterface
{
    /**
     * @var QuoteRepository
     */
    private $quoteRepository;

    /**
     * @var ProductRepositoryInterface
     */
    private $productRepository;

    /**
     * @var CartHydrator
     */
    private $cartHydrator;

    /**
     * @var ArrayManager
     */
    private $arrayManager;

    /**
     * @var QuoteModel
     */
    private $quote;

    /**
     * @var ValueFactory
     */
    private $valueFactory;

    /**
     * @var UserContextInterface
     */
    private $userContext;

    /**
     * @param CartHydrator $cartHydrator
     * @param ArrayManager $arrayManager
     * @param QuoteRepository $quoteRepository
     * @param ProductRepositoryInterface $productRepository
     * @param ValueFactory $valueFactory
     * @param UserContextInterface $userContext
     */
    public function __construct(
        CartHydrator $cartHydrator,
        ArrayManager $arrayManager,
        QuoteRepository $quoteRepository,
        ProductRepositoryInterface $productRepository,
        ValueFactory $valueFactory,
        UserContextInterface $userContext
    ) {
        $this->valueFactory = $valueFactory;
        $this->userContext = $userContext;
        $this->arrayManager = $arrayManager;
        $this->productRepository = $productRepository;
        $this->cartHydrator = $cartHydrator;
        $this->quoteRepository = $quoteRepository;
    }

    /**
     * {@inheritDoc}
     */
    public function resolve(Field $field, $context, ResolveInfo $info, array $value = null, array $args = null) : Value
    {
        $cartId = $this->arrayManager->get('input/cart_id', $args);
        $cartItems = $this->arrayManager->get('input/cartItems', $args);

        if (!isset($cartId)) {
            throw new GraphQlInputException(
                __('Missing key %1 in cart data', ['cart_id'])
            );
        }

        if (!isset($cartItems)) {
            throw new GraphQlInputException(
                __('Missing key %1 in cart data', ['cartItems'])
            );
        }

        // todo: add security checking

        $cart = $this->quoteRepository->getActive($cartId);

        foreach ($cartItems as $cartItem) {
            $sku = $this->arrayManager->get('details/sku', $cartItem);
            $qty = $this->arrayManager->get('details/quantity', $cartItem);
            $product  = $this->productRepository->get($sku);

            $cart->addProduct($product, $qty);
        }

        $this->quoteRepository->save($cart);

        $cartData = [
            'cart' => $this->cartHydrator->hydrate($cart),
        ];

        $result = function () use ($cartData) {
            return $cartData;
        };

        return $this->valueFactory->create($result);
    }
}