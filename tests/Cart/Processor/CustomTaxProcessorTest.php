<?php declare(strict_types=1);

namespace AcademyCartExamples\Test\Cart\Processor;

use AcademyCartExamples\Cart\Processor\CustomTaxProcessor;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Checkout\Cart\Cart;
use Shopware\Core\Checkout\Cart\CartBehavior;
use Shopware\Core\Checkout\Cart\LineItem\CartDataCollection;
use Shopware\Core\Checkout\Cart\Price\Struct\CartPrice;
use Shopware\Core\Checkout\Cart\Tax\Struct\CalculatedTax;
use Shopware\Core\Checkout\Cart\Tax\Struct\CalculatedTaxCollection;
use Shopware\Core\Checkout\Cart\Tax\Struct\TaxRuleCollection;
use Shopware\Core\Framework\Test\TestCaseBase\KernelTestBehaviour;
use Shopware\Core\System\SalesChannel\SalesChannelContext;

class CustomTaxProcessorTest extends TestCase
{
    use KernelTestBehaviour;

    private CustomTaxProcessor $processor;

    protected function setUp(): void
    {
        $this->processor = new CustomTaxProcessor();
    }

    public function testAppliesReducedTaxForB2BCustomers(): void
    {
        $cart = $this->createCartWithTax();
        $context = $this->createB2BContext();
        $data = new CartDataCollection();
        $behavior = new CartBehavior();

        $this->processor->process($data, $cart, $cart, $context, $behavior);

        $price = $cart->getPrice();
        $this->assertNotNull($price);
        
        $taxes = $price->getCalculatedTaxes();
        $this->assertCount(1, $taxes);

        $tax = $taxes->first();
        $this->assertNotNull($tax);
        
        // Should have 20% reduction (0.8 multiplier)
        $this->assertEquals(16.0, $tax->getTaxRate()); // 20 * 0.8
        $this->assertEquals(16.0, $tax->getTax()); // 20 * 0.8
    }

    public function testDoesNotModifyTaxForRegularCustomers(): void
    {
        $cart = $this->createCartWithTax();
        $context = $this->createRegularCustomerContext();
        $data = new CartDataCollection();
        $behavior = new CartBehavior();

        $this->processor->process($data, $cart, $cart, $context, $behavior);

        $price = $cart->getPrice();
        $this->assertNotNull($price);
        
        $taxes = $price->getCalculatedTaxes();
        $this->assertCount(1, $taxes);

        $tax = $taxes->first();
        $this->assertNotNull($tax);
        
        // Should remain unchanged
        $this->assertEquals(20.0, $tax->getTaxRate());
        $this->assertEquals(20.0, $tax->getTax());
    }

    private function createCartWithTax(): Cart
    {
        $cart = new Cart('test-cart');
        
        $tax = new CalculatedTax(20.0, 20.0, 100.0);
        $taxes = new CalculatedTaxCollection([$tax]);
        
        $price = new CartPrice(
            100.0,
            100.0,
            100.0,
            $taxes,
            new TaxRuleCollection(),
            'EUR'
        );
        
        $cart->setPrice($price);
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
