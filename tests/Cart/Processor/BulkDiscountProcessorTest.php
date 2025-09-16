<?php declare(strict_types=1);

namespace AcademyCartExamples\Test\Cart\Processor;

use AcademyCartExamples\Cart\Processor\BulkDiscountProcessor;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Checkout\Cart\Cart;
use Shopware\Core\Checkout\Cart\CartBehavior;
use Shopware\Core\Checkout\Cart\LineItem\CartDataCollection;
use Shopware\Core\Checkout\Cart\LineItem\LineItem;
use Shopware\Core\Checkout\Cart\Price\Struct\CalculatedPrice;
use Shopware\Core\Checkout\Cart\Tax\Struct\CalculatedTaxCollection;
use Shopware\Core\Checkout\Cart\Tax\Struct\TaxRuleCollection;
use Shopware\Core\Checkout\Customer\CustomerEntity;
use Shopware\Core\System\SalesChannel\SalesChannelContext;

class BulkDiscountProcessorTest extends TestCase
{
    private BulkDiscountProcessor $processor;

    protected function setUp(): void
    {
        $this->processor = new BulkDiscountProcessor();
    }

    public function testAppliesBulkDiscountForB2BCustomers(): void
    {
        $cart = $this->createCartWithQuantity(100); // 100 items
        $context = $this->createB2BContext();
        $data = new CartDataCollection();
        $behavior = new CartBehavior();

        $this->processor->process($data, $cart, $cart, $context, $behavior);

        $lineItems = $cart->getLineItems();
        foreach ($lineItems as $lineItem) {
            if ($lineItem->getType() === LineItem::PRODUCT_LINE_ITEM_TYPE) {
                $price = $lineItem->getPrice();
                $this->assertNotNull($price);
                
                // Should have 15% discount applied
                $expectedUnitPrice = 10.00 * 0.85; // 15% off
                $this->assertEquals($expectedUnitPrice, $price->getUnitPrice());
            }
        }
    }

    public function testAppliesCorrectDiscountTiers(): void
    {
        // Test 20+ items (5% discount)
        $cart20 = $this->createCartWithQuantity(20);
        $context = $this->createB2BContext();
        $data = new CartDataCollection();
        $behavior = new CartBehavior();

        $this->processor->process($data, $cart20, $cart20, $context, $behavior);

        $lineItem = $cart20->getLineItems()->first();
        $this->assertNotNull($lineItem);
        $price = $lineItem->getPrice();
        $this->assertNotNull($price);
        
        // Should have 5% discount applied
        $expectedUnitPrice = 10.00 * 0.95; // 5% off
        $this->assertEquals($expectedUnitPrice, $price->getUnitPrice());
    }

    public function testIgnoresRegularCustomers(): void
    {
        $cart = $this->createCartWithQuantity(100);
        $context = $this->createRegularCustomerContext();
        $data = new CartDataCollection();
        $behavior = new CartBehavior();

        $this->processor->process($data, $cart, $cart, $context, $behavior);

        $lineItems = $cart->getLineItems();
        foreach ($lineItems as $lineItem) {
            if ($lineItem->getType() === LineItem::PRODUCT_LINE_ITEM_TYPE) {
                $price = $lineItem->getPrice();
                $this->assertNotNull($price);
                
                // Should have original price (no discount)
                $this->assertEquals(10.00, $price->getUnitPrice());
            }
        }
    }

    public function testNoDiscountForSmallQuantities(): void
    {
        $cart = $this->createCartWithQuantity(10); // Below 20 items
        $context = $this->createB2BContext();
        $data = new CartDataCollection();
        $behavior = new CartBehavior();

        $this->processor->process($data, $cart, $cart, $context, $behavior);

        $lineItem = $cart->getLineItems()->first();
        $this->assertNotNull($lineItem);
        $price = $lineItem->getPrice();
        $this->assertNotNull($price);
        
        // Should have original price (no discount)
        $this->assertEquals(10.00, $price->getUnitPrice());
    }

    private function createCartWithQuantity(int $quantity): Cart
    {
        $cart = new Cart('test-cart');
        
        $lineItem = new LineItem('test-product', LineItem::PRODUCT_LINE_ITEM_TYPE);
        $lineItem->setStackable(true); // Make it stackable
        $lineItem->setQuantity($quantity);
        $lineItem->setPrice(new CalculatedPrice(
            10.00, // unit price
            10.00 * $quantity, // total price
            new CalculatedTaxCollection(),
            new TaxRuleCollection()
        ));
        
        $cart->add($lineItem);
        return $cart;
    }

    private function createB2BContext(): SalesChannelContext
    {
        $customer = new CustomerEntity();
        $customer->setCompany('Test Company');
        
        $context = $this->createMock(SalesChannelContext::class);
        $context->method('getCustomer')->willReturn($customer);
        
        return $context;
    }

    private function createRegularCustomerContext(): SalesChannelContext
    {
        $customer = new CustomerEntity();
        // No company set = regular customer
        
        $context = $this->createMock(SalesChannelContext::class);
        $context->method('getCustomer')->willReturn($customer);
        
        return $context;
    }
}
