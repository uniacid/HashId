<?php

declare(strict_types=1);

namespace Pgs\HashIdBundle\Tests\Service;

use Pgs\HashIdBundle\Attribute\Hash as HashAttribute;
use Pgs\HashIdBundle\Service\CompatibilityLayer;
use Pgs\HashIdBundle\Rector\DeprecationHandler;
use PHPUnit\Framework\TestCase;

class CompatibilityLayerTest extends TestCase
{
    private CompatibilityLayer $compatibilityLayer;
    
    protected function setUp(): void
    {
        DeprecationHandler::clearLog();
        $this->compatibilityLayer = new CompatibilityLayer(true, true);
    }
    
    protected function tearDown(): void
    {
        DeprecationHandler::clearLog();
        DeprecationHandler::enableWarnings();
    }
    
    public function testExtractFromAttributeOnly(): void
    {
        $controller = new class {
            #[HashAttribute('id')]
            public function show(int $id): void
            {
            }
        };
        
        $reflectionClass = new \ReflectionClass($controller);
        $method = $reflectionClass->getMethod('show');
        
        $parameters = $this->compatibilityLayer->extractHashConfiguration($method);
        
        $this->assertEquals(['id'], $parameters);
        $this->assertFalse(DeprecationHandler::hasDeprecation('@Hash'));
    }
    
    public function testExtractFromAnnotationOnly(): void
    {
        $controller = new class {
            /**
             * @Hash("id")
             */
            public function show(int $id): void
            {
            }
        };
        
        $reflectionClass = new \ReflectionClass($controller);
        $method = $reflectionClass->getMethod('show');
        
        $parameters = $this->compatibilityLayer->extractHashConfiguration($method);
        
        $this->assertEquals(['id'], $parameters);
        $this->assertTrue(DeprecationHandler::hasDeprecation('@Hash'));
    }
    
    public function testExtractFromAnnotationWithMultipleParameters(): void
    {
        $controller = new class {
            /**
             * @Hash({"id", "parentId"})
             */
            public function show(int $id, int $parentId): void
            {
            }
        };
        
        $reflectionClass = new \ReflectionClass($controller);
        $method = $reflectionClass->getMethod('show');
        
        $parameters = $this->compatibilityLayer->extractHashConfiguration($method);
        
        $this->assertEquals(['id', 'parentId'], $parameters);
        $this->assertTrue(DeprecationHandler::hasDeprecation('@Hash'));
    }
    
    public function testExtractWithBothAttributeAndAnnotation(): void
    {
        $controller = new class {
            /**
             * @Hash("id")
             */
            #[HashAttribute('id')]
            public function show(int $id): void
            {
            }
        };
        
        $reflectionClass = new \ReflectionClass($controller);
        $method = $reflectionClass->getMethod('show');
        
        // Should prefer attribute when both are present
        $parameters = $this->compatibilityLayer->extractHashConfiguration($method);
        
        $this->assertEquals(['id'], $parameters);
        
        // Check for duplicate configuration
        $this->assertTrue($this->compatibilityLayer->hasDuplicateConfiguration($method));
    }
    
    public function testExtractWithNoHashConfiguration(): void
    {
        $controller = new class {
            public function index(): void
            {
            }
        };
        
        $reflectionClass = new \ReflectionClass($controller);
        $method = $reflectionClass->getMethod('index');
        
        $parameters = $this->compatibilityLayer->extractHashConfiguration($method);
        
        $this->assertNull($parameters);
    }
    
    public function testDeprecationWarningSuppression(): void
    {
        // First create a layer with warnings disabled
        $compatibilityLayerNoWarnings = new CompatibilityLayer(false, true);
        
        $controller = new class {
            /**
             * @Hash("id")
             */
            public function show(int $id): void
            {
            }
        };
        
        $reflectionClass = new \ReflectionClass($controller);
        $method = $reflectionClass->getMethod('show');
        
        $compatibilityLayerNoWarnings->extractHashConfiguration($method);
        
        // Deprecation should still be logged even when warnings are suppressed
        $this->assertTrue(DeprecationHandler::hasDeprecation('@Hash'));
        $this->assertTrue(DeprecationHandler::areWarningsSuppressed());
    }
    
    public function testPrefersAttributes(): void
    {
        $this->assertTrue($this->compatibilityLayer->prefersAttributes());
        
        $compatibilityLayerNoPreference = new CompatibilityLayer(true, false);
        $this->assertFalse($compatibilityLayerNoPreference->prefersAttributes());
    }
    
    public function testCompatibilityReport(): void
    {
        $className = get_class(new class {
            #[HashAttribute('id')]
            public function showAttribute(int $id): void
            {
            }
            
            /**
             * @Hash("id")
             */
            public function showAnnotation(int $id): void
            {
            }
            
            /**
             * @Hash("id")
             */
            #[HashAttribute('id')]
            public function showBoth(int $id): void
            {
            }
            
            public function showNone(): void
            {
            }
        });
        
        $report = $this->compatibilityLayer->getCompatibilityReport($className);
        
        $this->assertEquals($className, $report['class']);
        $this->assertTrue($report['uses_annotations']);
        $this->assertTrue($report['uses_attributes']);
        $this->assertTrue($report['has_duplicates']);
        
        $this->assertArrayHasKey('showAttribute', $report['methods']);
        $this->assertFalse($report['methods']['showAttribute']['uses_annotation']);
        $this->assertTrue($report['methods']['showAttribute']['uses_attribute']);
        $this->assertFalse($report['methods']['showAttribute']['is_duplicate']);
        
        $this->assertArrayHasKey('showAnnotation', $report['methods']);
        $this->assertTrue($report['methods']['showAnnotation']['uses_annotation']);
        $this->assertFalse($report['methods']['showAnnotation']['uses_attribute']);
        $this->assertFalse($report['methods']['showAnnotation']['is_duplicate']);
        
        $this->assertArrayHasKey('showBoth', $report['methods']);
        $this->assertTrue($report['methods']['showBoth']['uses_annotation']);
        $this->assertTrue($report['methods']['showBoth']['uses_attribute']);
        $this->assertTrue($report['methods']['showBoth']['is_duplicate']);
        
        $this->assertArrayNotHasKey('showNone', $report['methods']);
    }
}