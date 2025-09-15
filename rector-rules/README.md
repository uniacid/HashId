# HashId Bundle Custom Rector Rules

This directory contains custom Rector rules specific to the HashId Bundle modernization project. These rules automate the transformation from v3.x (annotation-based) to v4.x (attribute-based) systems, achieving 90%+ automation rate for modernization patterns.

## Overview

The custom Rector rules enable automated modernization of the HashId Bundle codebase, handling complex transformation patterns while maintaining API compatibility. The rules are designed to work together to provide a comprehensive modernization path.

## Available Rules

### 1. HashAnnotationToAttributeRule

**Purpose**: Converts `@Hash` annotations to PHP 8 `#[Hash]` attributes.

**Transformations**:
- `@Hash("param")` → `#[Hash('param')]`
- `@Hash({"param1", "param2"})` → `#[Hash(['param1', 'param2'])]`
- Handles both class-level and method-level annotations
- Preserves parameter names and configuration
- Updates use statements automatically

**Example**:
```php
// Before
use Pgs\HashIdBundle\Annotation\Hash;

class OrderController
{
    /**
     * @Hash("id")
     */
    public function show(int $id) { }
}

// After
use Pgs\HashIdBundle\Attribute\Hash;

class OrderController
{
    #[Hash('id')]
    public function show(int $id) { }
}
```

### 2. ConfigurationModernizationRule

**Purpose**: Modernizes bundle configuration patterns with typed properties and environment variable support.

**Transformations**:
- Adds typed class constants for configuration keys
- Implements environment variable support for sensitive values
- Adds validation with typed properties
- Creates helper methods for environment variable access

**Features**:
- `CONFIG_SALT`, `CONFIG_MIN_HASH_LENGTH`, `CONFIG_ALPHABET` constants
- Environment variable support via `getEnvOrDefault()` method
- Validation closures for configuration values
- PHP 8.3 typed class constants ready

**Example**:
```php
// Before
$rootNode
    ->children()
        ->scalarNode('salt')
            ->defaultValue('')
        ->end()
    ->end();

// After
$rootNode
    ->children()
        ->scalarNode(self::CONFIG_SALT)
            ->defaultValue($this->getEnvOrDefault('HASHID_SALT', ''))
            ->validate()
                ->ifTrue(fn($v) => !is_string($v))
                ->thenInvalid('Salt must be a string')
            ->end()
        ->end()
    ->end();
```

### 3. ServiceDefinitionRule

**Purpose**: Modernizes service definitions with constructor property promotion and readonly properties.

**Transformations**:
- Applies constructor property promotion
- Adds `readonly` modifier where appropriate
- Adds Symfony service attributes (`#[AutoconfigureTag]`)
- Adds return types to getter methods
- Removes unnecessary property declarations and assignments

**Example**:
```php
// Before
class HasherFactory
{
    private $converter;
    private $config;
    
    public function __construct($converter, array $config)
    {
        $this->converter = $converter;
        $this->config = $config;
    }
}

// After
#[AutoconfigureTag('pgs_hashid.service')]
class HasherFactory
{
    public function __construct(
        private readonly $converter,
        private readonly array $config
    ) {
    }
}
```

## Usage

### Running Individual Rules

To apply a specific rule to your codebase:

```bash
# Apply HashAnnotationToAttributeRule
vendor/bin/rector process src --config=rector.php --only="Pgs\HashIdBundle\Rector\HashAnnotationToAttributeRule"

# Apply ConfigurationModernizationRule
vendor/bin/rector process src --config=rector.php --only="Pgs\HashIdBundle\Rector\ConfigurationModernizationRule"

# Apply ServiceDefinitionRule
vendor/bin/rector process src --config=rector.php --only="Pgs\HashIdBundle\Rector\ServiceDefinitionRule"
```

### Running All HashId Rules

To apply all HashId modernization rules:

```bash
vendor/bin/rector process src --config=rector.php
```

### Dry Run Mode

To preview changes without modifying files:

```bash
vendor/bin/rector process src --config=rector.php --dry-run
```

## Testing

### Running Tests

Each rule has comprehensive test coverage:

```bash
# Run all Rector rule tests
vendor/bin/phpunit tests/Rector/

# Run specific rule tests
vendor/bin/phpunit tests/Rector/HashAnnotationToAttributeRuleTest.php
vendor/bin/phpunit tests/Rector/ConfigurationModernizationRuleTest.php
vendor/bin/phpunit tests/Rector/ServiceDefinitionRuleTest.php
```

### Test Fixtures

Test fixtures are located in `tests/Rector/Fixtures/` with the following structure:

```
tests/Rector/Fixtures/
├── HashAnnotationToAttribute/
│   ├── single_parameter.php.inc
│   ├── multiple_parameters.php.inc
│   └── class_level.php.inc
├── ConfigurationModernization/
│   └── simple_configuration.php.inc
└── ServiceDefinition/
    ├── constructor_promotion.php.inc
    └── readonly_properties.php.inc
```

Each fixture file contains:
- Input code (before transformation)
- Expected output (after transformation)
- Separated by `-----` delimiter

## Contributing

### Adding New Rules

1. **Create the Rule Class**:
   ```php
   // rector-rules/YourNewRule.php
   namespace Pgs\HashIdBundle\Rector;
   
   final class YourNewRule extends AbstractRector
   {
       // Implementation
   }
   ```

2. **Add Test Class**:
   ```php
   // tests/Rector/YourNewRuleTest.php
   final class YourNewRuleTest extends AbstractRectorTestCase
   {
       // Test implementation
   }
   ```

3. **Create Test Fixtures**:
   ```php
   // tests/Rector/Fixtures/YourNewRule/example.php.inc
   <?php
   // Input code
   ?>
   -----
   <?php
   // Expected output
   ?>
   ```

4. **Register in rector.php**:
   The rule will be auto-discovered if it follows the naming pattern `*Rule.php`

### Best Practices

1. **Rule Design**:
   - Keep rules focused on a single transformation
   - Ensure rules are idempotent (can be run multiple times safely)
   - Preserve code formatting where possible
   - Add comprehensive PHPDoc comments

2. **Testing**:
   - Cover edge cases in fixtures
   - Test with real-world code samples
   - Verify backward compatibility handling
   - Test error conditions

3. **Documentation**:
   - Provide clear examples in rule definition
   - Document all transformation patterns
   - Include migration notes for users

## Compatibility

### Version 4.x (Transitional)
- Both annotations and attributes are supported
- Deprecation warnings guide migration
- Backward compatibility maintained
- Gradual migration path provided

### Version 5.0 (Clean)
- Annotation support removed
- Full attribute-based system
- PHP 8.2+ minimum requirement
- Complete modernization

## Performance

The custom Rector rules are optimized for performance:
- Parallel processing enabled by default
- Caching configured in `var/cache/rector`
- Minimal AST traversals
- Efficient pattern matching

Expected processing times:
- Small project (< 100 files): < 5 seconds
- Medium project (100-500 files): 10-30 seconds
- Large project (> 500 files): 1-3 minutes

## Troubleshooting

### Common Issues

1. **Rule Not Applied**:
   - Verify rule is registered in `rector.php`
   - Check file paths in configuration
   - Ensure PHP version compatibility

2. **Unexpected Transformations**:
   - Run with `--debug` flag for details
   - Check fixture files for expected behavior
   - Verify rule precedence if multiple rules apply

3. **Performance Issues**:
   - Clear cache: `rm -rf var/cache/rector`
   - Disable parallel processing for debugging
   - Check for circular dependencies

## Future Enhancements

### Planned for v4.1
- Enhanced validation rules
- Performance optimization rules
- Deprecation tracking dashboard

### Planned for v5.0
- Complete annotation removal
- Strict type enforcement rules
- PHP 8.3+ feature adoption rules