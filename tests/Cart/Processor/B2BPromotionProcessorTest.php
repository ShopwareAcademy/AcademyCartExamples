<?php declare(strict_types=1);

namespace AcademyCartExamples\Test\Cart\Processor;

use AcademyCartExamples\Cart\Processor\B2BPromotionProcessor;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Checkout\Cart\Cart;
use Shopware\Core\Checkout\Cart\CartBehavior;
use Shopware\Core\Checkout\Cart\LineItem\CartDataCollection;
use Shopware\Core\Checkout\Cart\LineItem\LineItem;
use Shopware\Core\Checkout\Cart\Price\Struct\CalculatedPrice;
use Shopware\Core\Checkout\Cart\Tax\Struct\CalculatedTaxCollection;
use Shopware\Core\Checkout\Cart\Tax\Struct\TaxRuleCollection;
use Shopware\Core\Framework\Test\TestCaseBase\KernelTestBehaviour;
use Shopware\Core\System\SalesChannel\SalesChannelContext;

class B2BPromotionProcessorTest extends TestCase
{
    use KernelTestBehaviour;

    private B2BPromotionProcessor $processor;

    protected function setUp(): void
    {
        $this->processor = new B2BPromotionProcessor();
    }

    public function testAppliesB2BDiscountForHighValueOrders(): void
    {
        $cart = $this->createCartWithValue(600.00); // Over €500 threshold
        $context = $this->createB2BContext();
        $data = new CartDataCollection();
        $behavior = new CartBehavior();

        $this->processor->process($data, $cart, $cart, $context, $behavior);

        // Should have added a promotion line item
        $promotionItems = $cart->getLineItems()->filter(function (LineItem $item) {
            return $item->getType() === LineItem::PROMOTION_LINE_ITEM_TYPE;
        });
        $this->assertCount(1, $promotionItems);

        $promotionItem = $promotionItems->first();
        $this->assertNotNull($promotionItem);
        $this->assertEquals('b2b-bonus-discount', $promotionItem->getId());
        $this->assertEquals('B2B Bonus Discount', $promotionItem->getLabel());

        $price = $promotionItem->getPrice();
        $this->assertNotNull($price);
        $this->assertEquals(-30.00, $price->getUnitPrice()); // 5% of €600
    }

    public function testDoesNotApplyDiscountForLowValueOrders(): void
    {
        $cart = $this->createCartWithValue(400.00); // Below €500 threshold
        $context = $this->createB2BContext();
        $data = new CartDataCollection();
        $behavior = new CartBehavior();

        $this->processor->process($data, $cart, $cart, $context, $behavior);

        // Should not have added any promotion line items
        $promotionItems = $cart->getLineItems()->filter(function (LineItem $item) {
            return $item->getType() === LineItem::PROMOTION_LINE_ITEM_TYPE;
        });
        $this->assertCount(0, $promotionItems);
    }

    public function testDoesNotApplyDiscountForRegularCustomers(): void
    {
        $cart = $this->createCartWithValue(600.00);
        $context = $this->createRegularCustomerContext();
        $data = new CartDataCollection();
        $behavior = new CartBehavior();

        $this->processor->process($data, $cart, $cart, $context, $behavior);

        // Should not have added any promotion line items
        $promotionItems = $cart->getLineItems()->filter(function (LineItem $item) {
            return $item->getType() === LineItem::PROMOTION_LINE_ITEM_TYPE;
        });
        $this->assertCount(0, $promotionItems);
    }

    private function createCartWithValue(float $value): Cart
    {
        $cart = new Cart('test-cart');
        
        $lineItem = new LineItem('test-product', LineItem::PRODUCT_LINE_ITEM_TYPE);
        $lineItem->setStackable(true);
        $lineItem->setQuantity(1);
        $lineItem->setPrice(new CalculatedPrice(
            $value,
            $value,
            new CalculatedTaxCollection(),
            new TaxRuleCollection()
        ));
        
        $cart->add($lineItem);
        return $cart;
    }

    private function createB2BContext(): SalesChannelContext
    {
        $customer = $this->createMock(\Shopware\Core\Checkout\Customer\CustomerEntity::class);
        $customer->method('getCompany')->willReturn('Test Company');
        
        $context = $this->createMock(SalesChannelContext::class);
        $context->method('getCustomer')->willReturn($customer);
        
        return $context;
    }

    private function createRegularCustomerContext(): SalesChannelContext
    {
        $customer = $this->createMock(\Shopware\Core\Checkout\Customer\CustomerEntity::class);
        $customer->method('getCompany')->willReturn(null);
        
        $context = $this->createMock(SalesChannelContext::class);
        $context->method('getCustomer')->willReturn($customer);
        
        return $context;
    }
}
