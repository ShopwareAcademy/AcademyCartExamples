<?php declare(strict_types=1);

namespace AcademyCartExamples\Cart;

use Shopware\Core\Checkout\Cart\Cart;
use Shopware\Core\Checkout\Cart\CartValidatorInterface;
use Shopware\Core\Checkout\Cart\Error\Error;
use Shopware\Core\Checkout\Cart\Error\ErrorCollection;
use Shopware\Core\Checkout\Cart\Error\GenericCartError;
use Shopware\Core\Checkout\Cart\LineItem\LineItem;
use Shopware\Core\System\SalesChannel\SalesChannelContext;

class ProductAvailabilityValidator implements CartValidatorInterface
{
    public function validate(Cart $cart, ErrorCollection $errors, SalesChannelContext $context): void
    {
        // Only validate if cart has line items
        if ($cart->getLineItems()->count() === 0) {
            return;
        }

        foreach ($cart->getLineItems() as $lineItem) {
            if ($lineItem->getType() === 'product') {
                $this->validateProductLineItem($lineItem, $errors, $context);
            }
        }
    }

    private function validateProductLineItem(LineItem $lineItem, ErrorCollection $errors, SalesChannelContext $context): void
    {
        // Check if product is still available
        if (!$this->isProductAvailable($lineItem->getReferencedId(), $context)) {
            $errors->add(new GenericCartError(
                'ACADEMY_PRODUCT_UNAVAILABLE',
                'Product is no longer available',
                ['productId' => $lineItem->getReferencedId()],
                Error::LEVEL_ERROR,
                true,  // blockOrder
                false, // persistent
                false  // blockResubmit
            ));
        }
    }

    private function isProductAvailable(string $productId, SalesChannelContext $context): bool
    {
        // In a real implementation, you would check the product's availability
        // For this example, we'll simulate some products being unavailable
        $unavailableProducts = ['unavailable-product-1', 'discontinued-product-2'];
        
        return !in_array($productId, $unavailableProducts, true);
    }
}
