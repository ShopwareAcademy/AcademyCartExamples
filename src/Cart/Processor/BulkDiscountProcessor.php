<?php declare(strict_types=1);

namespace AcademyCartExamples\Cart\Processor;

use AcademyCartExamples\Service\AcademyCartService;
use Shopware\Core\Checkout\Cart\Cart;
use Shopware\Core\Checkout\Cart\CartBehavior;
use Shopware\Core\Checkout\Cart\LineItem\CartDataCollection;
use Shopware\Core\Checkout\Cart\CartProcessorInterface;
use Shopware\Core\System\SalesChannel\SalesChannelContext;

class BulkDiscountProcessor implements CartProcessorInterface
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
        // Only apply to B2B customers
        if (false === $this->academyCartService->isB2BCustomer($context)) {
            return;
        }

        $lineItems = $toCalculate->getLineItems();
        $totalQuantity = $this->academyCartService->calculateTotalQuantity($lineItems);

        // Apply bulk discount based on quantity
        $discountPercentage = $this->academyCartService->getBulkDiscountPercentage($totalQuantity);

        if ($discountPercentage > 0) {
            $this->academyCartService->applyBulkDiscount($lineItems, $discountPercentage);
        }
    }
}
