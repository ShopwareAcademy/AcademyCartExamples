# AcademyCartExamples Plugin

A Shopware 6 plugin demonstrating cart validation, cart processing, and custom cart logic for educational purposes.

## Features

- Cart validation examples (e.g. minimum order value)
- Cart processing examples (custom processors)
- Custom cart errors with snippet translations (German/English)
- PHPUnit tests for validators and processors

## Component Structure

The plugin focuses on cart-related components instead of DAL entities:

- **Validators** (validate cart state and add errors)
  - `MinimumOrderValueValidator` (B2B minimum order value example)
  - `ProductAvailabilityValidator` (availability validation example)
- **Processors** (manipulate cart during calculation)
  - `BulkDiscountProcessor`
  - `B2BDiscountProcessor`
- **Errors**
  - `MinimumOrderValueError` (translated cart error)

## Configuration / Rules (Examples)

This plugin includes educational example logic, e.g.:

- Treat customers with a company (`company` set) as B2B
- Apply a minimum order value threshold (see validator implementation)

## Architecture Overview

### Core Components

1. **Cart Validation**
   - Implements validation logic to block checkout / show cart errors when conditions are not met.

2. **Cart Processing**
   - Demonstrates how to implement processors that participate in the cart calculation pipeline.

3. **Internationalization**
   - Snippets under `Resources/snippet` provide DE/EN messages for cart errors.

4. **Testing**
   - Example PHPUnit tests for validators/processors using Shopware’s kernel test bootstrap.

### File Structure

```
src/
├── AcademyCartExamples.php                       # Main plugin class
├── Cart/
│   ├── Error/
│   │   └── MinimumOrderValueError.php            # Custom error class
│   ├── MinimumOrderValueValidator.php            # Minimum order value validation
│   ├── ProductAvailabilityValidator.php          # Availability validation
│   └── Processor/
│       ├── B2BDiscountProcessor.php              # Processor example
│       └── BulkDiscountProcessor.php             # Processor example
├── Service/
│   └── AcademyCartService.php                    # Supporting service(s)
└── Resources/
    ├── config/
    │   └── services.xml                          # Service definitions
    └── snippet/
        ├── de_DE/academyCart.de-DE.json
        └── en_GB/academyCart.en-GB.json

tests/
├── Cart/
│   ├── MinimumOrderValueValidatorTest.php
│   ├── SimpleMinimumOrderValueValidatorTest.php
│   └── Processor/
│       ├── B2BPromotionProcessorTest.php
│       └── BulkDiscountProcessorTest.php
└── TestBootstrap.php
```

## Installation

1. Place the plugin in `custom/plugins/AcademyCartExamples/`
2. Run `bin/console plugin:refresh`
3. Run `bin/console plugin:install --activate AcademyCartExamples`
4. Run `bin/console cache:clear`

## Usage

### Minimum Order Value Validation (Example)

Depending on the implemented logic, the plugin can add a cart error for B2B customers if a minimum order value is not reached.

### Error Messages

Snippets are provided in:

- `Resources/snippet/en_GB/academyCart.en-GB.json`
- `Resources/snippet/de_DE/academyCart.de-DE.json`

## Technical Details

### Cart Integration

This plugin demonstrates:

- Where and how to register custom cart validators/processors via DI (`services.xml`)
- How to add translated cart errors and display them in the cart

### Testing

Run the test suite:

```bash
# From Shopware root directory
./vendor/bin/phpunit --configuration="custom/plugins/AcademyCartExamples"
```

## Development Notes

This plugin serves as a reference implementation for:

- Cart validation patterns in Shopware 6
- Cart processing pipeline examples
- Translated cart errors (snippets)
- Writing PHPUnit tests for cart extensions

## Educational Value

This plugin demonstrates:

1. **Cart Validation Patterns**: How to implement custom cart validators
2. **Error Handling**: Proper error classes and translation integration
3. **Service Registration**: Dependency injection configuration
4. **Testing Strategies**: Both integration and unit testing approaches
5. **Shopware 6.7 Compatibility**: Latest interfaces and best practices

## Requirements

- Shopware 6.7+
- PHP 8.3
- Composer

## License

MIT License – Educational use only (intended as an educational example; the MIT license in `composer.json` applies).

## Contributing

This plugin is part of the Shopware Academy curriculum. For educational purposes only.