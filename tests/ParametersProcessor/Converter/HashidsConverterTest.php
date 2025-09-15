<?php declare(strict_types=1);

namespace Pgs\HashIdBundle\Tests\ParametersProcessor\Converter;

use Hashids\HashidsInterface;
use Pgs\HashIdBundle\ParametersProcessor\Converter\HashidsConverter;
use PHPUnit\Framework\TestCase;

/**
 * Comprehensive test suite for HashidsConverter.
 *
 * @covers \Pgs\HashIdBundle\ParametersProcessor\Converter\HashidsConverter
 */
class HashidsConverterTest extends TestCase
{
    private HashidsInterface $hashids;
    private HashidsConverter $converter;
    
    protected function setUp(): void
    {
        $this->hashids = $this->createMock(HashidsInterface::class);
        $this->converter = new HashidsConverter($this->hashids);
    }
    
    /**
     * Test encoding delegates to Hashids.
     */
    public function testEncodeDelegatesToHashids(): void
    {
        $value = 123;
        $expectedHash = 'abc123xyz';
        
        $this->hashids->expects($this->once())
            ->method('encode')
            ->with($value)
            ->willReturn($expectedHash);
        
        $result = $this->converter->encode($value);
        
        $this->assertSame($expectedHash, $result);
    }
    
    /**
     * Test encoding array delegates to Hashids.
     */
    public function testEncodeArrayDelegatesToHashids(): void
    {
        $values = [123, 456, 789];
        $expectedHash = 'xyz789abc';
        
        $this->hashids->expects($this->once())
            ->method('encode')
            ->with($values)
            ->willReturn($expectedHash);
        
        $result = $this->converter->encode($values);
        
        $this->assertSame($expectedHash, $result);
    }
    
    /**
     * Test decoding single value.
     */
    public function testDecodeSingleValue(): void
    {
        $hash = 'abc123xyz';
        $expectedValues = [123];
        
        $this->hashids->expects($this->once())
            ->method('decode')
            ->with($hash)
            ->willReturn($expectedValues);
        
        $result = $this->converter->decode($hash);
        
        $this->assertSame(123, $result);
    }
    
    /**
     * Test decoding returns original value when decode returns empty array.
     */
    public function testDecodeReturnsOriginalWhenEmpty(): void
    {
        $hash = 'invalid-hash';
        
        $this->hashids->expects($this->once())
            ->method('decode')
            ->with($hash)
            ->willReturn([]);
        
        $result = $this->converter->decode($hash);
        
        $this->assertSame('invalid-hash', $result);
    }
    
    /**
     * Test decoding returns first value from array.
     */
    public function testDecodeReturnsFirstValue(): void
    {
        $hash = 'multi-hash';
        $expectedValues = [123, 456, 789];
        
        $this->hashids->expects($this->once())
            ->method('decode')
            ->with($hash)
            ->willReturn($expectedValues);
        
        $result = $this->converter->decode($hash);
        
        // Only returns the first value
        $this->assertSame(123, $result);
    }
    
    /**
     * Test encoding with various input types.
     *
     * @dataProvider encodeDataProvider
     */
    public function testEncodeVariousTypes(mixed $input, string $expectedOutput): void
    {
        $this->hashids->expects($this->once())
            ->method('encode')
            ->with($input)
            ->willReturn($expectedOutput);
        
        $result = $this->converter->encode($input);
        
        $this->assertSame($expectedOutput, $result);
    }
    
    /**
     * Data provider for encode tests.
     */
    public static function encodeDataProvider(): array
    {
        return [
            'integer' => [123, 'hash123'],
            'zero' => [0, 'hash0'],
            'string number' => ['456', 'hash456'],
            'float' => [78.9, 'hash78'],
            'array' => [[1, 2, 3], 'hashArray'],
            'mixed value' => ['text', 'hashText'],
        ];
    }
    
    /**
     * Test decoding with various hash types.
     *
     * @dataProvider decodeDataProvider
     */
    public function testDecodeVariousHashes(string $hash, array $decodeResult, mixed $expectedOutput): void
    {
        $this->hashids->expects($this->once())
            ->method('decode')
            ->with($hash)
            ->willReturn($decodeResult);
        
        $result = $this->converter->decode($hash);
        
        $this->assertSame($expectedOutput, $result);
    }
    
    /**
     * Data provider for decode tests.
     */
    public static function decodeDataProvider(): array
    {
        return [
            'valid hash single' => ['validHash', [999], 999],
            'valid hash multiple' => ['multiHash', [111, 222, 333], 111],
            'invalid hash' => ['invalidHash', [], 'invalidHash'],
            'empty string' => ['', [], ''],
            'special characters' => ['hash-123', [], 'hash-123'],
        ];
    }
}