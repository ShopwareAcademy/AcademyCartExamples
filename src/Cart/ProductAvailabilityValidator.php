<?php declare(strict_types=1);

namespace AcademyCartExamples\Cart;

use AcademyCartExamples\Service\AcademyCartService;
use Shopware\Core\Checkout\Cart\Cart;
use Shopware\Core\Checkout\Cart\CartValidatorInterface;
use Shopware\Core\Checkout\Cart\Error\ErrorCollection;
use Shopware\Core\Checkout\Cart\LineItem\LineItem;
use Shopware\Core\System\SalesChannel\SalesChannelContext;

class ProductAvailabilityValidator implements CartValidatorInterface
{
    public function __construct(
        private readonly AcademyCartService $academyCartService
    ) {
    }

    public function validate(Cart $cart, ErrorCollection $errors, SalesChannelContext $context): void
    {
        // Only validate if cart has line items
        if (empty($cart->getLineItems()->getElements())) {
            return;
        }

        foreach ($cart->getLineItems() as $lineItem) {
            if ($lineItem->getType() === LineItem::PRODUCT_LINE_ITEM_TYPE) {
                $this->academyCartService->validateProductLineItem($lineItem, $errors);
            }
        }
    }
}
