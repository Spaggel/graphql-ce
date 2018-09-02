<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\SimpleProductGraphQl\Model\Resolver\Cart;

use Magento\Authorization\Model\UserContextInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Framework\DataObject;
use Magento\Framework\DataObjectFactory;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Exception\GraphQlInputException;
use Magento\Framework\GraphQl\Query\Resolver\Value;
use Magento\Framework\GraphQl\Query\Resolver\ValueFactory;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Magento\Framework\Message\AbstractMessage;
use Magento\Framework\Stdlib\ArrayManager;
use Magento\Quote\Api\Data\CartInterface;
use Magento\Quote\Api\GuestCartRepositoryInterface;
use Magento\Quote\Model\Quote;
use Magento\Quote\Model\QuoteRepository;
use Magento\QuoteGraphQl\Model\Hydrator\CartHydrator;

/**
 * {@inheritdoc}
 */
class AddSimpleProductsToCart implements ResolverInterface
{
    /**
     * @var DataObjectFactory
     */
    private $dataObjectFactory;

    /**
     * @var GuestCartRepositoryInterface
     */
    private $guestCartRepository;

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
     * @var ValueFactory
     */
    private $valueFactory;

    /**
     * @var UserContextInterface
     */
    private $userContext;

    /**
     * @param DataObjectFactory $dataObjectFactory
     * @param CartHydrator $cartHydrator
     * @param ArrayManager $arrayManager
     * @param GuestCartRepositoryInterface $guestCartRepository
     * @param QuoteRepository $quoteRepository
     * @param ProductRepositoryInterface $productRepository
     * @param ValueFactory $valueFactory
     * @param UserContextInterface $userContext
     */
    public function __construct(
        DataObjectFactory $dataObjectFactory,
        CartHydrator $cartHydrator,
        ArrayManager $arrayManager,
        GuestCartRepositoryInterface $guestCartRepository,
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
        $this->guestCartRepository = $guestCartRepository;
        $this->dataObjectFactory = $dataObjectFactory;
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

        /** @var CartInterface|Quote $cart */
        $cart = $this->guestCartRepository->get($cartId);

        foreach ($cartItems as $cartItem) {
            $sku = $this->arrayManager->get('details/sku', $cartItem);
            $product  = $this->productRepository->get($sku);

            $cart->addProduct($product, $this->getBuyRequest($cartItem));

            if ($cart->getData('has_error')) {
                throw new GraphQlInputException(__('Cart has an error: %1 (%2)', $this->getCartErrors($cart), $sku));
            }
        }

        $this->quoteRepository->save($cart);

        $result = function () use ($cart) {
            return [
                'cart' => $this->cartHydrator->hydrate($cart)
            ];
        };

        return $this->valueFactory->create($result);
    }

    /**
     * @param array $cartItem
     *
     * @return DataObject
     */
    private function getBuyRequest($cartItem): DataObject
    {
        $qty = $this->arrayManager->get('details/qty', $cartItem);
        $customizableOptions = $this->arrayManager->get('customizable_options', $cartItem, []);
        $customOptions = [];

        foreach ($customizableOptions as $customizableOption) {
            $customOptions[$customizableOption['id']] = $customizableOption['value'];
        }

        return $this->dataObjectFactory->create([
            'data' => [
                'qty' => $qty,
                'options' => $customOptions
            ]
        ]);
    }

    /**
     * @param CartInterface|Quote $cart
     *
     * @return string
     */
    private function getCartErrors($cart): string
    {
        $errorMessages = [];

        /** @var AbstractMessage $error */
        foreach ($cart->getErrors() as $error) {
            $errorMessages[] = $error->getText();
        }

        return implode(PHP_EOL, $errorMessages);
    }
}