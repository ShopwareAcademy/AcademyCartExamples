<?php declare(strict_types=1);

namespace AcademyCartExamples\Service;

use Shopware\Core\Checkout\Cart\Error\Error;
use Shopware\Core\Checkout\Cart\Error\ErrorCollection;
use Shopware\Core\Checkout\Cart\Error\GenericCartError;
use Shopware\Core\Checkout\Cart\LineItem\LineItem;
use Shopware\Core\System\SalesChannel\SalesChannelContext;

class AcademyCartService
{
    public const float MINIMUM_ORDER_VALUE = 100.00; // Fix value defined here or get from plugin config

    public function isB2BCustomer(SalesChannelContext $context): bool
    {
        // Check if customer has B2B role or company
        $customer = $context->getCustomer();
        if (null === $customer) {
            return false;
        }

        if (null === $customer->getCompany()) {
            return false;
        }

        return true;
    }
    public function validateProductLineItem(LineItem $lineItem, ErrorCollection $errors): void
    {
        // Check if product is still available
        if (false === $this->isProductAvailable($lineItem->getReferencedId())) {
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

    private function isProductAvailable(string $productId): bool
    {
        // In a real implementation, you would check the product's availability
        // For this example, we'll simulate some products being unavailable
        $unavailableProducts = ['unavailable-product-1', 'discontinued-product-2'];

        if (true === in_array($productId, $unavailableProducts, true)) {
            return false;
        }

        return true;
    }
}