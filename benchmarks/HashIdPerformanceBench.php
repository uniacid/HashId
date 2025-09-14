<?php

declare(strict_types=1);

namespace Pgs\HashIdBundle\Benchmarks;

use PhpBench\Benchmark\Metadata\Annotations\Iterations;
use PhpBench\Benchmark\Metadata\Annotations\Revs;
use PhpBench\Benchmark\Metadata\Annotations\Warmup;
use Hashids\Hashids;

/**
 * Performance benchmarks for HashId operations.
 */
class HashIdPerformanceBench
{
    private Hashids $hashids;
    private array $testData;

    public function __construct()
    {
        $this->hashids = new Hashids('test-salt', 8, 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ1234567890');
        
        // Generate test data
        $this->testData = [];
        for ($i = 1; $i <= 1000; $i++) {
            $this->testData[] = $i;
        }
    }

    /**
     * @Revs(100)
     * @Iterations(5)
     * @Warmup(2)
     */
    public function benchSingleEncode(): void
    {
        $this->hashids->encode(12345);
    }

    /**
     * @Revs(100)
     * @Iterations(5)
     * @Warmup(2)
     */
    public function benchSingleDecode(): void
    {
        $encoded = $this->hashids->encode(12345);
        $this->hashids->decode($encoded);
    }

    /**
     * @Revs(10)
     * @Iterations(3)
     * @Warmup(1)
     */
    public function benchBatchEncode(): void
    {
        foreach ($this->testData as $id) {
            $this->hashids->encode($id);
        }
    }

    /**
     * @Revs(10)
     * @Iterations(3)
     * @Warmup(1)
     */
    public function benchBatchDecode(): void
    {
        $encoded = [];
        foreach ($this->testData as $id) {
            $encoded[] = $this->hashids->encode($id);
        }
        
        foreach ($encoded as $hash) {
            $this->hashids->decode($hash);
        }
    }

    /**
     * @Revs(50)
     * @Iterations(3)
     * @Warmup(1)
     */
    public function benchMultipleParamsEncode(): void
    {
        $this->hashids->encode(123, 456, 789);
    }

    /**
     * @Revs(50)
     * @Iterations(3)
     * @Warmup(1)
     */
    public function benchMultipleParamsDecode(): void
    {
        $encoded = $this->hashids->encode(123, 456, 789);
        $this->hashids->decode($encoded);
    }

    /**
     * @Revs(20)
     * @Iterations(3)
     * @Warmup(1)
     */
    public function benchLargeNumberEncode(): void
    {
        $this->hashids->encode(PHP_INT_MAX - 1000);
    }

    /**
     * @Revs(20)
     * @Iterations(3)
     * @Warmup(1)
     */
    public function benchLargeNumberDecode(): void
    {
        $encoded = $this->hashids->encode(PHP_INT_MAX - 1000);
        $this->hashids->decode($encoded);
    }
}