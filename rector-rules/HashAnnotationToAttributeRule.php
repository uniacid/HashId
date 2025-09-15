<?php

declare(strict_types=1);

namespace Pgs\HashIdBundle\Rector;

use PhpParser\Node;
use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\ClassMethod;
use PhpParser\Node\AttributeGroup;
use PhpParser\Node\Attribute;
use PhpParser\Node\Name\FullyQualified;
use PhpParser\Node\Arg;
use PhpParser\Node\Scalar\String_;
use PhpParser\Node\Expr\Array_;
use PhpParser\Node\Expr\ArrayItem;
use PhpParser\Comment\Doc;
use Rector\Rector\AbstractRector;
use Symplify\RuleDocGenerator\ValueObject\CodeSample\CodeSample;
use Symplify\RuleDocGenerator\ValueObject\RuleDefinition;

/**
 * Converts @Hash annotations to #[Hash] attributes.
 * 
 * Handles both single parameter and array parameter syntax:
 * - @Hash("param") → #[Hash('param')]
 * - @Hash({"param1", "param2"}) → #[Hash(['param1', 'param2'])]
 */
final class HashAnnotationToAttributeRule extends AbstractRector
{
    private const HASH_ANNOTATION_CLASS = 'Pgs\HashIdBundle\Annotation\Hash';
    private const HASH_ATTRIBUTE_CLASS = 'Pgs\HashIdBundle\Attribute\Hash';

    public function getRuleDefinition(): RuleDefinition
    {
        return new RuleDefinition(
            'Convert @Hash annotations to #[Hash] attributes',
            [
                new CodeSample(
                    <<<'CODE_SAMPLE'
use Pgs\HashIdBundle\Annotation\Hash;

class OrderController
{
    /**
     * @Hash("id")
     */
    public function show(int $id)
    {
    }
}
CODE_SAMPLE
                    ,
                    <<<'CODE_SAMPLE'
use Pgs\HashIdBundle\Attribute\Hash;

class OrderController
{
    #[Hash('id')]
    public function show(int $id)
    {
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
        return [ClassMethod::class, Class_::class];
    }

    /**
     * @param ClassMethod|Class_ $node
     */
    public function refactor(Node $node): ?Node
    {
        $docComment = $node->getDocComment();
        if ($docComment === null) {
            return null;
        }

        $docText = $docComment->getText();
        
        // Look for @Hash annotations
        if (!str_contains($docText, '@Hash')) {
            return null;
        }

        $hasChanges = false;
        $newDocLines = [];
        $attributes = [];
        
        $lines = explode("\n", $docText);
        foreach ($lines as $line) {
            if (preg_match('/@Hash\s*\(([^)]+)\)/', $line, $matches)) {
                // Extract parameters from annotation
                $params = $this->parseHashParameters($matches[1]);
                if ($params !== null) {
                    $attributes[] = $this->createHashAttribute($params);
                    $hasChanges = true;
                    // Skip this line (remove the annotation)
                    continue;
                }
            }
            $newDocLines[] = $line;
        }

        if (!$hasChanges) {
            return null;
        }

        // Add attributes to the node
        foreach ($attributes as $attribute) {
            $node->attrGroups[] = new AttributeGroup([$attribute]);
        }

        // Update or remove the doc comment
        $newDocText = implode("\n", $newDocLines);
        $newDocText = $this->cleanupDocComment($newDocText);
        
        if ($newDocText === null || trim($newDocText) === '' || trim($newDocText) === '/***/') {
            $node->setDocComment(null);
        } else {
            $node->setDocComment(new Doc($newDocText));
        }

        return $node;
    }

    /**
     * Parse Hash parameters from annotation string.
     * 
     * @return string|array<string>|null
     */
    private function parseHashParameters(string $params): string|array|null
    {
        $params = trim($params);
        
        // Handle single parameter: "param"
        if (preg_match('/^"([^"]+)"$/', $params, $matches)) {
            return $matches[1];
        }
        
        // Handle single parameter with single quotes: 'param'
        if (preg_match("/^'([^']+)'$/", $params, $matches)) {
            return $matches[1];
        }
        
        // Handle array parameters: {"param1", "param2"} or ['param1', 'param2']
        if (preg_match('/^[\[\{](.+)[\]\}]$/', $params, $matches)) {
            $result = [];
            preg_match_all('/["\']([^"\']+)["\']/', $matches[1], $paramMatches);
            foreach ($paramMatches[1] as $param) {
                $result[] = $param;
            }
            return !empty($result) ? $result : null;
        }
        
        return null;
    }

    /**
     * Create Hash attribute node.
     * 
     * @param string|array<string> $parameters
     */
    private function createHashAttribute(string|array $parameters): Attribute
    {
        $attributeName = new FullyQualified(self::HASH_ATTRIBUTE_CLASS);
        
        if (is_string($parameters)) {
            // Single parameter
            $args = [new Arg(new String_($parameters))];
        } else {
            // Array of parameters
            $arrayItems = [];
            foreach ($parameters as $param) {
                $arrayItems[] = new ArrayItem(new String_($param));
            }
            $args = [new Arg(new Array_($arrayItems))];
        }
        
        return new Attribute($attributeName, $args);
    }

    /**
     * Clean up doc comment after removing annotations.
     */
    private function cleanupDocComment(string $docText): ?string
    {
        // Remove empty lines at the beginning and end of the comment
        $lines = explode("\n", $docText);
        $cleanedLines = [];
        
        foreach ($lines as $line) {
            // Remove lines that only contain asterisks and whitespace after annotation removal
            $trimmed = trim($line);
            if ($trimmed === '*' || $trimmed === '') {
                // Keep the opening and closing doc comment lines
                if (str_contains($line, '/**') || str_contains($line, '*/')) {
                    $cleanedLines[] = $line;
                }
                continue;
            }
            $cleanedLines[] = $line;
        }
        
        // Check if only opening/closing remain
        $hasContent = false;
        foreach ($cleanedLines as $line) {
            $trimmed = trim($line);
            if ($trimmed !== '/**' && $trimmed !== '*/' && $trimmed !== '*' && $trimmed !== '') {
                $hasContent = true;
                break;
            }
        }
        
        if (!$hasContent) {
            return null;
        }
        
        return implode("\n", $cleanedLines);
    }
}