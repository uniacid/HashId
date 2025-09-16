<?php

declare(strict_types=1);

namespace Pgs\HashIdBundle\Tests\Migration;

use ReflectionClass;
use Attribute;
use ReflectionProperty;
use PHPUnit\Framework\TestCase;
use Pgs\HashIdBundle\Annotation\Hash as AnnotationHash;
use Pgs\HashIdBundle\Attribute\Hash as AttributeHash;

class CompatibilityTest extends TestCase
{
    public function testAnnotationClassExists(): void
    {
        $this->assertTrue(
            class_exists(AnnotationHash::class),
            'Annotation Hash class should exist for v3.x compatibility'
        );
    }
    
    public function testAttributeClassExists(): void
    {
        $this->assertTrue(
            class_exists(AttributeHash::class),
            'Attribute Hash class should exist for v4.x'
        );
    }
    
    public function testAnnotationPropertiesMatch(): void
    {
        $annotationReflection = new ReflectionClass(AnnotationHash::class);
        $attributeReflection = new ReflectionClass(AttributeHash::class);
        
        // Both should have the same public properties/methods for compatibility
        $annotationProps = $this->getPublicProperties($annotationReflection);
        $attributeProps = $this->getPublicProperties($attributeReflection);
        
        // Core properties that must exist in both
        $coreProperties = ['parameters'];
        
        foreach ($coreProperties as $prop) {
            $this->assertContains(
                $prop,
                $annotationProps,
                sprintf('Annotation class should have %s property', $prop)
            );
            $this->assertContains(
                $prop,
                $attributeProps,
                sprintf('Attribute class should have %s property', $prop)
            );
        }
    }
    
    public function testBothSystemsCanBeInstantiated(): void
    {
        // Test annotation instantiation (old style)
        $annotation = new AnnotationHash(['id']);
        $this->assertInstanceOf(AnnotationHash::class, $annotation);
        $this->assertEquals(['id'], $annotation->getParameters());
        
        // Test attribute instantiation (new style)
        $attribute = new AttributeHash(['id']);
        $this->assertInstanceOf(AttributeHash::class, $attribute);
        $this->assertEquals(['id'], $attribute->getParameters());
    }
    
    public function testSingleParameterConstructor(): void
    {
        // Both should support single parameter as string
        $annotation = new AnnotationHash('id');
        $this->assertEquals(['id'], $annotation->getParameters());
        
        $attribute = new AttributeHash('id');
        $this->assertEquals(['id'], $attribute->getParameters());
    }
    
    public function testMultipleParametersConstructor(): void
    {
        // Both should support multiple parameters as array
        $params = ['id', 'otherId', 'thirdId'];
        
        $annotation = new AnnotationHash($params);
        $this->assertEquals($params, $annotation->getParameters());
        
        $attribute = new AttributeHash($params);
        $this->assertEquals($params, $attribute->getParameters());
    }
    
    public function testAttributeHasCorrectTarget(): void
    {
        $reflection = new ReflectionClass(AttributeHash::class);
        $attributes = $reflection->getAttributes(Attribute::class);
        
        $this->assertNotEmpty($attributes, 'Hash attribute should have #[Attribute] declaration');
        
        if (!empty($attributes)) {
            $attributeInstance = $attributes[0]->newInstance();
            // Check that it targets methods (for controller actions)
            $this->assertTrue(
                ($attributeInstance->flags & Attribute::TARGET_METHOD) !== 0,
                'Hash attribute should target methods'
            );
        }
    }
    
    public function testServiceDefinitionsExist(): void
    {
        $servicesFile = dirname(__DIR__, 2) . '/src/Resources/config/services.yaml';
        if (!file_exists($servicesFile)) {
            $servicesFile = dirname(__DIR__, 2) . '/src/Resources/config/services.xml';
        }
        
        $this->assertFileExists($servicesFile, 'Service definitions file should exist');
        
        $content = file_get_contents($servicesFile);
        
        // Check for key services
        $requiredServices = [
            'AnnotationProvider',
            'ParametersProcessor',
            'RouterDecorator'
        ];
        
        foreach ($requiredServices as $service) {
            $this->assertStringContainsString(
                $service,
                $content,
                sprintf('Service definitions should include %s', $service)
            );
        }
    }
    
    public function testConfigurationCompatibility(): void
    {
        // Test that configuration supports both old and new features
        $configFile = dirname(__DIR__, 2) . '/src/DependencyInjection/Configuration.php';
        $this->assertFileExists($configFile, 'Configuration class should exist');
        
        $content = file_get_contents($configFile);
        
        // Check for compatibility settings
        $this->assertStringContainsString('compatibility', $content, 'Configuration should have compatibility section');
        
        // Check for core settings that must be maintained
        $coreSettings = ['salt', 'min_hash_length', 'alphabet'];
        foreach ($coreSettings as $setting) {
            $this->assertStringContainsString(
                $setting,
                $content,
                sprintf('Configuration should maintain %s setting', $setting)
            );
        }
    }
    
    public function testDeprecationMessagesExist(): void
    {
        // Check that deprecation messages are properly defined
        $annotationFile = dirname(__DIR__, 2) . '/src/Annotation/Hash.php';
        $this->assertFileExists($annotationFile);
        
        $content = file_get_contents($annotationFile);
        
        // Should have deprecation notice
        $this->assertMatchesRegularExpression(
            '/@deprecated|trigger_error.*E_USER_DEPRECATED/',
            $content,
            'Annotation class should have deprecation notice'
        );
    }
    
    private function getPublicProperties(ReflectionClass $reflection): array
    {
        $properties = [];
        foreach ($reflection->getProperties(ReflectionProperty::IS_PUBLIC) as $prop) {
            $properties[] = $prop->getName();
        }
        return $properties;
    }
}