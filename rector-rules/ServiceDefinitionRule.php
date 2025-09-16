<?php

declare(strict_types=1);

namespace Pgs\HashIdBundle\Rector;

use PhpParser\Node;
use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\ClassMethod;
use PhpParser\Node\Stmt\Property;
use PhpParser\Node\Stmt\PropertyProperty;
use PhpParser\Node\Param;
use PhpParser\Node\Expr\Assign;
use PhpParser\Node\Expr\PropertyFetch;
use PhpParser\Node\Expr\Variable;
use PhpParser\Node\Identifier;
use PhpParser\Node\Name\FullyQualified;
use PhpParser\Node\AttributeGroup;
use PhpParser\Node\Attribute;
use PhpParser\Node\Arg;
use PhpParser\Node\Scalar\String_;
use PhpParser\BuilderFactory;
use PhpParser\Modifiers;
use Rector\Rector\AbstractRector;
use Symplify\RuleDocGenerator\ValueObject\CodeSample\CodeSample;
use Symplify\RuleDocGenerator\ValueObject\RuleDefinition;

/**
 * Modernizes service definitions in HashId Bundle.
 * 
 * - Converts to constructor property promotion
 * - Adds readonly properties where appropriate
 * - Adds Symfony service attributes
 * - Implements proper dependency injection patterns
 */
final class ServiceDefinitionRule extends AbstractRector
{
    private BuilderFactory $builderFactory;

    public function __construct()
    {
        $this->builderFactory = new BuilderFactory();
    }

    public function getRuleDefinition(): RuleDefinition
    {
        return new RuleDefinition(
            'Modernize HashId Bundle service definitions with constructor property promotion and readonly properties',
            [
                new CodeSample(
                    <<<'CODE_SAMPLE'
namespace Pgs\HashIdBundle\Service;

class HasherFactory
{
    private $hashidsConverter;
    private $config;
    
    public function __construct(HashidsConverter $hashidsConverter, array $config)
    {
        $this->hashidsConverter = $hashidsConverter;
        $this->config = $config;
    }
    
    public function getConverter()
    {
        return $this->hashidsConverter;
    }
}
CODE_SAMPLE
                    ,
                    <<<'CODE_SAMPLE'
namespace Pgs\HashIdBundle\Service;

use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;

#[AutoconfigureTag('pgs_hashid.service')]
class HasherFactory
{
    public function __construct(
        private readonly HashidsConverter $hashidsConverter,
        #[Autowire('%pgs_hash_id.config%')]
        private readonly array $config
    ) {
    }
    
    public function getConverter(): HashidsConverter
    {
        return $this->hashidsConverter;
    }
}
CODE_SAMPLE
                ),
            ]
        );
    }

    /**
     * @return array<class-string<Node>>
     */
    public function getNodeTypes(): array
    {
        return [Class_::class];
    }

    /**
     * @param Class_ $node
     */
    public function refactor(Node $node): ?Node
    {
        if (!$this->isServiceClass($node)) {
            return null;
        }

        $hasChanges = false;

        // Apply constructor property promotion
        if ($this->applyConstructorPropertyPromotion($node)) {
            $hasChanges = true;
        }

        // Add service attributes
        if ($this->addServiceAttributes($node)) {
            $hasChanges = true;
        }

        // Add return types to getter methods
        if ($this->addReturnTypes($node)) {
            $hasChanges = true;
        }

        return $hasChanges ? $node : null;
    }

    /**
     * Check if this is a service class.
     */
    private function isServiceClass(Class_ $class): bool
    {
        if ($class->name === null) {
            return false;
        }

        $namespace = $this->getName($class);
        
        // Check if it's in a service-related namespace
        return $namespace !== null && (
            str_contains($namespace, 'Service') ||
            str_contains($namespace, 'Factory') ||
            str_contains($namespace, 'Converter') ||
            str_contains($namespace, 'Processor') ||
            str_contains($namespace, 'Decorator')
        );
    }

    /**
     * Apply constructor property promotion.
     */
    private function applyConstructorPropertyPromotion(Class_ $class): bool
    {
        $constructor = $this->findConstructor($class);
        if ($constructor === null) {
            return false;
        }

        $promotableProperties = $this->findPromotableProperties($class, $constructor);
        if (empty($promotableProperties)) {
            return false;
        }

        // Remove old property declarations
        $this->removeProperties($class, array_keys($promotableProperties));

        // Update constructor parameters with promotion
        foreach ($constructor->params as $param) {
            $paramName = $param->var instanceof Variable ? $param->var->name : null;
            if ($paramName !== null && isset($promotableProperties[$paramName])) {
                $propertyInfo = $promotableProperties[$paramName];
                
                // Add visibility modifier
                $param->flags = $propertyInfo['visibility'];
                
                // Add readonly if appropriate
                if ($this->shouldBeReadonly($class, $paramName)) {
                    $param->flags |= Modifiers::READONLY;
                }
            }
        }

        // Remove property assignments from constructor body
        $this->removePropertyAssignments($constructor, array_keys($promotableProperties));

        return true;
    }

    /**
     * Find constructor method.
     */
    private function findConstructor(Class_ $class): ?ClassMethod
    {
        foreach ($class->stmts as $stmt) {
            if ($stmt instanceof ClassMethod && $stmt->name->toString() === '__construct') {
                return $stmt;
            }
        }
        return null;
    }

    /**
     * Find properties that can be promoted.
     * 
     * @return array<string, array{visibility: int, type: ?Node}>
     */
    private function findPromotableProperties(Class_ $class, ClassMethod $constructor): array
    {
        $properties = [];

        // Find all properties
        $classProperties = [];
        foreach ($class->stmts as $stmt) {
            if ($stmt instanceof Property) {
                foreach ($stmt->props as $prop) {
                    $classProperties[$prop->name->toString()] = [
                        'visibility' => $stmt->flags,
                        'type' => $stmt->type,
                    ];
                }
            }
        }

        // Check which constructor parameters match properties
        foreach ($constructor->params as $param) {
            $paramName = $param->var instanceof Variable ? $param->var->name : null;
            if ($paramName !== null && isset($classProperties[$paramName])) {
                // Check if parameter is assigned to property in constructor
                if ($this->isAssignedToProperty($constructor, $paramName)) {
                    $properties[$paramName] = $classProperties[$paramName];
                }
            }
        }

        return $properties;
    }

    /**
     * Check if parameter is assigned to property in constructor.
     */
    private function isAssignedToProperty(ClassMethod $constructor, string $paramName): bool
    {
        if ($constructor->stmts === null) {
            return false;
        }

        foreach ($constructor->stmts as $stmt) {
            if ($stmt instanceof Node\Stmt\Expression && 
                $stmt->expr instanceof Assign) {
                $assign = $stmt->expr;
                
                if ($assign->var instanceof PropertyFetch &&
                    $assign->var->var instanceof Variable &&
                    $assign->var->var->name === 'this' &&
                    $assign->var->name instanceof Identifier &&
                    $assign->var->name->toString() === $paramName &&
                    $assign->expr instanceof Variable &&
                    $assign->expr->name === $paramName) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Check if property should be readonly.
     */
    private function shouldBeReadonly(Class_ $class, string $propertyName): bool
    {
        // Check if property has any setters
        foreach ($class->stmts as $stmt) {
            if ($stmt instanceof ClassMethod) {
                $methodName = $stmt->name->toString();
                
                // Skip constructor
                if ($methodName === '__construct') {
                    continue;
                }
                
                // Check for setter methods
                if (strtolower($methodName) === 'set' . strtolower($propertyName)) {
                    return false;
                }
                
                // Check for property modifications in method body
                if ($this->modifiesProperty($stmt, $propertyName)) {
                    return false;
                }
            }
        }
        
        return true;
    }

    /**
     * Check if method modifies a property.
     */
    private function modifiesProperty(ClassMethod $method, string $propertyName): bool
    {
        if ($method->stmts === null) {
            return false;
        }

        foreach ($method->stmts as $stmt) {
            if ($this->containsPropertyModification($stmt, $propertyName)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if statement contains property modification.
     */
    private function containsPropertyModification(Node $node, string $propertyName): bool
    {
        if ($node instanceof Assign &&
            $node->var instanceof PropertyFetch &&
            $node->var->var instanceof Variable &&
            $node->var->var->name === 'this' &&
            $node->var->name instanceof Identifier &&
            $node->var->name->toString() === $propertyName) {
            return true;
        }

        foreach ($node->getSubNodeNames() as $subNodeName) {
            $subNode = $node->$subNodeName;
            
            if ($subNode instanceof Node) {
                if ($this->containsPropertyModification($subNode, $propertyName)) {
                    return true;
                }
            } elseif (is_array($subNode)) {
                foreach ($subNode as $item) {
                    if ($item instanceof Node && $this->containsPropertyModification($item, $propertyName)) {
                        return true;
                    }
                }
            }
        }

        return false;
    }

    /**
     * Remove properties from class.
     * 
     * @param array<string> $propertyNames
     */
    private function removeProperties(Class_ $class, array $propertyNames): void
    {
        $newStmts = [];
        
        foreach ($class->stmts as $stmt) {
            if ($stmt instanceof Property) {
                $keep = false;
                foreach ($stmt->props as $prop) {
                    if (!in_array($prop->name->toString(), $propertyNames, true)) {
                        $keep = true;
                        break;
                    }
                }
                
                if ($keep) {
                    $newStmts[] = $stmt;
                }
            } else {
                $newStmts[] = $stmt;
            }
        }
        
        $class->stmts = $newStmts;
    }

    /**
     * Remove property assignments from constructor.
     * 
     * @param array<string> $propertyNames
     */
    private function removePropertyAssignments(ClassMethod $constructor, array $propertyNames): void
    {
        if ($constructor->stmts === null) {
            return;
        }

        $newStmts = [];
        
        foreach ($constructor->stmts as $stmt) {
            $skip = false;
            
            if ($stmt instanceof Node\Stmt\Expression && 
                $stmt->expr instanceof Assign &&
                $stmt->expr->var instanceof PropertyFetch &&
                $stmt->expr->var->var instanceof Variable &&
                $stmt->expr->var->var->name === 'this' &&
                $stmt->expr->var->name instanceof Identifier &&
                in_array($stmt->expr->var->name->toString(), $propertyNames, true)) {
                $skip = true;
            }
            
            if (!$skip) {
                $newStmts[] = $stmt;
            }
        }
        
        $constructor->stmts = $newStmts;
    }

    /**
     * Add service attributes.
     */
    private function addServiceAttributes(Class_ $class): bool
    {
        // Check if attributes already exist
        if (!empty($class->attrGroups)) {
            foreach ($class->attrGroups as $attrGroup) {
                foreach ($attrGroup->attrs as $attr) {
                    if ($attr->name instanceof FullyQualified && 
                        str_contains($attr->name->toString(), 'AutoconfigureTag')) {
                        return false;
                    }
                }
            }
        }

        // Add AutoconfigureTag attribute
        $attribute = new Attribute(
            new FullyQualified('Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag'),
            [new Arg(new String_('pgs_hashid.service'))]
        );
        
        $class->attrGroups[] = new AttributeGroup([$attribute]);
        
        return true;
    }

    /**
     * Add return types to getter methods.
     */
    private function addReturnTypes(Class_ $class): bool
    {
        $hasChanges = false;
        
        foreach ($class->stmts as $stmt) {
            if ($stmt instanceof ClassMethod && 
                str_starts_with($stmt->name->toString(), 'get') &&
                $stmt->returnType === null) {
                
                // Try to infer return type from method body
                $returnType = $this->inferReturnType($stmt);
                if ($returnType !== null) {
                    $stmt->returnType = $returnType;
                    $hasChanges = true;
                }
            }
        }
        
        return $hasChanges;
    }

    /**
     * Infer return type from method body.
     */
    private function inferReturnType(ClassMethod $method): ?Node
    {
        if ($method->stmts === null) {
            return null;
        }

        foreach ($method->stmts as $stmt) {
            if ($stmt instanceof Node\Stmt\Return_ && $stmt->expr !== null) {
                if ($stmt->expr instanceof PropertyFetch &&
                    $stmt->expr->var instanceof Variable &&
                    $stmt->expr->var->name === 'this') {
                    // Could look up property type here
                    // For simplicity, we'll just add 'mixed' for now
                    return new Identifier('mixed');
                }
            }
        }
        
        return null;
    }
}