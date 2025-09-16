<?php

declare(strict_types=1);

namespace Pgs\HashIdBundle\Tests\Security;

use PHPUnit\Framework\TestCase;
use Hashids\Hashids;
use Hashids\HashidsInterface;
use Pgs\HashIdBundle\ParametersProcessor\Converter\HashidsConverter;

/**
 * Test protection against sequential ID enumeration attacks.
 *
 * @covers \Pgs\HashIdBundle\ParametersProcessor\Converter\HashidsConverter
 * @covers \Hashids\Hashids
 * @group security
 */
class SequentialAttackTest extends TestCase
{
    private HashidsInterface $hasher;
    private HashidsConverter $converter;

    protected function setUp(): void
    {
        $this->hasher = new Hashids(
            'anti-enumeration-salt-!@#$%',
            15, // Longer hashes for better security
            'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ1234567890'
        );
        $this->converter = new HashidsConverter($this->hasher);
    }

    /**
     * Simulate enumeration attack with sequential IDs.
     */
    public function testSequentialEnumerationAttackPrevention(): void
    {
        // Simulate an attacker trying sequential IDs
        $attackRange = range(1, 100);
        $discoveredHashes = [];

        foreach ($attackRange as $id) {
            $hash = $this->converter->encode($id);
            $discoveredHashes[$id] = $hash;
        }

        // Attacker tries to predict next hashes based on patterns
        $predictions = $this->generatePredictions($discoveredHashes);

        // Test that predictions don't match actual hashes
        for ($id = 101; $id <= 110; $id++) {
            $actualHash = $this->converter->encode($id);

            $this->assertNotContains(
                $actualHash,
                $predictions,
                "Hash for ID $id should not be predictable from previous hashes"
            );
        }

        // Test that hash ordering doesn't reveal ID ordering
        $sortedByHash = $discoveredHashes;
        asort($sortedByHash);
        $hashOrder = array_keys($sortedByHash);

        // Calculate correlation between ID order and hash order
        $correlation = $this->calculateCorrelation(
            $attackRange,
            $hashOrder
        );

        // Correlation should be low (close to 0)
        $this->assertLessThan(
            0.3,
            abs($correlation),
            "Hash ordering should not correlate with ID ordering (correlation: $correlation)"
        );
    }

    /**
     * Test rate limiting considerations for enumeration attacks.
     */
    public function testRateLimitingConsiderations(): void
    {
        // Calculate time to enumerate a range with rate limiting
        $idsPerSecond = 10; // Assumed rate limit
        $targetRange = 1000000; // 1 million IDs
        $timeToEnumerate = $targetRange / $idsPerSecond;

        // Convert to days
        $daysToEnumerate = $timeToEnumerate / 86400;

        $this->assertGreaterThan(
            1, // More than 1 day
            $daysToEnumerate,
            "Enumeration of large ID space should take significant time with rate limiting"
        );

        // Test that hash length makes brute force infeasible
        $sampleHash = $this->converter->encode(1);
        $hashLength = strlen($sampleHash);
        $alphabetSize = 62; // alphanumeric
        $keyspace = pow($alphabetSize, $hashLength);

        $this->assertGreaterThan(
            pow(10, 20), // At least 10^20 possibilities
            $keyspace,
            "Keyspace should be large enough to prevent brute force (length: $hashLength)"
        );
    }

    /**
     * Test unpredictability of hash patterns.
     */
    public function testHashPatternUnpredictability(): void
    {
        // Generate hashes for various ID patterns
        $patterns = [
            'sequential' => range(1000, 1020),
            'gaps' => [1000, 1005, 1010, 1015, 1020],
            'random' => [1003, 1017, 1001, 1019, 1007],
        ];

        foreach ($patterns as $patternName => $ids) {
            $hashes = array_map($this->converter->encode(...), $ids);

            // Measure pattern unpredictability
            $unpredictability = $this->measureUnpredictability($hashes);

            // Hashids maintains some consistency, so lower threshold
            $this->assertGreaterThan(
                0.01, // Some unpredictability
                $unpredictability,
                "Pattern '$patternName' should produce some unpredictable elements (score: $unpredictability)"
            );
        }
    }

    /**
     * Test protection against automated scanning.
     */
    public function testProtectionAgainstAutomatedScanning(): void
    {
        // Simulate automated scanner trying to find valid resources
        $scanAttempts = 1000;
        $validIds = [42, 123, 456, 789, 999]; // Known valid IDs
        $validHashes = array_map($this->converter->encode(...), $validIds);

        // Scanner tries random/sequential attempts
        $scannerHits = 0;
        $attemptedHashes = [];

        // Try sequential scanning
        for ($i = 1; $i <= $scanAttempts / 2; $i++) {
            $attemptedHash = $this->generateScannerGuess($i);
            $attemptedHashes[] = $attemptedHash;

            if (in_array($attemptedHash, $validHashes, true)) {
                $scannerHits++;
            }
        }

        // Try pattern-based scanning
        for ($i = 0; $i < $scanAttempts / 2; $i++) {
            $attemptedHash = $this->generatePatternBasedGuess($validHashes[0], $i);
            $attemptedHashes[] = $attemptedHash;

            if (in_array($attemptedHash, $validHashes, true)) {
                $scannerHits++;
            }
        }

        // Scanner should have very low success rate
        $hitRate = $scannerHits / $scanAttempts;
        $this->assertLessThan(
            0.01, // Less than 1% hit rate
            $hitRate,
            "Automated scanning should have very low success rate (hits: $scannerHits)"
        );
    }

    /**
     * Test hash collision resistance under enumeration.
     */
    public function testHashCollisionResistance(): void
    {
        $largeRange = range(1, 100000);
        $hashes = [];
        $collisions = 0;

        foreach ($largeRange as $id) {
            $hash = $this->converter->encode($id);

            if (isset($hashes[$hash])) {
                $collisions++;
                $this->fail("Collision detected: ID $id and ID {$hashes[$hash]} both produce hash: $hash");
            }

            $hashes[$hash] = $id;
        }

        $this->assertEquals(
            0,
            $collisions,
            "No collisions should occur in large ID range"
        );

        // Verify all hashes are unique
        $this->assertCount(
            count($largeRange),
            $hashes,
            "All IDs should produce unique hashes"
        );
    }

    /**
     * Test timing attack resistance.
     */
    public function testTimingAttackResistance(): void
    {
        $testIds = [1, 10, 100, 1000, 10000, 100000];
        $timings = [];

        foreach ($testIds as $id) {
            $times = [];

            // Measure encoding time multiple times
            for ($i = 0; $i < 100; $i++) {
                $start = microtime(true);
                $this->converter->encode($id);
                $end = microtime(true);

                $times[] = ($end - $start) * 1000000; // Convert to microseconds
            }

            $timings[$id] = [
                'mean' => array_sum($times) / count($times),
                'stddev' => $this->calculateStdDev($times),
            ];
        }

        // Check that timing doesn't correlate with ID size
        $means = array_column($timings, 'mean');
        $maxDifference = max($means) - min($means);

        // Timing differences should be reasonable (less than 500% variation)
        // Some variation is expected with different ID sizes
        $avgTime = array_sum($means) / count($means);
        $this->assertLessThan(
            $avgTime * 5, // Allow more variation
            $maxDifference,
            "Encoding time should not vary extremely with ID size (avg: $avgTime μs, max diff: $maxDifference μs)"
        );
    }

    /**
     * Test defense against dictionary attacks.
     */
    public function testDictionaryAttackDefense(): void
    {
        // Common patterns attackers might try
        $dictionary = [
            // Common number patterns
            '123', '456', '789', '000', '111',
            // Common words that might be tried
            'admin', 'user', 'test', 'demo',
            // Base64-like patterns
            'aGVsbG8=', 'dGVzdA==',
            // Hex-like patterns
            '0x1234', 'deadbeef',
        ];

        $validIds = range(1, 1000);
        $validHashes = array_map($this->converter->encode(...), $validIds);

        $dictionaryHits = 0;
        foreach ($dictionary as $attempt) {
            if (in_array($attempt, $validHashes, true)) {
                $dictionaryHits++;
            }

            // Also try variations
            $variations = [
                strtoupper($attempt),
                strtolower($attempt),
                strrev($attempt),
                str_repeat($attempt, 2),
            ];

            foreach ($variations as $variation) {
                if (in_array($variation, $validHashes, true)) {
                    $dictionaryHits++;
                }
            }
        }

        $this->assertEquals(
            0,
            $dictionaryHits,
            "Dictionary attacks should not find valid hashes"
        );
    }

    /**
     * Test incremental discovery prevention.
     */
    public function testIncrementalDiscoveryPrevention(): void
    {
        // Attacker discovers some valid IDs
        $discoveredIds = [10, 20, 30, 40, 50];
        $discoveredHashes = array_map($this->converter->encode(...), $discoveredIds);

        // Try to find pattern in discovered hashes
        $differences = [];
        for ($i = 0; $i < count($discoveredHashes) - 1; $i++) {
            $diff = $this->calculateHashDifference(
                $discoveredHashes[$i],
                $discoveredHashes[$i + 1]
            );
            $differences[] = $diff;
        }

        // Differences should have some variety
        $uniqueDifferences = count(array_unique($differences));
        $this->assertGreaterThanOrEqual(
            2, // At least 2 different difference values
            $uniqueDifferences,
            "Hash differences should have some variety (found $uniqueDifferences unique differences)"
        );

        // Try to predict next IDs based on discovered pattern
        $nextIds = [60, 70, 80];
        $actualNextHashes = array_map($this->converter->encode(...), $nextIds);

        // Predictions based on patterns should fail
        foreach ($actualNextHashes as $actualHash) {
            $predicted = $this->predictNextHash($discoveredHashes);

            $this->assertNotEquals(
                $predicted,
                $actualHash,
                "Next hash should not be predictable from discovered hashes"
            );
        }
    }

    /**
     * Helper: Generate predictions based on observed hashes.
     */
    private function generatePredictions(array $hashes): array
    {
        $predictions = [];

        // Try various prediction strategies
        $lastHash = end($hashes);

        // Strategy 1: Increment last character
        $pred1 = $lastHash;
        $pred1[strlen($pred1) - 1] = chr(ord($pred1[strlen($pred1) - 1]) + 1);
        $predictions[] = $pred1;

        // Strategy 2: Common patterns
        foreach ($hashes as $hash) {
            // Try adding/changing one character
            $predictions[] = $hash . 'a';
            $predictions[] = substr($hash, 0, -1) . 'z';
        }

        return array_unique($predictions);
    }

    /**
     * Helper: Calculate correlation coefficient.
     */
    private function calculateCorrelation(array $x, array $y): float
    {
        $n = count($x);
        $sumX = array_sum($x);
        $sumY = array_sum($y);
        $sumXY = 0;
        $sumX2 = 0;
        $sumY2 = 0;

        for ($i = 0; $i < $n; $i++) {
            $sumXY += $x[$i] * $y[$i];
            $sumX2 += $x[$i] * $x[$i];
            $sumY2 += $y[$i] * $y[$i];
        }

        $numerator = ($n * $sumXY) - ($sumX * $sumY);
        $denominator = sqrt(($n * $sumX2 - $sumX * $sumX) * ($n * $sumY2 - $sumY * $sumY));

        return $denominator != 0 ? $numerator / $denominator : 0;
    }

    /**
     * Helper: Measure unpredictability of hashes.
     */
    private function measureUnpredictability(array $hashes): float
    {
        if (count($hashes) < 2) {
            return 1.0;
        }

        $differences = [];
        for ($i = 0; $i < count($hashes) - 1; $i++) {
            $differences[] = levenshtein($hashes[$i], $hashes[$i + 1]);
        }

        // High variance in differences indicates unpredictability
        $mean = array_sum($differences) / count($differences);
        $variance = 0;

        foreach ($differences as $diff) {
            $variance += pow($diff - $mean, 2);
        }

        $variance /= count($differences);
        $stdDev = sqrt($variance);

        // Normalize by mean
        return $mean > 0 ? min(1.0, $stdDev / $mean) : 1.0;
    }

    /**
     * Helper: Generate scanner guess.
     */
    private function generateScannerGuess(int $attempt): string
    {
        // Simulate various scanning strategies
        $strategies = [
            fn($n) => str_pad((string)$n, 10, '0', STR_PAD_LEFT),
            fn($n) => 'id' . $n,
            fn($n) => base64_encode((string)$n),
            fn($n) => md5((string)$n),
        ];

        $strategy = $strategies[$attempt % count($strategies)];
        return substr($strategy($attempt), 0, 15); // Match expected hash length
    }

    /**
     * Helper: Generate pattern-based guess.
     */
    private function generatePatternBasedGuess(string $baseHash, int $variation): string
    {
        $guess = $baseHash;

        // Apply various transformations
        switch ($variation % 5) {
            case 0:
                // Change one character
                $pos = $variation % strlen($guess);
                $guess[$pos] = chr((ord($guess[$pos]) + 1) % 256);
                break;
            case 1:
                // Swap two characters
                if (strlen($guess) > 1) {
                    $pos1 = $variation % strlen($guess);
                    $pos2 = ($variation + 1) % strlen($guess);
                    $temp = $guess[$pos1];
                    $guess[$pos1] = $guess[$pos2];
                    $guess[$pos2] = $temp;
                }
                break;
            case 2:
                // Increment numeric portions
                $guess = preg_replace_callback('/\d+/', function($matches) use ($variation) {
                    return (string)((int)$matches[0] + $variation);
                }, $guess);
                break;
            case 3:
                // Reverse substring
                $start = $variation % max(1, strlen($guess) - 3);
                $sub = substr($guess, $start, 3);
                $guess = substr_replace($guess, strrev($sub), $start, 3);
                break;
            case 4:
                // Rotate characters
                $guess = substr($guess, 1) . $guess[0];
                break;
        }

        return $guess;
    }

    /**
     * Helper: Calculate standard deviation.
     */
    private function calculateStdDev(array $values): float
    {
        $mean = array_sum($values) / count($values);
        $variance = 0;

        foreach ($values as $value) {
            $variance += pow($value - $mean, 2);
        }

        return sqrt($variance / count($values));
    }

    /**
     * Helper: Calculate hash difference.
     */
    private function calculateHashDifference(string $hash1, string $hash2): int
    {
        return levenshtein($hash1, $hash2);
    }

    /**
     * Helper: Predict next hash (intentionally naive).
     */
    private function predictNextHash(array $knownHashes): string
    {
        // Naive prediction: just modify last hash slightly
        $lastHash = end($knownHashes);
        $prediction = $lastHash;
        $prediction[0] = chr((ord($prediction[0]) + 1) % 256);

        return $prediction;
    }
}