<?php

declare(strict_types=1);

namespace Pgs\HashIdBundle\Tests\Security;

use PHPUnit\Framework\TestCase;
use Hashids\Hashids;
use Hashids\HashidsInterface;
use Pgs\HashIdBundle\ParametersProcessor\Converter\HashidsConverter;

/**
 * Security tests for URL obfuscation effectiveness.
 *
 * @covers \Pgs\HashIdBundle\ParametersProcessor\Converter\HashidsConverter
 * @covers \Hashids\Hashids
 * @group security
 */
class UrlObfuscationTest extends TestCase
{
    /**
     * Create a Hashids instance with specified configuration.
     */
    private function createHashids(
        string $salt = 'test-salt',
        int $minLength = 10,
        ?string $alphabet = null
    ): HashidsInterface {
        return new Hashids(
            $salt,
            $minLength,
            $alphabet ?? 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ1234567890'
        );
    }

    /**
     * Test that encoded IDs cannot be reverse-engineered without salt.
     */
    public function testEncodedIdsCannotBeReversedWithoutSalt(): void
    {
        $hasher = $this->createHashids('secret-salt-xyz');
        $converter = new HashidsConverter($hasher);

        // Encode some IDs
        $encodedIds = [];
        for ($id = 1; $id <= 10; $id++) {
            $encodedIds[$id] = $converter->encode($id);
        }

        // Try to decode with wrong salt
        $wrongHasher = $this->createHashids('wrong-salt');
        $wrongConverter = new HashidsConverter($wrongHasher);

        foreach ($encodedIds as $originalId => $encodedId) {
            $decodedId = $wrongConverter->decode($encodedId);

            // Should either return null or wrong ID
            $this->assertNotEquals(
                $originalId,
                $decodedId,
                "ID $originalId should not be recoverable with wrong salt"
            );
        }
    }

    /**
     * Test URL uniqueness and collision prevention.
     */
    public function testUrlUniquenessAndCollisionPrevention(): void
    {
        $hasher = $this->createHashids('test-salt');
        $converter = new HashidsConverter($hasher);

        $encodedValues = [];
        $testRange = 10000;

        // Encode a large range of IDs
        for ($id = 1; $id <= $testRange; $id++) {
            $encoded = $converter->encode($id);

            // Check for uniqueness
            $this->assertNotContains(
                $encoded,
                $encodedValues,
                "Collision detected: ID $id produced duplicate hash $encoded"
            );

            $encodedValues[] = $encoded;
        }

        // Verify all encoded values are unique
        $uniqueValues = array_unique($encodedValues);
        $this->assertCount(
            $testRange,
            $uniqueValues,
            'All encoded IDs should be unique'
        );
    }

    /**
     * Test consistent encoding/decoding across requests.
     */
    public function testConsistentEncodingAcrossRequests(): void
    {
        $salt = 'consistent-salt-abc';
        $testIds = [1, 42, 123, 999, 12345, 999999];

        // Simulate first request
        $hasher1 = $this->createHashids($salt);
        $converter1 = new HashidsConverter($hasher1);

        $firstRequestHashes = [];
        foreach ($testIds as $id) {
            $firstRequestHashes[$id] = $converter1->encode($id);
        }

        // Simulate second request (new instances)
        $hasher2 = $this->createHashids($salt);
        $converter2 = new HashidsConverter($hasher2);

        foreach ($testIds as $id) {
            $secondRequestHash = $converter2->encode($id);

            $this->assertEquals(
                $firstRequestHashes[$id],
                $secondRequestHash,
                "ID $id should produce consistent hash across requests"
            );

            // Verify decode also works consistently
            $decoded = $converter2->decode($firstRequestHashes[$id]);
            $this->assertEquals(
                $id,
                $decoded,
                "Hash should decode to original ID $id"
            );
        }
    }

    /**
     * Test obfuscation strength with different alphabet configurations.
     */
    public function testObfuscationStrengthWithDifferentAlphabets(): void
    {
        $testId = 12345;
        $alphabets = [
            'numbers' => '0123456789',
            'lowercase' => 'abcdefghijklmnopqrstuvwxyz',
            'alphanumeric' => 'abcdefghijklmnopqrstuvwxyz0123456789',
            'full' => 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789',
            'extended' => 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*',
        ];

        $encodedValues = [];

        foreach ($alphabets as $name => $alphabet) {
            // Skip alphabets that are too short for Hashids
            if (strlen($alphabet) < 16) {
                continue;
            }

            $hasher = $this->createHashids('test-salt', 10, $alphabet);
            $converter = new HashidsConverter($hasher);

            $encoded = $converter->encode($testId);

            // Verify encoded value uses only allowed characters
            $this->assertMatchesRegularExpression(
                '/^[' . preg_quote($alphabet, '/') . ']+$/',
                $encoded,
                "Encoded value for '$name' alphabet should only use allowed characters"
            );

            // Verify different alphabets produce different hashes
            foreach ($encodedValues as $prevName => $prevEncoded) {
                if ($alphabet !== $alphabets[$prevName]) {
                    $this->assertNotEquals(
                        $prevEncoded,
                        $encoded,
                        "Different alphabets ('$prevName' vs '$name') should produce different hashes"
                    );
                }
            }

            $encodedValues[$name] = $encoded;

            // Verify decode works with the same alphabet
            $decoded = $converter->decode($encoded);
            $this->assertEquals(
                $testId,
                $decoded,
                "Should correctly decode with '$name' alphabet"
            );
        }
    }

    /**
     * Test that small changes in salt produce completely different hashes.
     */
    public function testSaltSensitivity(): void
    {
        $testIds = [1, 100, 1000];
        $salts = [
            'salt1',
            'salt2',
            'Salt1', // Case change
            'salt1!', // Added character
            'salt', // Removed character
        ];

        $hashesPerSalt = [];

        foreach ($salts as $salt) {
            $hasher = $this->createHashids($salt);
            $converter = new HashidsConverter($hasher);

            $hashes = [];
            foreach ($testIds as $id) {
                $hashes[$id] = $converter->encode($id);
            }

            // Compare with all previous salt results
            foreach ($hashesPerSalt as $prevSalt => $prevHashes) {
                foreach ($testIds as $id) {
                    $this->assertNotEquals(
                        $prevHashes[$id],
                        $hashes[$id],
                        "Different salts ('$prevSalt' vs '$salt') should produce different hashes for ID $id"
                    );
                }
            }

            $hashesPerSalt[$salt] = $hashes;
        }
    }

    /**
     * Test minimum hash length enforcement.
     */
    public function testMinimumHashLengthEnforcement(): void
    {
        $minLengths = [5, 10, 15, 20, 30];
        $testIds = [1, 5, 10, 100, 1000];

        foreach ($minLengths as $minLength) {
            $hasher = $this->createHashids('test-salt', $minLength);
            $converter = new HashidsConverter($hasher);

            foreach ($testIds as $id) {
                $encoded = $converter->encode($id);

                $this->assertGreaterThanOrEqual(
                    $minLength,
                    strlen($encoded),
                    "Hash for ID $id should be at least $minLength characters long"
                );

                // Verify it still decodes correctly
                $decoded = $converter->decode($encoded);
                $this->assertEquals(
                    $id,
                    $decoded,
                    "Hash with min_length $minLength should decode correctly"
                );
            }
        }
    }

    /**
     * Test that hash structure doesn't reveal ID patterns.
     */
    public function testHashStructureDoesNotRevealPatterns(): void
    {
        $hasher = $this->createHashids('pattern-test');
        $converter = new HashidsConverter($hasher);

        // Test sequential IDs
        $sequentialHashes = [];
        for ($id = 100; $id <= 110; $id++) {
            $sequentialHashes[$id] = $converter->encode($id);
        }

        // Check that sequential IDs don't produce similar hashes
        $previousHash = null;
        foreach ($sequentialHashes as $id => $hash) {
            if ($previousHash !== null) {
                // Calculate similarity
                $similarity = similar_text($previousHash, $hash, $percent);

                // Hashes should be sufficiently different (less than 50% similar)
                $this->assertLessThan(
                    50,
                    $percent,
                    "Sequential IDs should produce dissimilar hashes (ID $id similarity: $percent%)"
                );
            }
            $previousHash = $hash;
        }

        // Test powers of 10
        $powerHashes = [];
        for ($exp = 0; $exp <= 6; $exp++) {
            $id = pow(10, $exp);
            $powerHashes[$id] = $converter->encode($id);
        }

        // Verify no obvious patterns in length or structure
        $lengths = array_map('strlen', $powerHashes);
        $uniqueLengths = array_unique($lengths);

        // With minimum length enforcement, all hashes might be same length
        // But the content should be completely different
        // Check that no two hashes are identical
        $hashValues = array_values($powerHashes);
        $uniqueHashes = array_unique($hashValues);
        $this->assertCount(
            count($hashValues),
            $uniqueHashes,
            "All power-of-10 IDs should produce unique hashes"
        );

        // Check that hashes don't follow a predictable pattern
        // by ensuring they have different character distributions
        foreach ($powerHashes as $id => $hash) {
            $charCounts = array_count_values(str_split($hash));
            // No character should dominate the hash (appear more than 50%)
            $maxCount = max($charCounts);
            $this->assertLessThan(
                strlen($hash) * 0.5,
                $maxCount,
                "Hash for ID $id should have balanced character distribution"
            );
        }
    }

    /**
     * Test protection against hash manipulation.
     */
    public function testProtectionAgainstHashManipulation(): void
    {
        $hasher = $this->createHashids('manipulation-test');
        $converter = new HashidsConverter($hasher);

        $originalId = 42;
        $originalHash = $converter->encode($originalId);

        // Try various manipulations
        $manipulations = [
            substr($originalHash, 0, -1), // Remove last character
            $originalHash . 'x', // Add character
            str_replace(substr($originalHash, 0, 1), 'z', $originalHash), // Replace first char
            strrev($originalHash), // Reverse
            strtoupper($originalHash), // Change case (if applicable)
        ];

        foreach ($manipulations as $manipulated) {
            if ($manipulated === $originalHash) {
                continue; // Skip if manipulation resulted in same value
            }

            $decoded = $converter->decode($manipulated);

            // Manipulated hash should either not decode or decode to wrong ID
            $this->assertThat(
                $decoded,
                $this->logicalOr(
                    $this->isNull(),
                    $this->logicalNot($this->equalTo($originalId))
                ),
                "Manipulated hash '$manipulated' should not decode to original ID"
            );
        }
    }

    /**
     * Test encoding and decoding of edge case values.
     */
    public function testEdgeCaseValues(): void
    {
        $hasher = $this->createHashids('edge-case-test');
        $converter = new HashidsConverter($hasher);

        $edgeCases = [
            0,
            1,
            PHP_INT_MAX,
            999999999,
            2147483647, // Max 32-bit int
        ];

        foreach ($edgeCases as $value) {
            $encoded = $converter->encode($value);

            $this->assertNotEmpty(
                $encoded,
                "Should encode edge case value: $value"
            );

            $decoded = $converter->decode($encoded);

            $this->assertEquals(
                $value,
                $decoded,
                "Should correctly decode edge case value: $value"
            );
        }
    }
}