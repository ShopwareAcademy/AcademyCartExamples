<?php declare(strict_types=1);

namespace AcademyCartExamples\Cart;

use AcademyCartExamples\Cart\Error\MinimumOrderValueError;
use AcademyCartExamples\Service\AcademyCartService;
use Shopware\Core\Checkout\Cart\Cart;
use Shopware\Core\Checkout\Cart\CartValidatorInterface;
use Shopware\Core\Checkout\Cart\Error\ErrorCollection;
use Shopware\Core\System\SalesChannel\SalesChannelContext;

class MinimumOrderValueValidator implements CartValidatorInterface
{
    public function __construct(
        private readonly AcademyCartService $academyCartService
    ) {
    }

    public function validate(Cart $cart, ErrorCollection $errors, SalesChannelContext $context): void
    {
        // Only validate for logged-in B2B customers
        $isB2BCustomer = $this->academyCartService->isB2BCustomer($context);
        if (false === $isB2BCustomer) {
            return;
        }

        $total = $cart->getPrice()->getTotalPrice();
        $minimumValue = AcademyCartService::MINIMUM_ORDER_VALUE; // €100 minimum for B2B

        if ($total < $minimumValue) {
            $missing = $minimumValue - $total;
            $errors->add(new MinimumOrderValueError($total, $minimumValue, $missing));
        }
    }
}
