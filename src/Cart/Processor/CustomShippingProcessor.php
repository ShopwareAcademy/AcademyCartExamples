<?php declare(strict_types=1);

namespace AcademyCartExamples\Cart\Processor;

use Shopware\Core\Checkout\Cart\Cart;
use Shopware\Core\Checkout\Cart\CartBehavior;
use Shopware\Core\Checkout\Cart\Delivery\Struct\Delivery;
use Shopware\Core\Checkout\Cart\LineItem\CartDataCollection;
use Shopware\Core\Checkout\Cart\CartProcessorInterface;
use Shopware\Core\Checkout\Cart\LineItem\LineItem;
use Shopware\Core\Checkout\Cart\Price\Struct\CalculatedPrice;
use Shopware\Core\Checkout\Cart\Tax\Struct\CalculatedTaxCollection;
use Shopware\Core\Checkout\Cart\Tax\Struct\TaxRuleCollection;
use Shopware\Core\System\SalesChannel\SalesChannelContext;

class CustomShippingProcessor implements CartProcessorInterface
{
    public function process(
        CartDataCollection $data,
        Cart $original,
        Cart $toCalculate,
        SalesChannelContext $context,
        CartBehavior $behavior
    ): void {
        $deliveries = $toCalculate->getDeliveries();
        
        foreach ($deliveries as $delivery) {
            $this->applyCustomShippingRules($delivery, $toCalculate, $context);
        }
    }

    private function applyCustomShippingRules(
        Delivery $delivery,
        Cart $cart,
        SalesChannelContext $context
    ): void {
        $totalWeight = $this->calculateTotalWeight($cart);
        $shippingCost = $this->calculateShippingCost($totalWeight, $context);

        // Apply free shipping for B2B customers over €200
        if ($this->isB2BCustomer($context) && $cart->getPrice()->getTotalPrice() > 200.00) {
            $shippingCost = 0.00;
        }

        $delivery->setShippingCosts(new CalculatedPrice(
            $shippingCost,
            $shippingCost,
            new CalculatedTaxCollection(),
            new TaxRuleCollection()
        ));
    }

    private function calculateTotalWeight(Cart $cart): float
    {
        $totalWeight = 0.0;
        foreach ($cart->getLineItems() as $lineItem) {
            if ($lineItem->getType() === LineItem::PRODUCT_LINE_ITEM_TYPE) {
                $weight = $lineItem->getPayloadValue('weight', 0.0);
                $totalWeight += $weight * $lineItem->getQuantity();
            }
        }
        return $totalWeight;
    }

    private function calculateShippingCost(float $weight, SalesChannelContext $context): float
    {
        // Custom shipping calculation based on weight
        if ($weight <= 1.0) return 5.99;
        if ($weight <= 5.0) return 9.99;
        if ($weight <= 10.0) return 14.99;
        
        return 19.99; // Heavy items
    }

    private function isB2BCustomer(SalesChannelContext $context): bool
    {
        $customer = $context->getCustomer();
        return $customer && $customer->getCompany() !== null;
    }
}
