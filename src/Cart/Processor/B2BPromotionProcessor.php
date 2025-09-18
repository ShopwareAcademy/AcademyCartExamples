<?php declare(strict_types=1);

namespace AcademyCartExamples\Cart\Processor;

use Shopware\Core\Checkout\Cart\Cart;
use Shopware\Core\Checkout\Cart\CartBehavior;
use Shopware\Core\Checkout\Cart\LineItem\CartDataCollection;
use Shopware\Core\Checkout\Cart\CartProcessorInterface;
use Shopware\Core\Checkout\Cart\LineItem\LineItem;
use Shopware\Core\Checkout\Cart\LineItem\LineItemCollection;
use Shopware\Core\Checkout\Cart\Price\Struct\CalculatedPrice;
use Shopware\Core\Checkout\Cart\Tax\Struct\CalculatedTaxCollection;
use Shopware\Core\Checkout\Cart\Tax\Struct\TaxRuleCollection;
use Shopware\Core\System\SalesChannel\SalesChannelContext;

class B2BPromotionProcessor implements CartProcessorInterface
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
        $totalValue = $this->calculateTotalValue($lineItems);

        // Apply additional 5% B2B discount for orders over €500
        if ($totalValue > 500.00) {
            $this->addB2BDiscount($toCalculate, $totalValue);
        }
    }

    private function addB2BDiscount(Cart $cart, float $totalValue): void
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

    private function isB2BCustomer(SalesChannelContext $context): bool
    {
        $customer = $context->getCustomer();
        return $customer && $customer->getCompany() !== null;
    }

    private function calculateTotalValue(LineItemCollection $lineItems): float
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
}
