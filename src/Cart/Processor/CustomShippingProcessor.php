<?php declare(strict_types=1);

namespace AcademyCartExamples\Cart\Processor;

use AcademyCartExamples\Service\AcademyCartService;
use Shopware\Core\Checkout\Cart\Cart;
use Shopware\Core\Checkout\Cart\CartBehavior;
use Shopware\Core\Checkout\Cart\LineItem\CartDataCollection;
use Shopware\Core\Checkout\Cart\CartProcessorInterface;
use Shopware\Core\System\SalesChannel\SalesChannelContext;

class CustomShippingProcessor implements CartProcessorInterface
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
        $deliveries = $toCalculate->getDeliveries();
        
        foreach ($deliveries as $delivery) {
            $this->academyCartService->applyCustomShippingRules($delivery, $toCalculate, $context);
        }
    }
}
