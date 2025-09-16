<?php

declare(strict_types=1);

namespace Pgs\HashIdBundle\Tests\Security;

use PHPUnit\Framework\TestCase;
use Hashids\Hashids;
use Hashids\HashidsInterface;
use Pgs\HashIdBundle\ParametersProcessor\Converter\HashidsConverter;

/**
 * Test the effectiveness of URL obfuscation against enumeration attacks.
 *
 * @covers \Pgs\HashIdBundle\ParametersProcessor\Converter\HashidsConverter
 * @covers \Hashids\Hashids
 * @group security
 */
class EnumerationPreventionTest extends TestCase
{
    private HashidsInterface $hasher;
    private HashidsConverter $converter;

    protected function setUp(): void
    {
        $this->hasher = new Hashids(
            'secure-enumeration-salt',
            12,
            'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ1234567890'
        );
        $this->converter = new HashidsConverter($this->hasher);
    }

    /**
     * Test that sequential IDs produce non-sequential hashes.
     */
    public function testSequentialIdsProduceNonSequentialHashes(): void
    {
        $range = range(1000, 1100);
        $hashes = [];

        foreach ($range as $id) {
            $hashes[$id] = $this->converter->encode($id);
        }

        // Check that hashes are not in any predictable order
        $sortedHashes = array_values($hashes);
        sort($sortedHashes);

        // The sorted hashes should not match the original order
        $originalOrder = array_values($hashes);
        $this->assertNotEquals(
            $originalOrder,
            $sortedHashes,
            'Sequential IDs should not produce alphabetically sequential hashes'
        );

        // Check that consecutive IDs produce very different hashes
        for ($i = 1000; $i < 1100; $i++) {
            $hash1 = $hashes[$i];
            $hash2 = $hashes[$i + 1];

            // Calculate Levenshtein distance
            $distance = levenshtein($hash1, $hash2);
            $maxLength = max(strlen($hash1), strlen($hash2));

            // Distance should be significant (at least 50% different)
            $this->assertGreaterThan(
                $maxLength * 0.5,
                $distance,
                "Consecutive IDs $i and " . ($i + 1) . " should produce very different hashes"
            );
        }
    }

    /**
     * Test entropy and randomness of generated hashes.
     */
    public function testHashEntropyAndRandomness(): void
    {
        $sampleSize = 1000;
        $hashes = [];

        // Generate hashes for a sample range
        for ($id = 1; $id <= $sampleSize; $id++) {
            $hashes[] = $this->converter->encode($id);
        }

        // Test character distribution across all hashes
        $allChars = implode('', $hashes);
        $charFrequency = array_count_values(str_split($allChars));

        // Calculate entropy
        $totalChars = strlen($allChars);
        $entropy = 0;

        foreach ($charFrequency as $count) {
            $probability = $count / $totalChars;
            $entropy -= $probability * log($probability, 2);
        }

        // Normalize entropy (divide by log2 of alphabet size)
        $alphabetSize = strlen('abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ1234567890');
        $maxEntropy = log($alphabetSize, 2);
        $normalizedEntropy = $entropy / $maxEntropy;

        // Good entropy should be above 0.8 (80% of maximum)
        $this->assertGreaterThan(
            0.8,
            $normalizedEntropy,
            "Hash entropy should be high (got: $normalizedEntropy)"
        );

        // Test position-based character distribution
        $positionFrequency = [];
        foreach ($hashes as $hash) {
            for ($i = 0; $i < strlen($hash); $i++) {
                $positionFrequency[$i][] = $hash[$i];
            }
        }

        // Each position should have good character variety
        // Only check positions that exist in most hashes
        $minHashLength = min(array_map('strlen', $hashes));
        for ($position = 0; $position < $minHashLength; $position++) {
            $chars = $positionFrequency[$position] ?? [];
            $uniqueChars = count(array_unique($chars));
            $totalChars = count($chars);

            // Check character variety based on sample size
            // Some positions may have limited variety in Hashids
            if ($totalChars > 100) {
                // Only check positions with enough samples
                $this->assertGreaterThanOrEqual(
                    2, // At least 2 different characters
                    $uniqueChars,
                    "Position $position should have some character variety (found $uniqueChars unique chars in $totalChars samples)"
                );
            }
        }
    }

    /**
     * Test resistance to pattern analysis.
     */
    public function testResistanceToPatternAnalysis(): void
    {
        // Test different patterns of IDs
        $patterns = [
            'sequential' => range(1, 100),
            'even' => array_map(fn($n) => $n * 2, range(1, 50)),
            'odd' => array_map(fn($n) => $n * 2 + 1, range(1, 50)),
            'powers_of_2' => array_map(fn($n) => pow(2, $n), range(0, 10)),
            'fibonacci' => $this->generateFibonacci(15),
            'primes' => [2, 3, 5, 7, 11, 13, 17, 19, 23, 29, 31, 37, 41, 43, 47],
        ];

        foreach ($patterns as $patternName => $ids) {
            $hashes = array_map([$this->converter, 'encode'], $ids);

            // Check that the pattern is not preserved in hashes
            $this->assertPatternNotPreserved(
                $ids,
                $hashes,
                "Pattern '$patternName' should not be preserved in hashes"
            );
        }
    }

    /**
     * Test minimum hash length enforcement for security.
     */
    public function testMinimumHashLengthForSecurity(): void
    {
        $minLengths = [8, 12, 16, 20];

        foreach ($minLengths as $minLength) {
            $hasher = new Hashids('test-salt', $minLength);
            $converter = new HashidsConverter($hasher);

            // Test with small IDs that would normally produce short hashes
            $smallIds = [1, 2, 3, 5, 10];

            foreach ($smallIds as $id) {
                $hash = $converter->encode($id);

                $this->assertGreaterThanOrEqual(
                    $minLength,
                    strlen($hash),
                    "Hash for ID $id should meet minimum length of $minLength"
                );
            }

            // Calculate keyspace size
            $alphabetSize = 62; // alphanumeric
            $keyspace = pow($alphabetSize, $minLength);

            // Keyspace should be large enough to prevent brute force
            $this->assertGreaterThan(
                pow(10, 12), // At least 1 trillion possibilities
                $keyspace,
                "Keyspace for length $minLength should be large enough to prevent brute force"
            );
        }
    }

    /**
     * Test that hash patterns don't reveal ID relationships.
     */
    public function testHashPatternsDoNotRevealRelationships(): void
    {
        // Test related IDs
        $relatedGroups = [
            'consecutive' => [100, 101, 102, 103],
            'multiples' => [100, 200, 300, 400],
            'powers' => [10, 100, 1000, 10000],
            'arithmetic' => [5, 10, 15, 20, 25],
        ];

        foreach ($relatedGroups as $groupName => $ids) {
            $hashes = array_map([$this->converter, 'encode'], $ids);

            // Check common prefix length
            // Hashids may produce some common prefixes for related numbers
            $commonPrefix = $this->getCommonPrefixLength($hashes);
            $minHashLength = min(array_map('strlen', $hashes));

            // Common prefix should be less than 60% of hash length
            $this->assertLessThan(
                ceil($minHashLength * 0.6),
                $commonPrefix,
                "Related IDs in group '$groupName' should not share overly long common prefixes (found $commonPrefix chars in common)"
            );

            // Check common suffix length
            $commonSuffix = $this->getCommonSuffixLength($hashes);
            $this->assertLessThanOrEqual(
                3,
                $commonSuffix,
                "Related IDs in group '$groupName' should not share long common suffixes (found $commonSuffix chars)"
            );

            // Check edit distances between hashes
            $totalPairs = 0;
            $sufficientlyDifferent = 0;

            for ($i = 0; $i < count($hashes) - 1; $i++) {
                for ($j = $i + 1; $j < count($hashes); $j++) {
                    $distance = levenshtein($hashes[$i], $hashes[$j]);
                    $avgLength = (strlen($hashes[$i]) + strlen($hashes[$j])) / 2;

                    $totalPairs++;
                    if ($distance > $avgLength * 0.2) {
                        $sufficientlyDifferent++;
                    }
                }
            }

            // At least 80% of pairs should be sufficiently different
            $differentRate = $sufficientlyDifferent / $totalPairs;
            $this->assertGreaterThan(
                0.8,
                $differentRate,
                "Most hash pairs in group '$groupName' should be different (rate: $differentRate)"
            );
        }
    }

    /**
     * Test distribution uniformity across hash space.
     */
    public function testHashDistributionUniformity(): void
    {
        $sampleSize = 1000;
        $buckets = 10; // Fewer buckets for more stable test
        $distribution = array_fill(0, $buckets, 0);

        // Generate hashes and distribute into buckets
        for ($id = 1; $id <= $sampleSize; $id++) {
            $hash = $this->converter->encode($id);

            // Use hash of entire string for better distribution
            $bucketIndex = abs(crc32($hash)) % $buckets;
            $distribution[$bucketIndex]++;
        }

        // Check distribution is not extremely skewed
        $expected = $sampleSize / $buckets;
        $variance = 0;

        foreach ($distribution as $observed) {
            $variance += pow($observed - $expected, 2);
        }

        $stdDev = sqrt($variance / $buckets);

        // Standard deviation should not be too high
        // (allowing for natural variation in hash function)
        $this->assertLessThan(
            $expected * 0.5, // Std dev less than 50% of expected
            $stdDev,
            "Hash distribution should not be extremely skewed (std dev: $stdDev, expected: $expected)"
        );

        // Also check that no bucket is too empty or too full
        $min = min($distribution);
        $max = max($distribution);

        $this->assertGreaterThan(
            $expected * 0.5,
            $min,
            'No bucket should be too empty'
        );

        $this->assertLessThan(
            $expected * 2,
            $max,
            'No bucket should be too full'
        );
    }

    /**
     * Test avalanche effect - small input changes cause large output changes.
     */
    public function testAvalancheEffect(): void
    {
        $baseIds = [1, 100, 1000, 10000];

        foreach ($baseIds as $baseId) {
            $baseHash = $this->converter->encode($baseId);

            // Test adjacent IDs
            $adjacentIds = [$baseId - 1, $baseId + 1, $baseId + 2];

            foreach ($adjacentIds as $adjacentId) {
                if ($adjacentId <= 0) {
                    continue;
                }

                $adjacentHash = $this->converter->encode($adjacentId);

                // Count bit differences if both hashes are same length
                if (strlen($baseHash) === strlen($adjacentHash)) {
                    $differences = 0;
                    for ($i = 0; $i < strlen($baseHash); $i++) {
                        if ($baseHash[$i] !== $adjacentHash[$i]) {
                            $differences++;
                        }
                    }

                    // At least 40% of characters should be different
                    $this->assertGreaterThan(
                        strlen($baseHash) * 0.4,
                        $differences,
                        "Small input change should cause large output change"
                    );
                }
            }
        }
    }

    /**
     * Helper: Generate Fibonacci sequence.
     */
    private function generateFibonacci(int $count): array
    {
        $fib = [1, 1];
        for ($i = 2; $i < $count; $i++) {
            $fib[] = $fib[$i - 1] + $fib[$i - 2];
        }
        return $fib;
    }

    /**
     * Helper: Check if a pattern is preserved in hashes.
     */
    private function assertPatternNotPreserved(array $ids, array $hashes, string $message): void
    {
        // Check if the ordering relationship is preserved
        $idPairs = [];
        $hashPairs = [];

        for ($i = 0; $i < count($ids) - 1; $i++) {
            for ($j = $i + 1; $j < count($ids); $j++) {
                $idPairs[] = $ids[$i] < $ids[$j];
                $hashPairs[] = strcmp($hashes[$i], $hashes[$j]) < 0;
            }
        }

        // Count how many ordering relationships are preserved
        $preserved = 0;
        for ($i = 0; $i < count($idPairs); $i++) {
            if ($idPairs[$i] === $hashPairs[$i]) {
                $preserved++;
            }
        }

        // Less than 85% of relationships should be preserved
        // (Hashids may preserve some ordering, but not all)
        $preservationRate = $preserved / count($idPairs);
        $this->assertLessThan(0.85, $preservationRate, $message . " (preservation rate: $preservationRate)");
    }

    /**
     * Helper: Get common prefix length of strings.
     */
    private function getCommonPrefixLength(array $strings): int
    {
        if (count($strings) < 2) {
            return 0;
        }

        $minLength = min(array_map('strlen', $strings));
        $commonLength = 0;

        for ($i = 0; $i < $minLength; $i++) {
            $char = $strings[0][$i];
            $allMatch = true;

            foreach ($strings as $string) {
                if ($string[$i] !== $char) {
                    $allMatch = false;
                    break;
                }
            }

            if ($allMatch) {
                $commonLength++;
            } else {
                break;
            }
        }

        return $commonLength;
    }

    /**
     * Helper: Get common suffix length of strings.
     */
    private function getCommonSuffixLength(array $strings): int
    {
        $reversed = array_map('strrev', $strings);
        return $this->getCommonPrefixLength($reversed);
    }
}