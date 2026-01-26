# AcademyCartExamples Plugin

A Shopware 6 plugin demonstrating cart validation, processing, and custom logic for educational purposes.

## Overview

This plugin serves as a practical example for the Shopware Academy learning units on cart validation and processing. It demonstrates real-world scenarios that developers encounter when building custom cart logic in Shopware 6.

## Features

### Cart Validation
- **Minimum Order Value Validator**: Enforces minimum order values for B2B customers
- **Product Availability Validator**: Basic product availability checking
- **Custom Error Classes**: Proper error handling with translation support

### Testing
- **Comprehensive Test Suite**: 6 PHPUnit tests with 12 assertions
- **Integration Tests**: Full Shopware kernel integration
- **Unit Tests**: Isolated component testing
- **Shopware 6.7 Compatible**: Uses latest interfaces and patterns

### Internationalization
- **German Translations**: Complete German language support
- **English Translations**: Default English language support
- **Translation Keys**: Proper snippet key usage

## Installation

1. Copy the plugin to your Shopware 6 installation:
   ```bash
   cp -r AcademyCartExamples /path/to/shopware/custom/plugins/
   ```

2. Install and activate the plugin:
   ```bash
   bin/console plugin:refresh
   bin/console plugin:install --activate AcademyCartExamples
   ```

3. Clear the cache:
   ```bash
   bin/console cache:clear
   ```

## Testing

Run the test suite:

```bash
# From Shopware root directory
./vendor/bin/phpunit --configuration="custom/plugins/AcademyCartExamples"
```

## Usage

### B2B Minimum Order Validation

The plugin automatically validates B2B customers (customers with a company) against a minimum order value of €100.00.

- **B2B customers below minimum**: Cart shows validation error
- **B2C customers**: No validation applied
- **B2B customers above minimum**: Validation passes

### Error Messages

The plugin provides localized error messages:

- **English**: "Minimum order value not reached. Current: €X.XX, Required: €100.00, Missing: €Y.YY"
- **German**: "Mindestbestellwert nicht erreicht. Aktuell: €X.XX, Erforderlich: €100.00, Fehlend: €Y.YY"

## Code Structure

```
src/
├── AcademyCartExamples.php              # Main plugin class
├── Cart/
│   ├── MinimumOrderValueValidator.php   # B2B minimum order validation
│   ├── ProductAvailabilityValidator.php # Product availability validation
│   └── Error/
│       └── MinimumOrderValueError.php   # Custom error class
└── Resources/
    ├── config/
    │   └── services.xml                 # Service definitions
    └── snippet/
        ├── de_DE/
        │   └── academyCart.de-DE.json   # German translations
        └── en_GB/
            └── academyCart.en-GB.json   # English translations

tests/
├── Cart/
│   ├── MinimumOrderValueValidatorTest.php      # Integration tests
│   └── SimpleMinimumOrderValueValidatorTest.php # Unit tests
└── TestBootstrap.php                           # Test bootstrap
```

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

MIT License – Educational use only

## Contributing

This plugin is part of the Shopware Academy curriculum. For educational purposes only.