<?php declare(strict_types=1);

namespace AcademyCartExamples\Service;

use Shopware\Core\Checkout\Cart\Cart;
use Shopware\Core\Checkout\Cart\Price\QuantityPriceCalculator;
use Shopware\Core\Checkout\Cart\Price\Struct\QuantityPriceDefinition;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Core\Checkout\Cart\LineItem\LineItem;
use Shopware\Core\Checkout\Cart\LineItem\LineItemCollection;
use Shopware\Core\Checkout\Cart\Tax\Struct\TaxRuleCollection;

class AcademyCartService
{
    public const string B2B_DISCOUNT_ID = 'academy-b2b-bonus-discount';

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
        $discountLineItem = new LineItem(
            self::B2B_DISCOUNT_ID,
            LineItem::DISCOUNT_LINE_ITEM
        );

        $discountLineItem->setLabel('B2B Bonus Discount');
        $discountLineItem->setGood(false); // Not a physical product
        $discountLineItem->setStackable(false); // Not stackable

        $discountAmount = $totalValue * 0.05; // 5% additional discount

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
}