<?php declare(strict_types=1);

namespace AcademyCartExamples\Cart\Processor;

use AcademyCartExamples\Service\AcademyCartService;
use Shopware\Core\Checkout\Cart\Cart;
use Shopware\Core\Checkout\Cart\CartBehavior;
use Shopware\Core\Checkout\Cart\LineItem\CartDataCollection;
use Shopware\Core\Checkout\Cart\CartProcessorInterface;
use Shopware\Core\Checkout\Cart\Tax\Struct\CalculatedTaxCollection;
use Shopware\Core\System\SalesChannel\SalesChannelContext;

class CustomTaxProcessor implements CartProcessorInterface
{
    public function __construct(
        private readonly AcademyCartService $academyCartService
    ) {
    }

    public function process(
        CartDataCollection $data,
        Cart $original,
        Cart $toCalculate,
        SalesChannelContext $context,
        CartBehavior $behavior
    ): void {
        $price = $toCalculate->getPrice();
        $calculatedTaxes = $price->getCalculatedTaxes();
        $newCalculatedTaxes = new CalculatedTaxCollection();

        foreach ($calculatedTaxes as $tax) {
            $adjustedTax = $this->academyCartService->adjustTaxForB2B($tax, $context);
            $newCalculatedTaxes->add($adjustedTax);
        }

        $price->setCalculatedTaxes($newCalculatedTaxes);
    }


}
