#!/usr/bin/env php
<?php

/**
 * Simple API documentation generator for HashId Bundle.
 *
 * Generates HTML documentation from PHPDoc comments in the source code.
 */

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use Symfony\Component\Finder\Finder;

class SimpleDocGenerator
{
    private string $sourceDir;
    private string $outputDir;
    private array $classes = [];
    private array $interfaces = [];
    private array $traits = [];

    public function __construct(string $sourceDir, string $outputDir)
    {
        $this->sourceDir = $sourceDir;
        $this->outputDir = $outputDir;
    }

    public function generate(): void
    {
        echo "Generating API documentation...\n";

        // Create output directory
        if (!is_dir($this->outputDir)) {
            mkdir($this->outputDir, 0755, true);
        }

        // Scan source files
        $this->scanSourceFiles();

        // Generate documentation pages
        $this->generateIndexPage();
        $this->generateClassPages();

        echo sprintf(
            "Documentation generated successfully!\n" .
            "  - Classes: %d\n" .
            "  - Interfaces: %d\n" .
            "  - Traits: %d\n" .
            "  - Output: %s\n",
            count($this->classes),
            count($this->interfaces),
            count($this->traits),
            realpath($this->outputDir)
        );
    }

    private function scanSourceFiles(): void
    {
        $finder = new Finder();
        $finder->files()
            ->in($this->sourceDir)
            ->name('*.php')
            ->notPath('Tests');

        foreach ($finder as $file) {
            $content = file_get_contents($file->getRealPath());

            // Extract namespace
            if (preg_match('/namespace\s+([^;]+);/', $content, $namespaceMatch)) {
                $namespace = $namespaceMatch[1];

                // Extract class/interface/trait
                if (preg_match('/(class|interface|trait)\s+([^\s{]+)/', $content, $classMatch)) {
                    $type = $classMatch[1];
                    $name = $classMatch[2];
                    $fullName = $namespace . '\\' . $name;

                    $info = [
                        'name' => $name,
                        'fullName' => $fullName,
                        'namespace' => $namespace,
                        'file' => $file->getRelativePathname(),
                        'type' => $type,
                    ];

                    switch ($type) {
                        case 'class':
                            $this->classes[$fullName] = $info;
                            break;
                        case 'interface':
                            $this->interfaces[$fullName] = $info;
                            break;
                        case 'trait':
                            $this->traits[$fullName] = $info;
                            break;
                    }
                }
            }
        }

        // Sort by name
        ksort($this->classes);
        ksort($this->interfaces);
        ksort($this->traits);
    }

    private function generateIndexPage(): void
    {
        $html = <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>HashId Bundle API Documentation</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
            background: #f5f5f5;
        }
        h1 { color: #2E7D32; border-bottom: 3px solid #4CAF50; padding-bottom: 10px; }
        h2 { color: #555; margin-top: 30px; }
        .container { background: white; padding: 30px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        .grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 20px; margin-top: 20px; }
        .card { background: #f9f9f9; padding: 15px; border-radius: 5px; border-left: 4px solid #4CAF50; }
        .card h3 { margin-top: 0; color: #2E7D32; }
        ul { list-style: none; padding: 0; }
        li { padding: 5px 0; }
        a { color: #2E7D32; text-decoration: none; }
        a:hover { text-decoration: underline; }
        .type-badge {
            display: inline-block;
            padding: 2px 8px;
            border-radius: 3px;
            font-size: 0.8em;
            margin-left: 5px;
            background: #e0e0e0;
        }
        .interface-badge { background: #e3f2fd; color: #1976d2; }
        .trait-badge { background: #fce4ec; color: #c2185b; }
        .stats { background: #f0f7ff; padding: 15px; border-radius: 5px; margin: 20px 0; }
    </style>
</head>
<body>
    <div class="container">
        <h1>HashId Bundle API Documentation</h1>
        <p>Automatic route parameter obfuscation for Symfony applications.</p>

        <div class="stats">
            <strong>Statistics:</strong>
            Classes: {$this->count($this->classes)} |
            Interfaces: {$this->count($this->interfaces)} |
            Traits: {$this->count($this->traits)}
        </div>

        <h2>Core Components</h2>
        <div class="grid">
            <div class="card">
                <h3>Bundle Setup</h3>
                <ul>
                    <li><a href="class-PgsHashIdBundle.html">PgsHashIdBundle</a></li>
                </ul>
            </div>

            <div class="card">
                <h3>Router Decoration</h3>
                <ul>
                    <li><a href="class-RouterDecorator.html">RouterDecorator</a></li>
                </ul>
            </div>

            <div class="card">
                <h3>Annotations/Attributes</h3>
                <ul>
                    <li><a href="class-Hash-Attribute.html">Hash (Attribute)</a></li>
                    <li><a href="class-Hash-Annotation.html">Hash (Annotation)</a></li>
                </ul>
            </div>

            <div class="card">
                <h3>Parameter Processing</h3>
                <ul>
                    <li><a href="class-Encode.html">Encode</a></li>
                    <li><a href="class-Decode.html">Decode</a></li>
                    <li><a href="interface-ParametersProcessorInterface.html">ParametersProcessorInterface</a> <span class="type-badge interface-badge">interface</span></li>
                </ul>
            </div>
        </div>

        <h2>All Classes</h2>
        <ul>
HTML;

        foreach ($this->classes as $class) {
            $html .= sprintf(
                '            <li><a href="class-%s.html">%s</a></li>' . "\n",
                str_replace('\\', '-', $class['name']),
                $class['fullName']
            );
        }

        $html .= <<<HTML
        </ul>

        <h2>All Interfaces</h2>
        <ul>
HTML;

        foreach ($this->interfaces as $interface) {
            $html .= sprintf(
                '            <li><a href="interface-%s.html">%s</a></li>' . "\n",
                str_replace('\\', '-', $interface['name']),
                $interface['fullName']
            );
        }

        $html .= <<<HTML
        </ul>

        <h2>All Traits</h2>
        <ul>
HTML;

        foreach ($this->traits as $trait) {
            $html .= sprintf(
                '            <li><a href="trait-%s.html">%s</a></li>' . "\n",
                str_replace('\\', '-', $trait['name']),
                $trait['fullName']
            );
        }

        $html .= <<<HTML
        </ul>
    </div>
</body>
</html>
HTML;

        file_put_contents($this->outputDir . '/index.html', $html);
    }

    private function generateClassPages(): void
    {
        $allItems = array_merge($this->classes, $this->interfaces, $this->traits);

        foreach ($allItems as $item) {
            $this->generateClassPage($item);
        }
    }

    private function generateClassPage(array $classInfo): void
    {
        if (!class_exists($classInfo['fullName']) &&
            !interface_exists($classInfo['fullName']) &&
            !trait_exists($classInfo['fullName'])) {
            return;
        }

        $reflection = new ReflectionClass($classInfo['fullName']);
        $docComment = $reflection->getDocComment() ?: 'No documentation available.';

        // Clean up docblock
        $docComment = $this->formatDocBlock($docComment);

        $html = <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{$classInfo['name']} - HashId Bundle API</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
            background: #f5f5f5;
        }
        .container { background: white; padding: 30px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        h1 { color: #2E7D32; border-bottom: 3px solid #4CAF50; padding-bottom: 10px; }
        h2 { color: #555; margin-top: 30px; border-bottom: 1px solid #ddd; padding-bottom: 5px; }
        .breadcrumb { margin-bottom: 20px; }
        .breadcrumb a { color: #2E7D32; text-decoration: none; }
        .namespace { color: #666; font-size: 0.9em; }
        .doc-block { background: #f9f9f9; padding: 15px; border-radius: 5px; margin: 15px 0; }
        .method { margin: 20px 0; padding: 15px; background: #fafafa; border-left: 3px solid #4CAF50; }
        .method-name { font-weight: bold; color: #2E7D32; }
        .parameter { color: #0066cc; }
        .return-type { color: #663399; }
        code { background: #f4f4f4; padding: 2px 5px; border-radius: 3px; font-family: 'Courier New', monospace; }
        pre { background: #2d2d2d; color: #f8f8f2; padding: 15px; border-radius: 5px; overflow-x: auto; }
    </style>
</head>
<body>
    <div class="container">
        <div class="breadcrumb">
            <a href="index.html">API Documentation</a> / {$classInfo['type']} / {$classInfo['name']}
        </div>

        <h1>{$classInfo['type']} {$classInfo['name']}</h1>
        <div class="namespace">Namespace: {$classInfo['namespace']}</div>
        <div class="namespace">File: {$classInfo['file']}</div>

        <div class="doc-block">
            {$docComment}
        </div>

        <h2>Methods</h2>
HTML;

        foreach ($reflection->getMethods(ReflectionMethod::IS_PUBLIC) as $method) {
            if ($method->getDeclaringClass()->getName() !== $classInfo['fullName']) {
                continue;
            }

            $methodDoc = $method->getDocComment() ?: 'No documentation available.';
            $methodDoc = $this->formatDocBlock($methodDoc);

            $params = [];
            foreach ($method->getParameters() as $param) {
                $type = $param->getType();
                $typeStr = $type ? $this->getTypeString($type) : 'mixed';
                $params[] = sprintf('%s $%s', $typeStr, $param->getName());
            }
            $paramsStr = implode(', ', $params);

            $returnType = $method->getReturnType();
            $returnStr = $returnType ? ': ' . $this->getTypeString($returnType) : '';

            $html .= <<<HTML
        <div class="method">
            <div class="method-name">{$method->getName()}({$paramsStr}){$returnStr}</div>
            <div class="doc-block">{$methodDoc}</div>
        </div>
HTML;
        }

        $html .= <<<HTML
    </div>
</body>
</html>
HTML;

        $filename = sprintf(
            '%s/%s-%s.html',
            $this->outputDir,
            $classInfo['type'],
            str_replace('\\', '-', $classInfo['name'])
        );
        file_put_contents($filename, $html);
    }

    private function formatDocBlock(string $docBlock): string
    {
        // Remove /** and */
        $docBlock = preg_replace('/^\/\*\*|\*\/$/m', '', $docBlock);
        // Remove leading asterisks
        $docBlock = preg_replace('/^\s*\* ?/m', '', $docBlock);
        // Convert @param, @return, etc to formatted HTML
        $docBlock = preg_replace('/@(\w+)/', '<strong>@$1</strong>', $docBlock);
        // Convert code blocks
        $docBlock = preg_replace('/```php(.*?)```/s', '<pre><code>$1</code></pre>', $docBlock);
        // Convert inline code
        $docBlock = preg_replace('/`([^`]+)`/', '<code>$1</code>', $docBlock);
        // Convert line breaks
        $docBlock = nl2br(trim($docBlock));

        return $docBlock;
    }

    private function getTypeString($type): string
    {
        if ($type instanceof ReflectionNamedType) {
            return $type->getName();
        } elseif ($type instanceof ReflectionUnionType) {
            $types = array_map(fn($t) => $t->getName(), $type->getTypes());
            return implode('|', $types);
        }
        return 'mixed';
    }

    private function count(array $items): int
    {
        return count($items);
    }
}

// Run the generator
$sourceDir = __DIR__ . '/../src';
$outputDir = __DIR__ . '/../build/api-docs';

$generator = new SimpleDocGenerator($sourceDir, $outputDir);
$generator->generate();