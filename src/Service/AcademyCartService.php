<?php declare(strict_types=1);

namespace AcademyCartExamples\Service;

use Shopware\Core\Checkout\Cart\Cart;
use Shopware\Core\Checkout\Cart\Error\Error;
use Shopware\Core\Checkout\Cart\Error\ErrorCollection;
use Shopware\Core\Checkout\Cart\Error\GenericCartError;
use Shopware\Core\Checkout\Cart\LineItem\LineItem;
use Shopware\Core\Checkout\Cart\LineItem\LineItemCollection;
use Shopware\Core\Checkout\Cart\Price\QuantityPriceCalculator;
use Shopware\Core\Checkout\Cart\Price\Struct\CalculatedPrice;
use Shopware\Core\Checkout\Cart\Price\Struct\QuantityPriceDefinition;
use Shopware\Core\Checkout\Cart\Tax\Struct\CalculatedTaxCollection;
use Shopware\Core\Checkout\Cart\Tax\Struct\TaxRuleCollection;
use Shopware\Core\System\SalesChannel\SalesChannelContext;

class AcademyCartService
{
    public const float MINIMUM_ORDER_VALUE = 100.00;
    public const string B2B_DISCOUNT_ID = 'b2b-bonus-discount';

    private const string B2B_DISCOUNT_LINE_ITEM_TYPE = 'b2b-bonus-discount';

    private const int QUANTITY_OVER_HUNDRED = 100;
    private const int QUANTITY_OVER_FIFTY = 50;
    private const int QUANTITY_OVER_TWENTY = 20;
    private const float BULK_DISCOUNT_PERCENTAGE_FIFTEEN = 15.0;
    private const float BULK_DISCOUNT_PERCENTAGE_TEN = 10.0;
    private const float BULK_DISCOUNT_PERCENTAGE_FIVE = 5.0;

    public function __construct(
      private readonly QuantityPriceCalculator $quantityPriceCalculator
    ) {
    }

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
            if ($lineItem->getType() !== LineItem::PRODUCT_LINE_ITEM_TYPE) {
                continue;
            }


            $price = $lineItem->getPrice();
            if (null === $price) {
                continue;
            }

            $total += $price->getTotalPrice();
        }

        return $total;
    }

    public function addB2BDiscount(Cart $toCalculate, float $totalValue, SalesChannelContext $context): void
    {
        $discountAmount = $totalValue * 0.05; // 5% additional discount

        $discountLineItem = new LineItem(
            self::B2B_DISCOUNT_ID,
            self::B2B_DISCOUNT_LINE_ITEM_TYPE
        );

        $discountLineItem->setLabel('B2B Bonus Discount');
        $discountLineItem->setGood(false);
        $discountLineItem->setStackable(false);
        $discountLineItem->setRemovable(false);

        $priceDefinition = new QuantityPriceDefinition(
            -$discountAmount,
            new TaxRuleCollection(),
            1
        );

        $discountLineItem->setPriceDefinition($priceDefinition);

        $calculatedPrice = $this->quantityPriceCalculator->calculate($priceDefinition, $context);

        $discountLineItem->setPrice($calculatedPrice);

        $toCalculate->add($discountLineItem);
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
