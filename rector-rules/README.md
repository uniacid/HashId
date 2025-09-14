# HashId Bundle Custom Rector Rules

This directory contains custom Rector rules specific to the HashId Bundle modernization project.

## Bundle-Specific Patterns for Transformation

### 1. Annotation to Attribute Transformation

#### Hash Annotation
- **Pattern**: `@Hash("param")` or `@Hash({"param1", "param2"})`
- **Target**: `#[Hash('param')]` or `#[Hash(['param1', 'param2'])]`
- **Locations**: Controller methods
- **Rule**: `HashAnnotationToAttributeRule`

#### Route Annotation
- **Pattern**: `@Route("/path", name="route_name")`
- **Target**: `#[Route('/path', name: 'route_name')]`
- **Locations**: Controller classes and methods
- **Rule**: `RouteAnnotationToAttributeRule`

### 2. Symfony Compatibility Updates

#### Sensio Framework Extra Bundle
- **Pattern**: Use of `sensio/framework-extra-bundle` annotations
- **Target**: Native Symfony attributes
- **Affected**:
  - `@Route` → `#[Route]`
  - `@Method` → Removed (use `methods` parameter in Route)
  - `@Template` → Manual response handling

### 3. PHP 8.1+ Feature Adoption

#### Constructor Property Promotion
- **Pattern**: Traditional constructor with property assignments
- **Target**: Constructor property promotion
- **Applied to**: Service classes, configuration objects

#### Type Declarations
- **Pattern**: Mixed or missing type hints
- **Target**: Proper union types and type declarations
- **Focus**: Public API methods

### 4. Deprecated Patterns

#### Doctrine Annotations
- **Pattern**: Use of `doctrine/annotations` for parsing
- **Target**: Native PHP attributes with reflection
- **Migration**: Gradual with compatibility layer

## Custom Rules Implementation

### HashAnnotationToAttributeRule
Transforms `@Hash` annotations to PHP 8 attributes while maintaining backward compatibility.

### RouteAnnotationToAttributeRule  
Converts Symfony route annotations to attributes with proper parameter naming.

### DeprecationHandler
Adds deprecation notices when annotations are used, encouraging migration to attributes.

## Testing Infrastructure

Each rule has corresponding fixtures in `tests/Rector/Fixtures/` that demonstrate:
- Input code (before transformation)
- Expected output (after transformation)
- Edge cases and error handling

## Compatibility Considerations

During the transition period (v4.x):
- Both annotations and attributes are supported
- Configuration flag to disable deprecation warnings
- Clear migration path in UPGRADE-4.0.md

## Future Rules (v5.0)

- Complete removal of annotation support
- Strict type enforcement
- PHP 8.2+ features adoption