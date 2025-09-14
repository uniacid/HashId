<?php declare(strict_types=1);

namespace Pgs\HashIdBundle\Tests\ParametersProcessor;

use Pgs\HashIdBundle\ParametersProcessor\Converter\ConverterInterface;
use Pgs\HashIdBundle\ParametersProcessor\Encode;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class EncodeTest extends TestCase
{
    /**
     * @dataProvider encodeParametersDataProvider
     */
    public function testEncode(array $parametersToEncode, array $routeParameters, array $expected)
    {
        $encodeParametersProcessor = new Encode($this->getConverterMock(), $parametersToEncode);
        $processedParameters = $encodeParametersProcessor->process($routeParameters);
        self::assertSame($expected, $processedParameters);
        self::assertSame(\count($parametersToEncode) > 0, $encodeParametersProcessor->needToProcess());
    }

    /**
     * @return ConverterInterface|MockObject
     */
    protected function getConverterMock()
    {
        $mock = $this->getMockBuilder(ConverterInterface::class)->setMethods(['encode'])->getMockForAbstractClass();
        $mock
            ->method('encode')
            ->withAnyParameters()
            ->willReturn('encoded');

        return $mock;
    }

    public function encodeParametersDataProvider()
    {
        return [
            [
                ['id'],
                ['id' => 10, 'slug' => 'slug'],
                ['id' => 'encoded', 'slug' => 'slug'],
            ],
            [
                [],
                ['id' => 10, 'slug' => 'slug'],
                ['id' => 10, 'slug' => 'slug'],
            ],
            [
                ['foo'],
                ['id' => 10, 'slug' => 'slug'],
                ['id' => 10, 'slug' => 'slug'],
            ],
        ];
    }
}
