<?php declare(strict_types=1);

namespace AcademyCartExamples\Cart\Error;

use Shopware\Core\Checkout\Cart\Error\Error;

class MinimumOrderValueError extends Error
{
    private const KEY = 'academy-minimum-order-value';

    private float $currentValue;
    private float $minimumValue;
    private float $missing;

    public function __construct(float $currentValue, float $minimumValue, float $missing)
    {
        $this->currentValue = $currentValue;
        $this->minimumValue = $minimumValue;
        $this->missing = $missing;
        parent::__construct();
    }

    public function getId(): string
    {
        return self::KEY;
    }

    public function getMessageKey(): string
    {
        return self::KEY;
    }

    public function getLevel(): int
    {
        return self::LEVEL_ERROR;
    }

    public function blockOrder(): bool
    {
        return true;
    }

    public function getParameters(): array
    {
        return [
            'currentValue' => $this->currentValue,
            'minimumValue' => $this->minimumValue,
            'missing' => $this->missing
        ];
    }
}
