<?php declare(strict_types=1);

namespace AcademyCartExamples\Service;

use Shopware\Core\Checkout\Cart\LineItem\LineItem;
use Shopware\Core\Checkout\Cart\LineItem\LineItemCollection;
use Shopware\Core\Checkout\Cart\Price\Struct\CalculatedPrice;
use Shopware\Core\Checkout\Cart\Tax\Struct\CalculatedTaxCollection;
use Shopware\Core\Checkout\Cart\Tax\Struct\TaxRuleCollection;
use Shopware\Core\System\SalesChannel\SalesChannelContext;

class AcademyCartService
{
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
}