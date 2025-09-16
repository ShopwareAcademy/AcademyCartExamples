<?php declare(strict_types=1);

namespace AcademyCartExamples\Cart;

use AcademyCartExamples\Cart\Error\MinimumOrderValueError;
use Shopware\Core\Checkout\Cart\Cart;
use Shopware\Core\Checkout\Cart\CartValidatorInterface;
use Shopware\Core\Checkout\Cart\Error\ErrorCollection;
use Shopware\Core\System\SalesChannel\SalesChannelContext;

class MinimumOrderValueValidator implements CartValidatorInterface
{
    public function validate(Cart $cart, ErrorCollection $errors, SalesChannelContext $context): void
    {
        // Only validate for logged-in B2B customers
        $customer = $context->getCustomer();
        if (!$customer || !$this->isB2BCustomer($context)) {
            return;
        }

        $total = $cart->getPrice()->getTotalPrice();
        $minimumValue = 100.00; // €100 minimum for B2B

        if ($total < $minimumValue) {
            $missing = $minimumValue - $total;
            $errors->add(new MinimumOrderValueError($total, $minimumValue, $missing));
        }
    }

    private function isB2BCustomer(SalesChannelContext $context): bool
    {
        // Check if customer has B2B role or company
        $customer = $context->getCustomer();
        return $customer && $customer->getCompany() !== null;
    }
}
