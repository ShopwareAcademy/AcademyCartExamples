<?php declare(strict_types=1);

namespace AcademyCartExamples\Test\Cart\Processor;

use AcademyCartExamples\Cart\Processor\CustomShippingProcessor;
use AcademyCartExamples\Service\AcademyCartService;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Checkout\Cart\Cart;
use Shopware\Core\Checkout\Cart\CartBehavior;
use Shopware\Core\Checkout\Cart\Delivery\Struct\Delivery;
use Shopware\Core\Checkout\Cart\Delivery\Struct\DeliveryCollection;
use Shopware\Core\Checkout\Cart\Delivery\Struct\DeliveryDate;
use Shopware\Core\Checkout\Cart\Delivery\Struct\DeliveryPositionCollection;
use Shopware\Core\Checkout\Cart\Delivery\Struct\ShippingLocation;
use Shopware\Core\Checkout\Customer\CustomerEntity;
use Shopware\Core\Checkout\Shipping\ShippingMethodEntity;
use Shopware\Core\Checkout\Cart\LineItem\CartDataCollection;
use Shopware\Core\Checkout\Cart\LineItem\LineItem;
use Shopware\Core\Checkout\Cart\Price\Struct\CalculatedPrice;
use Shopware\Core\Checkout\Cart\Price\Struct\CartPrice;
use Shopware\Core\Checkout\Cart\Tax\Struct\CalculatedTaxCollection;
use Shopware\Core\Checkout\Cart\Tax\Struct\TaxRuleCollection;
use Shopware\Core\Framework\Test\TestCaseBase\KernelTestBehaviour;
use Shopware\Core\System\SalesChannel\SalesChannelContext;

class CustomShippingProcessorTest extends TestCase
{
    use KernelTestBehaviour;

    private CustomShippingProcessor $processor;
    private AcademyCartService $academyCartService;

    protected function setUp(): void
    {
        $this->processor = new CustomShippingProcessor($this->academyCartService);
    }

    public function testAppliesFreeShippingForB2BHighValueOrders(): void
    {
        $cart = $this->createCartWithValue(250.00); // Over €200 threshold
        $delivery = $this->createDelivery();
        $cart->setDeliveries(new DeliveryCollection([$delivery]));
        
        $context = $this->createB2BContext();
        $data = new CartDataCollection();
        $behavior = new CartBehavior();

        $this->processor->process($data, $cart, $cart, $context, $behavior);

        $shippingCosts = $delivery->getShippingCosts();
        $this->assertNotNull($shippingCosts);
        $this->assertEquals(0.00, $shippingCosts->getUnitPrice());
    }

    public function testCalculatesShippingBasedOnWeight(): void
    {
        $cart = $this->createCartWithWeight(3.0); // 3kg
        $delivery = $this->createDelivery();
        $cart->setDeliveries(new DeliveryCollection([$delivery]));
        
        $context = $this->createRegularCustomerContext();
        $data = new CartDataCollection();
        $behavior = new CartBehavior();

        $this->processor->process($data, $cart, $cart, $context, $behavior);

        $shippingCosts = $delivery->getShippingCosts();
        $this->assertNotNull($shippingCosts);
        $this->assertEquals(9.99, $shippingCosts->getUnitPrice()); // 3kg = €9.99
    }

    public function testCalculatesShippingForHeavyItems(): void
    {
        $cart = $this->createCartWithWeight(15.0); // 15kg
        $delivery = $this->createDelivery();
        $cart->setDeliveries(new DeliveryCollection([$delivery]));
        
        $context = $this->createRegularCustomerContext();
        $data = new CartDataCollection();
        $behavior = new CartBehavior();

        $this->processor->process($data, $cart, $cart, $context, $behavior);

        $shippingCosts = $delivery->getShippingCosts();
        $this->assertNotNull($shippingCosts);
        $this->assertEquals(19.99, $shippingCosts->getUnitPrice()); // 15kg = €19.99
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
        
        // Set cart price for the processor to work correctly
        $cartPrice = new CartPrice(
            $value,
            $value,
            $value,
            new CalculatedTaxCollection(),
            new TaxRuleCollection(),
            'EUR'
        );
        $cart->setPrice($cartPrice);
        
        return $cart;
    }

    private function createCartWithWeight(float $weight): Cart
    {
        $cart = new Cart('test-cart');
        
        $lineItem = new LineItem('test-product', LineItem::PRODUCT_LINE_ITEM_TYPE);
        $lineItem->setStackable(true);
        $lineItem->setQuantity(1);
        $lineItem->setPayloadValue('weight', $weight);
        $lineItem->setPrice(new CalculatedPrice(
            100.00,
            100.00,
            new CalculatedTaxCollection(),
            new TaxRuleCollection()
        ));
        
        $cart->add($lineItem);
        return $cart;
    }

    private function createDelivery(): Delivery
    {
        $shippingMethod = $this->createMock(ShippingMethodEntity::class);
        $shippingLocation = $this->createMock(ShippingLocation::class);
        $shippingCosts = new CalculatedPrice(
            0.0, // unit price
            0.0, // total price
            new CalculatedTaxCollection(),
            new TaxRuleCollection()
        );
        
        return new Delivery(
            new DeliveryPositionCollection(),
            new DeliveryDate(new \DateTime(), new \DateTime()),
            $shippingMethod,
            $shippingLocation,
            $shippingCosts
        );
    }

    private function createB2BContext(): SalesChannelContext
    {
        $customer = $this->createMock(CustomerEntity::class);
        $customer->method('getCompany')->willReturn('Test Company');
        
        $context = $this->createMock(SalesChannelContext::class);
        $context->method('getCustomer')->willReturn($customer);
        
        return $context;
    }

    private function createRegularCustomerContext(): SalesChannelContext
    {
        $customer = $this->createMock(CustomerEntity::class);
        $customer->method('getCompany')->willReturn(null);
        
        $context = $this->createMock(SalesChannelContext::class);
        $context->method('getCustomer')->willReturn($customer);
        
        return $context;
    }
}
