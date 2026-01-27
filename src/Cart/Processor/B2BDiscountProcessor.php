<?php declare(strict_types=1);

namespace AcademyCartExamples\Cart\Processor;

use AcademyCartExamples\Service\AcademyCartService;
use Shopware\Core\Checkout\Cart\Cart;
use Shopware\Core\Checkout\Cart\CartBehavior;
use Shopware\Core\Checkout\Cart\LineItem\CartDataCollection;
use Shopware\Core\Checkout\Cart\CartProcessorInterface;
use Shopware\Core\System\SalesChannel\SalesChannelContext;

class B2BDiscountProcessor implements CartProcessorInterface
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
        // Only apply to B2B customers; if not, remove discount
        if (false === $this->academyCartService->isB2BCustomer($context)) {
            if ($toCalculate->has(AcademyCartService::B2B_DISCOUNT_ID)) {
                $toCalculate->remove(AcademyCartService::B2B_DISCOUNT_ID);
            }

            return;
        }

        $totalValue = $this->academyCartService->calculateTotalValue($toCalculate->getLineItems());

        // Discount applies only for cart total above €500. Remove discount if below the threshold
        if ($totalValue <= 500.00) {
            if ($toCalculate->has(AcademyCartService::B2B_DISCOUNT_ID)) {
                $toCalculate->remove(AcademyCartService::B2B_DISCOUNT_ID);
            }
            return;
        }

        // Apply additional 5% B2B discount for orders over €500
        $this->academyCartService->addB2BDiscount($toCalculate, $totalValue, $context);
    }
}
