<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\GraphQl\Quote;

use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Model\Quote as QuoteModel;
use Magento\Quote\Model\QuoteIdMask;
use Magento\Quote\Model\QuoteIdToMaskedQuoteIdInterface;
use Magento\TestFramework\ObjectManager;
use Magento\TestFramework\TestCase\GraphQlAbstract;

/**
 * Test for adding simple product to cart test
 */
class AddSimpleProductsToCartTest extends GraphQlAbstract
{
    /**
     * @var QuoteIdMask
     */
    private $quoteIdMask;

    /**
     * @var ObjectManager
     */
    private $objectManager;

    /**
     * @var QuoteModel
     */
    private $quoteModel;

    /**
     * @var QuoteIdToMaskedQuoteIdInterface
     */
    private $quoteIdToMaskedQuote;

    /**
     * @return void
     */
    protected function setUp()
    {
        $this->objectManager = \Magento\TestFramework\Helper\Bootstrap::getObjectManager();
        $this->quoteIdMask = $this->objectManager->create(QuoteIdMask::class);
        $this->quoteModel = $this->objectManager->create(QuoteModel::class);
        $this->quoteIdToMaskedQuote = $this->objectManager->create(QuoteIdToMaskedQuoteIdInterface::class);
    }

    /**
     * @magentoApiDataFixture Magento/Catalog/_files/product_with_options.php
     * @magentoApiDataFixture Magento/Checkout/_files/active_quote.php
     * @return void
     */
    public function testAddSimpleProductsToCartForGuest()
    {
        $this->quoteModel->load('test_order_1', 'reserved_order_id');
        $quoteHash = $this->quoteIdToMaskedQuote->execute((int) $this->quoteModel->getId());

        $query = <<<QUERY
mutation {
  addSimpleProductsToCart(
    input: {
      cart_id: "$quoteHash", 
      cartItems: [
        {
          details: {
            sku: "simple", 
            qty: 1
          }, 
          customizable_options: [{id: 1, value: "Test"}, {id: 2, value: "Wow"}, {id: 3, value: "test.jpg"}, {id: 5, value: "1"}, {id: 6, value: "3"}]
        }
      ]
    }
  ) {
    
    cart {
      items {
        id
        qty
        ... on SimpleCartItem {
          customizable_options {
            id
            values {
              id
              label
              price {
                type
                value
              }
            }
          }
        }
        product {
          sku
          name
          categories {
            id
            name
          }
          websites {
            name
          }
        }
      }

    }
    
  }
}
QUERY;
        $response = $this->graphQlQuery($query);

        var_dump($response);

        self::assertArrayHasKey('addSimpleProductsToCart', $response);


    }
}
