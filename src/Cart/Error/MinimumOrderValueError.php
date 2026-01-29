<?php declare(strict_types=1);

namespace AcademyCartExamples\Cart\Error;

use Shopware\Core\Checkout\Cart\Error\Error;

class MinimumOrderValueError extends Error
{
    private const string KEY = 'academy-minimum-order-value';

    public function __construct(
        private readonly float $currentValue,
        private readonly float $minimumValue,
        private readonly float $missing
    )
    {
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