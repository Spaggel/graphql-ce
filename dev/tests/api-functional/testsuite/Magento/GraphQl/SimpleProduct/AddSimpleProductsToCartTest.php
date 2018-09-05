<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\GraphQl\Quote;

use Magento\Catalog\Api\Data\ProductCustomOptionInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Quote\Model\Quote as QuoteModel;
use Magento\Quote\Model\QuoteIdToMaskedQuoteIdInterface;
use Magento\TestFramework\ObjectManager;
use Magento\TestFramework\TestCase\GraphQlAbstract;

/**
 * Test for adding simple product to cart test
 */
class AddSimpleProductsToCartTest extends GraphQlAbstract
{
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
     * @var ProductRepositoryInterface
     */
    private $productRepository;

    /**
     * @return void
     */
    protected function setUp()
    {
        $this->objectManager = \Magento\TestFramework\Helper\Bootstrap::getObjectManager();
        $this->quoteModel = $this->objectManager->create(QuoteModel::class);
        $this->productRepository = $this->objectManager->create(ProductRepositoryInterface::class);
        $this->quoteIdToMaskedQuote = $this->objectManager->create(QuoteIdToMaskedQuoteIdInterface::class);
    }

    /**
     * @magentoApiDataFixture Magento/Catalog/_files/product_with_options.php
     * @magentoApiDataFixture Magento/Checkout/_files/active_quote.php
     * @return void
     */
    public function testAddSimpleProductsToCartForGuest()
    {
        $productSku = 'simple';
        $this->quoteModel->load('test_order_1', 'reserved_order_id');
        $quoteHash = $this->quoteIdToMaskedQuote->execute((int) $this->quoteModel->getId());
        $selectedOptionData = $this->getSelectedOptionData($productSku);

        $query = <<<QUERY
mutation {
  addSimpleProductsToCart(
    input: {
      cart_id: "$quoteHash", 
      cartItems: [
        {
          details: {
            sku: "$productSku", 
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

    /**
     * @param string $productSku
     * @throws NoSuchEntityException
     */
    protected function getSelectedOptionData(string $productSku)
    {
        $selectedProductData = [];
        $product = $this->productRepository->get($productSku);
        $fieldTypes = [ProductCustomOptionInterface::OPTION_TYPE_AREA, ProductCustomOptionInterface::OPTION_TYPE_FIELD];
        $dropdownTypes = [
            ProductCustomOptionInterface::OPTION_TYPE_DROP_DOWN,
            ProductCustomOptionInterface::OPTION_TYPE_RADIO,
            ProductCustomOptionInterface::OPTION_TYPE_CHECKBOX,
        ];

        foreach ($product->getOptions() as $option) {
            if (in_array($option->getType(), $fieldTypes)) {
                $selectedProductData[$option->getOptionId()] = $option->getType(); // custom text
            }
            if (in_array($option->getType(), $dropdownTypes)) {
                $optionValues = $option->getValues();

            }
        }

        return $selectedProductData;
    }
}
