<?php declare(strict_types=1);

namespace AcademyCartExamples\Test\Cart;

use AcademyCartExamples\Cart\Error\MinimumOrderValueError;
use AcademyCartExamples\Cart\MinimumOrderValueValidator;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Checkout\Cart\Cart;
use Shopware\Core\Checkout\Cart\Error\ErrorCollection;
use Shopware\Core\Checkout\Cart\LineItem\LineItem;
use Shopware\Core\Checkout\Cart\Price\Struct\CartPrice;
use Shopware\Core\Checkout\Cart\Tax\Struct\CalculatedTaxCollection;
use Shopware\Core\Checkout\Cart\Tax\Struct\TaxRuleCollection;
use Shopware\Core\Checkout\Customer\CustomerEntity;
use Shopware\Core\System\SalesChannel\SalesChannelContext;

class SimpleMinimumOrderValueValidatorTest extends TestCase
{
    private MinimumOrderValueValidator $validator;

    protected function setUp(): void
    {
        $this->validator = new MinimumOrderValueValidator();
    }

    public function testValidatesB2BCustomerMinimumOrder(): void
    {
        $cart = $this->createCartWithTotal(50.00); // Below minimum
        $context = $this->createB2BContext();
        $errors = new ErrorCollection();

        $this->validator->validate($cart, $errors, $context);

        $this->assertTrue($errors->count() > 0);
        $this->assertCount(1, $errors);
        
        $error = $errors->first();
        $this->assertInstanceOf(MinimumOrderValueError::class, $error);
        $this->assertEquals('academy-minimum-order-value', $error->getId());
    }

    public function testIgnoresRegularCustomers(): void
    {
        $cart = $this->createCartWithTotal(50.00);
        $context = $this->createRegularCustomerContext();
        $errors = new ErrorCollection();

        $this->validator->validate($cart, $errors, $context);

        $this->assertCount(0, $errors);
    }

    public function testAllowsB2BCustomerAboveMinimum(): void
    {
        $cart = $this->createCartWithTotal(150.00); // Above minimum
        $context = $this->createB2BContext();
        $errors = new ErrorCollection();

        $this->validator->validate($cart, $errors, $context);

        $this->assertCount(0, $errors);
    }

    private function createCartWithTotal(float $total): Cart
    {
        $cart = new Cart('test-cart');
        $cart->setPrice(new CartPrice(
            $total, // netPrice
            $total, // totalPrice
            $total, // positionPrice
            new CalculatedTaxCollection(), // calculatedTaxes
            new TaxRuleCollection(), // taxRules
            CartPrice::TAX_STATE_GROSS // taxStatus
        ));
        
        return $cart;
    }

    private function createB2BContext(): SalesChannelContext
    {
        $customer = new CustomerEntity();
        $customer->setId('test-customer');
        $customer->setCompany('Test Company');
        
        $context = $this->createMock(SalesChannelContext::class);
        $context->method('getCustomer')->willReturn($customer);
        
        return $context;
    }

    private function createRegularCustomerContext(): SalesChannelContext
    {
        $customer = new CustomerEntity();
        $customer->setId('test-customer');
        // No company set = regular customer
        
        $context = $this->createMock(SalesChannelContext::class);
        $context->method('getCustomer')->willReturn($customer);
        
        return $context;
    }
}
