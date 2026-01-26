<?php declare(strict_types=1);

namespace AcademyCartExamples\Service;

use Shopware\Core\Checkout\Cart\Cart;
use Shopware\Core\Checkout\Cart\Delivery\Struct\Delivery;
use Shopware\Core\Checkout\Cart\Error\Error;
use Shopware\Core\Checkout\Cart\Error\ErrorCollection;
use Shopware\Core\Checkout\Cart\Error\GenericCartError;
use Shopware\Core\Checkout\Cart\LineItem\LineItem;
use Shopware\Core\Checkout\Cart\LineItem\LineItemCollection;
use Shopware\Core\Checkout\Cart\Price\Struct\CalculatedPrice;
use Shopware\Core\Checkout\Cart\Tax\Struct\CalculatedTax;
use Shopware\Core\Checkout\Cart\Tax\Struct\CalculatedTaxCollection;
use Shopware\Core\Checkout\Cart\Tax\Struct\TaxRuleCollection;
use Shopware\Core\System\SalesChannel\SalesChannelContext;

class AcademyCartService
{
    public const float MINIMUM_ORDER_VALUE = 100.00;

    private const float B2B_FREE_SHIPPING_THRESHOLD = 200.00;
    private const float B2B_TAX_RATE_REDUCTION = 0.8;

    private const float SHIPPING_COST_WEIGHT_LIGHTEST = 1.0;
    private const float SHIPPING_COST_WEIGHT_LIGHT = 5.0;
    private const float SHIPPING_COST_WEIGHT_HEAVIEST = 10.0;
    private const float SHIPPING_COST_PRICE_LIGHTEST = 5.99;
    private const float SHIPPING_COST_PRICE_LIGHT = 9.99;
    private const float SHIPPING_COST_PRICE_HEAVIEST = 14.99;
    private const float SHIPPING_COST_PRICE_DEFAULT = 19.99;

    private const int QUANTITY_OVER_HUNDRED = 100;
    private const int QUANTITY_OVER_FIFTY = 50;
    private const int QUANTITY_OVER_TWENTY = 20;
    private const float BULK_DISCOUNT_PERCENTAGE_FIFTEEN = 15.0;
    private const float BULK_DISCOUNT_PERCENTAGE_TEN = 10.0;
    private const float BULK_DISCOUNT_PERCENTAGE_FIVE = 5.0;

    public function isB2BCustomer(SalesChannelContext $context): bool
    {
        // Check if customer has B2B role or company
        $customer = $context->getCustomer();
        if (null === $customer) {
            return false;
        }

        if (null === $customer->getCompany()) {
            return false;
        }

        return true;
    }

    public function calculateTotalValue(LineItemCollection $lineItems): float
    {
        $total = 0.0;
        foreach ($lineItems as $lineItem) {
            if ($lineItem->getType() === LineItem::PRODUCT_LINE_ITEM_TYPE) {
                $price = $lineItem->getPrice();
                if ($price) {
                    $total += $price->getTotalPrice();
                }
            }
        }
        return $total;
    }

    public function addB2BDiscount(Cart $cart, float $totalValue): void
    {
        $discountAmount = $totalValue * 0.05; // 5% additional discount

        $discountLineItem = new LineItem(
            'b2b-bonus-discount',
            LineItem::PROMOTION_LINE_ITEM_TYPE
        );

        $discountLineItem->setLabel('B2B Bonus Discount');
        $discountLineItem->setPrice(new CalculatedPrice(
            -$discountAmount,
            -$discountAmount,
            new CalculatedTaxCollection(),
            new TaxRuleCollection()
        ));

        $cart->add($discountLineItem);
    }

    public function calculateTotalQuantity(LineItemCollection $lineItems): int
    {
        $totalQuantity = 0;
        foreach ($lineItems as $lineItem) {
            if ($lineItem->getType() === LineItem::PRODUCT_LINE_ITEM_TYPE) {
                $totalQuantity += $lineItem->getQuantity();
            }
        }
        return $totalQuantity;
    }

    public function getBulkDiscountPercentage(int $quantity): float
    {
        if ($quantity >= self::QUANTITY_OVER_HUNDRED) {
            return self::BULK_DISCOUNT_PERCENTAGE_FIFTEEN; // 15% for 100+ items
        }

        if ($quantity >= self::QUANTITY_OVER_FIFTY) {
            return self::BULK_DISCOUNT_PERCENTAGE_TEN; // 10% for 50+ items
        }

        if ($quantity >= self::QUANTITY_OVER_TWENTY) {
            return self::BULK_DISCOUNT_PERCENTAGE_FIVE; // 5% for 20+ items
        }

        return 0.0;
    }

    public function applyBulkDiscount(LineItemCollection $lineItems, float $discountPercentage): void
    {
        foreach ($lineItems as $lineItem) {
            if ($lineItem->getType() === LineItem::PRODUCT_LINE_ITEM_TYPE) {
                $this->applyDiscountToLineItem($lineItem, $discountPercentage);
            }
        }
    }

    public function validateProductLineItem(LineItem $lineItem, ErrorCollection $errors): void
    {
        // Check if product is still available
        if (false === $this->isProductAvailable($lineItem->getReferencedId())) {
            $errors->add(new GenericCartError(
                'ACADEMY_PRODUCT_UNAVAILABLE',
                'Product is no longer available',
                ['productId' => $lineItem->getReferencedId()],
                Error::LEVEL_ERROR,
                true,  // blockOrder
                false, // persistent
                false  // blockResubmit
            ));
        }
    }

    public function applyCustomShippingRules(
        Delivery            $delivery,
        Cart                $cart,
        SalesChannelContext $context
    ): void
    {
        $totalWeight = $this->calculateTotalWeight($cart);
        $shippingCost = $this->calculateShippingCost($totalWeight);

        // Apply free shipping for B2B customers over €200
        if (true === $this->isB2BCustomer($context)
            && $cart->getPrice()->getTotalPrice() > self::B2B_FREE_SHIPPING_THRESHOLD
        ) {
            $shippingCost = 0.00;
        }

        $delivery->setShippingCosts(new CalculatedPrice(
            $shippingCost,
            $shippingCost,
            new CalculatedTaxCollection(),
            new TaxRuleCollection()
        ));
    }

    public function adjustTaxForB2B(CalculatedTax $tax, SalesChannelContext $context): CalculatedTax
    {
        // B2B customers might have different tax treatment
        if (true === $this->isB2BCustomer($context)) {
            // Example: Apply reduced tax rate for B2B
            $reducedRate = $tax->getTaxRate() * self::B2B_TAX_RATE_REDUCTION; // 20% reduction
            $reducedAmount = $tax->getTax() * self::B2B_TAX_RATE_REDUCTION;

            return new CalculatedTax(
                $reducedAmount,
                $reducedRate,
                $tax->getPrice()
            );
        }

        return $tax;
    }


    private function calculateTotalWeight(Cart $cart): float
    {
        $totalWeight = 0.0;
        foreach ($cart->getLineItems() as $lineItem) {
            if ($lineItem->getType() === LineItem::PRODUCT_LINE_ITEM_TYPE) {
                $weight = $lineItem->getPayloadValue('weight');
                $totalWeight += $weight * $lineItem->getQuantity();
            }
        }
        return $totalWeight;
    }

    private function calculateShippingCost(float $weight): float
    {
        // Custom shipping calculation based on weight
        if ($weight <= self::SHIPPING_COST_WEIGHT_LIGHTEST) {
            return self::SHIPPING_COST_PRICE_LIGHTEST;
        }

        if ($weight <= self::SHIPPING_COST_WEIGHT_LIGHT) {
            return self::SHIPPING_COST_PRICE_LIGHT;
        }

        if ($weight <= self::SHIPPING_COST_WEIGHT_HEAVIEST) {
            return self::SHIPPING_COST_PRICE_HEAVIEST;
        }

        return self::SHIPPING_COST_PRICE_DEFAULT; // Heavy items
    }

    private function applyDiscountToLineItem(LineItem $lineItem, float $discountPercentage): void
    {
        $price = $lineItem->getPrice();
        if (null === $price) {
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

    private function isProductAvailable(string $productId): bool
    {
        // In a real implementation, you would check the product's availability
        // For this example, we'll simulate some products being unavailable
        $unavailableProducts = ['unavailable-product-1', 'discontinued-product-2'];

        if (true === in_array($productId, $unavailableProducts, true)) {
            return false;
        }

        return true;
    }
}
