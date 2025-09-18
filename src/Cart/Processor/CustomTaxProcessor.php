<?php declare(strict_types=1);

namespace AcademyCartExamples\Cart\Processor;

use Shopware\Core\Checkout\Cart\Cart;
use Shopware\Core\Checkout\Cart\CartBehavior;
use Shopware\Core\Checkout\Cart\LineItem\CartDataCollection;
use Shopware\Core\Checkout\Cart\CartProcessorInterface;
use Shopware\Core\Checkout\Cart\Price\Struct\CartPrice;
use Shopware\Core\Checkout\Cart\Tax\Struct\CalculatedTax;
use Shopware\Core\Checkout\Cart\Tax\Struct\CalculatedTaxCollection;
use Shopware\Core\System\SalesChannel\SalesChannelContext;

class CustomTaxProcessor implements CartProcessorInterface
{
    public function process(
        CartDataCollection $data,
        Cart $original,
        Cart $toCalculate,
        SalesChannelContext $context,
        CartBehavior $behavior
    ): void {
        $price = $toCalculate->getPrice();
        if (!$price) {
            return;
        }

        $calculatedTaxes = $price->getCalculatedTaxes();
        $newCalculatedTaxes = new CalculatedTaxCollection();

        foreach ($calculatedTaxes as $tax) {
            $adjustedTax = $this->adjustTaxForB2B($tax, $context);
            $newCalculatedTaxes->add($adjustedTax);
        }

        $price->setCalculatedTaxes($newCalculatedTaxes);
    }

    private function adjustTaxForB2B(CalculatedTax $tax, SalesChannelContext $context): CalculatedTax
    {
        // B2B customers might have different tax treatment
        if ($this->isB2BCustomer($context)) {
            // Example: Apply reduced tax rate for B2B
            $reducedRate = $tax->getTaxRate() * 0.8; // 20% reduction
            $reducedAmount = $tax->getTax() * 0.8;
            
            return new CalculatedTax(
                $reducedAmount,
                $reducedRate,
                $tax->getPrice()
            );
        }

        return $tax;
    }

    private function isB2BCustomer(SalesChannelContext $context): bool
    {
        $customer = $context->getCustomer();
        return $customer && $customer->getCompany() !== null;
    }
}
