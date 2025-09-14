<?php declare(strict_types=1);

namespace Pgs\HashIdBundle\Tests\ParametersProcessor;

use Pgs\HashIdBundle\ParametersProcessor\Converter\ConverterInterface;
use Pgs\HashIdBundle\ParametersProcessor\Decode;
use Pgs\HashIdBundle\ParametersProcessor\ParametersProcessorInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class DecodeTest extends TestCase
{
    /**
     * @dataProvider decodeParametersDataProvider
     */
    public function testDecode(array $parametersToDecode, array $routeParameters, array $expected)
    {
        $decodeParametersProcessor = new Decode($this->getConverterMock(), $parametersToDecode);
        $processedParameters = $decodeParametersProcessor->process($routeParameters);
        self::assertSame($expected, $processedParameters);
    }

    public function testSetParametersToProcess()
    {
        $decodeParametersProcessor = new Decode($this->getConverterMock(), []);
        $parametersToProcess = ['param1', 'param2'];
        $result = $decodeParametersProcessor->setParametersToProcess($parametersToProcess);
        self::assertTrue($result instanceof ParametersProcessorInterface);
        self::assertSame($parametersToProcess, $decodeParametersProcessor->getParametersToProcess());
    }

    /**
     * @return ConverterInterface|MockObject
     */
    protected function getConverterMock()
    {
        $mock = $this->getMockBuilder(ConverterInterface::class)->onlyMethods(['decode'])->getMockForAbstractClass();
        $mock
            ->method('decode')
            ->withAnyParameters()
            ->willReturn(10);

        return $mock;
    }

    public function decodeParametersDataProvider()
    {
        return [
            [
                ['id'],
                ['id' => 'encoded', 'slug' => 'slug'],
                ['id' => 10, 'slug' => 'slug'],
            ],
            [
                [],
                ['id' => 'encoded', 'slug' => 'slug'],
                ['id' => 'encoded', 'slug' => 'slug'],
            ],
            [
                ['foo'],
                ['id' => 10, 'slug' => 'slug'],
                ['id' => 10, 'slug' => 'slug'],
            ],
        ];
    }
}
