<?php declare(strict_types=1);

namespace AcademyCartExamples\Cart\Processor;

use Shopware\Core\Checkout\Cart\Cart;
use Shopware\Core\Checkout\Cart\CartBehavior;
use Shopware\Core\Checkout\Cart\LineItem\CartDataCollection;
use Shopware\Core\Checkout\Cart\CartProcessorInterface;
use Shopware\Core\Checkout\Cart\LineItem\LineItemCollection;
use Shopware\Core\Checkout\Cart\LineItem\LineItem;
use Shopware\Core\Checkout\Cart\Price\Struct\CalculatedPrice;
use Shopware\Core\Checkout\Cart\Tax\Struct\CalculatedTaxCollection;
use Shopware\Core\Checkout\Cart\Tax\Struct\TaxRuleCollection;
use Shopware\Core\System\SalesChannel\SalesChannelContext;

class BulkDiscountProcessor implements CartProcessorInterface
{
    public function process(
        CartDataCollection $data,
        Cart $original,
        Cart $toCalculate,
        SalesChannelContext $context,
        CartBehavior $behavior
    ): void {
        // Only apply to B2B customers
        if (!$this->isB2BCustomer($context)) {
            return;
        }

        $lineItems = $toCalculate->getLineItems();
        $totalQuantity = $this->calculateTotalQuantity($lineItems);
        
        // Apply bulk discount based on quantity
        $discountPercentage = $this->getBulkDiscountPercentage($totalQuantity);
        
        if ($discountPercentage > 0) {
            $this->applyBulkDiscount($lineItems, $discountPercentage, $context);
        }
    }

    private function isB2BCustomer(SalesChannelContext $context): bool
    {
        $customer = $context->getCustomer();
        return $customer && $customer->getCompany() !== null;
    }

    private function calculateTotalQuantity(LineItemCollection $lineItems): int
    {
        $totalQuantity = 0;
        foreach ($lineItems as $lineItem) {
            if ($lineItem->getType() === LineItem::PRODUCT_LINE_ITEM_TYPE) {
                $totalQuantity += $lineItem->getQuantity();
            }
        }
        return $totalQuantity;
    }

    private function getBulkDiscountPercentage(int $quantity): float
    {
        if ($quantity >= 100) return 15.0;  // 15% for 100+ items
        if ($quantity >= 50) return 10.0;   // 10% for 50+ items
        if ($quantity >= 20) return 5.0;    // 5% for 20+ items
        
        return 0.0;
    }

    private function applyBulkDiscount(
        LineItemCollection $lineItems,
        float $discountPercentage,
        SalesChannelContext $context
    ): void {
        foreach ($lineItems as $lineItem) {
            if ($lineItem->getType() === LineItem::PRODUCT_LINE_ITEM_TYPE) {
                $this->applyDiscountToLineItem($lineItem, $discountPercentage, $context);
            }
        }
    }

    private function applyDiscountToLineItem(
        LineItem $lineItem,
        float $discountPercentage,
        SalesChannelContext $context
    ): void {
        $price = $lineItem->getPrice();
        if (!$price) {
            return;
        }

        $originalUnitPrice = $price->getUnitPrice();
        $discountedUnitPrice = $originalUnitPrice * (1 - $discountPercentage / 100);
        
        $newPrice = new CalculatedPrice(
            $discountedUnitPrice,
            $discountedUnitPrice * $lineItem->getQuantity(),
            new CalculatedTaxCollection(),
            new TaxRuleCollection()
        );

        $lineItem->setPrice($newPrice);
    }
}
