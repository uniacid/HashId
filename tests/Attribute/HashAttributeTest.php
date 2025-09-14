<?php

declare(strict_types=1);

namespace Pgs\HashIdBundle\Tests\Attribute;

use Pgs\HashIdBundle\Attribute\Hash;
use PHPUnit\Framework\TestCase;

class HashAttributeTest extends TestCase
{
    public function testAttributeWithSingleParameter(): void
    {
        $attribute = new Hash('id');
        
        $this->assertInstanceOf(Hash::class, $attribute);
        $this->assertEquals(['id'], $attribute->getParameters());
    }
    
    public function testAttributeWithMultipleParameters(): void
    {
        $attribute = new Hash(['id', 'parentId', 'userId']);
        
        $this->assertInstanceOf(Hash::class, $attribute);
        $this->assertEquals(['id', 'parentId', 'userId'], $attribute->getParameters());
    }
    
    public function testAttributeTargets(): void
    {
        $reflectionClass = new \ReflectionClass(Hash::class);
        $attributes = $reflectionClass->getAttributes(\Attribute::class);
        
        $this->assertCount(1, $attributes);
        
        $attributeInstance = $attributes[0]->newInstance();
        // Hash attribute supports both CLASS and METHOD targets
        $this->assertEquals(\Attribute::TARGET_CLASS | \Attribute::TARGET_METHOD, $attributeInstance->flags);
    }
    
    public function testAttributeOnMethod(): void
    {
        $controller = new class {
            #[Hash('id')]
            public function show(int $id): void
            {
            }
            
            #[Hash(['id', 'categoryId'])]
            public function showInCategory(int $id, int $categoryId): void
            {
            }
        };
        
        $reflectionClass = new \ReflectionClass($controller);
        
        // Test first method
        $showMethod = $reflectionClass->getMethod('show');
        $hashAttributes = $showMethod->getAttributes(Hash::class);
        $this->assertCount(1, $hashAttributes);
        
        $hashAttribute = $hashAttributes[0]->newInstance();
        $this->assertEquals(['id'], $hashAttribute->getParameters());
        
        // Test second method
        $showInCategoryMethod = $reflectionClass->getMethod('showInCategory');
        $hashAttributes = $showInCategoryMethod->getAttributes(Hash::class);
        $this->assertCount(1, $hashAttributes);
        
        $hashAttribute = $hashAttributes[0]->newInstance();
        $this->assertEquals(['id', 'categoryId'], $hashAttribute->getParameters());
    }
    
    public function testAttributeAbsenceOnMethod(): void
    {
        $controller = new class {
            public function index(): void
            {
            }
        };
        
        $reflectionClass = new \ReflectionClass($controller);
        $indexMethod = $reflectionClass->getMethod('index');
        $hashAttributes = $indexMethod->getAttributes(Hash::class);
        
        $this->assertCount(0, $hashAttributes);
    }
}