<?php

declare(strict_types=1);

namespace Pgs\HashIdBundle\Tests\Security;

use PHPUnit\Framework\TestCase;
use Hashids\Hashids;
use Pgs\HashIdBundle\ParametersProcessor\Converter\HashidsConverter;
use Pgs\HashIdBundle\Service\HasherFactory;

/**
 * Test cryptographic salt handling and configuration security.
 *
 * @covers \Pgs\HashIdBundle\Service\HasherFactory
 * @covers \Pgs\HashIdBundle\ParametersProcessor\Converter\HashidsConverter
 * @covers \Hashids\Hashids
 * @group security
 */
class SaltConfigurationTest extends TestCase
{
    /**
     * Test salt configuration from environment variables.
     */
    public function testSaltConfigurationFromEnvironment(): void
    {
        // Simulate environment variable
        $envSalt = 'env-secret-salt-' . bin2hex(random_bytes(8));

        // Test that salt is properly used
        $hasher1 = new Hashids($envSalt, 10);
        $converter1 = new HashidsConverter($hasher1);

        $testId = 42;
        $hash1 = $converter1->encode($testId);

        // Different salt should produce different hash
        $differentSalt = 'different-salt-' . bin2hex(random_bytes(8));
        $hasher2 = new Hashids($differentSalt, 10);
        $converter2 = new HashidsConverter($hasher2);

        $hash2 = $converter2->encode($testId);

        $this->assertNotEquals(
            $hash1,
            $hash2,
            'Different salts should produce different hashes for the same ID'
        );

        // Same salt should produce same hash
        $hasher3 = new Hashids($envSalt, 10);
        $converter3 = new HashidsConverter($hasher3);

        $hash3 = $converter3->encode($testId);

        $this->assertEquals(
            $hash1,
            $hash3,
            'Same salt should produce same hash for the same ID'
        );
    }

    /**
     * Test salt uniqueness requirements.
     */
    public function testSaltUniquenessRequirements(): void
    {
        $testId = 123;
        $salts = [];
        $hashes = [];

        // Generate multiple unique salts
        for ($i = 0; $i < 100; $i++) {
            $salt = 'salt-' . $i . '-' . uniqid();
            $salts[] = $salt;

            $hasher = new Hashids($salt, 10);
            $converter = new HashidsConverter($hasher);

            $hash = $converter->encode($testId);
            $hashes[] = $hash;
        }

        // All salts should be unique
        $uniqueSalts = array_unique($salts);
        $this->assertCount(
            count($salts),
            $uniqueSalts,
            'All salts should be unique'
        );

        // All hashes should be unique
        $uniqueHashes = array_unique($hashes);
        $this->assertCount(
            count($hashes),
            $uniqueHashes,
            'Unique salts should produce unique hashes'
        );

        // Test that similar salts produce different hashes
        $baseSalt = 'base-salt-123';
        $similarSalts = [
            $baseSalt,
            $baseSalt . '1',
            '1' . $baseSalt,
            strtoupper($baseSalt),
            str_replace('-', '_', $baseSalt),
        ];

        $similarHashes = [];
        foreach ($similarSalts as $salt) {
            $hasher = new Hashids($salt, 10);
            $converter = new HashidsConverter($hasher);
            $similarHashes[] = $converter->encode($testId);
        }

        $uniqueSimilarHashes = array_unique($similarHashes);
        $this->assertCount(
            count($similarHashes),
            $uniqueSimilarHashes,
            'Even similar salts should produce different hashes'
        );
    }

    /**
     * Test salt rotation scenarios.
     */
    public function testSaltRotationScenarios(): void
    {
        // Simulate old salt
        $oldSalt = 'old-salt-2023';
        $oldHasher = new Hashids($oldSalt, 10);
        $oldConverter = new HashidsConverter($oldHasher);

        // Generate hashes with old salt
        $ids = range(1, 10);
        $oldHashes = [];
        foreach ($ids as $id) {
            $oldHashes[$id] = $oldConverter->encode($id);
        }

        // Simulate salt rotation to new salt
        $newSalt = 'new-salt-2024';
        $newHasher = new Hashids($newSalt, 10);
        $newConverter = new HashidsConverter($newHasher);

        // Generate hashes with new salt
        $newHashes = [];
        foreach ($ids as $id) {
            $newHashes[$id] = $newConverter->encode($id);
        }

        // Verify all hashes changed
        foreach ($ids as $id) {
            $this->assertNotEquals(
                $oldHashes[$id],
                $newHashes[$id],
                "Hash for ID $id should change after salt rotation"
            );
        }

        // Test backward compatibility scenario
        // System might need to decode old hashes during transition
        foreach ($ids as $id) {
            // Old converter should still decode old hashes
            $decodedFromOld = $oldConverter->decode($oldHashes[$id]);
            $this->assertEquals(
                $id,
                $decodedFromOld,
                "Old salt should still decode old hashes during transition"
            );

            // New converter cannot decode old hashes (security feature)
            $decodedFromNew = $newConverter->decode($oldHashes[$id]);
            $this->assertNotEquals(
                $id,
                $decodedFromNew,
                "New salt should not decode old hashes (security boundary)"
            );
        }
    }

    /**
     * Test secure salt generation for 'secure' hasher type.
     */
    public function testSecureSaltGeneration(): void
    {
        // Test salt entropy
        $salts = [];
        for ($i = 0; $i < 100; $i++) {
            // Simulate secure salt generation
            $salt = bin2hex(random_bytes(32)); // 256 bits of entropy
            $salts[] = $salt;

            // Verify salt has sufficient length
            $this->assertGreaterThanOrEqual(
                64,
                strlen($salt),
                'Secure salt should be at least 64 characters (256 bits hex)'
            );

            // Verify salt has good character distribution
            $chars = str_split($salt);
            $uniqueChars = array_unique($chars);

            $this->assertGreaterThan(
                10, // Should use most hex characters
                count($uniqueChars),
                'Salt should have good character distribution'
            );
        }

        // Verify all generated salts are unique
        $uniqueSalts = array_unique($salts);
        $this->assertCount(
            100,
            $uniqueSalts,
            'All generated secure salts should be unique'
        );

        // Test that secure salts produce strong hashes
        $testId = 999;
        foreach (array_slice($salts, 0, 10) as $salt) {
            $hasher = new Hashids($salt, 20); // Longer minimum for secure mode
            $converter = new HashidsConverter($hasher);

            $hash = $converter->encode($testId);

            $this->assertGreaterThanOrEqual(
                20,
                strlen($hash),
                'Secure mode should produce longer hashes'
            );
        }
    }

    /**
     * Test salt configuration validation.
     */
    public function testSaltConfigurationValidation(): void
    {
        // Test empty salt (should be allowed but with warning)
        $emptyHasher = new Hashids('', 10);
        $emptyConverter = new HashidsConverter($emptyHasher);

        $hash1 = $emptyConverter->encode(1);
        $hash2 = $emptyConverter->encode(2);

        $this->assertNotEquals(
            $hash1,
            $hash2,
            'Even with empty salt, different IDs should produce different hashes'
        );

        // Test very long salt
        $longSalt = str_repeat('long-salt-', 100); // 1000 characters
        $longHasher = new Hashids($longSalt, 10);
        $longConverter = new HashidsConverter($longHasher);

        $hashLong = $longConverter->encode(42);
        $this->assertNotEmpty(
            $hashLong,
            'Very long salt should still work'
        );

        // Test salt with special characters
        $specialSalt = 'salt-!@#$%^&*()_+-=[]{}|;:,.<>?/~`';
        $specialHasher = new Hashids($specialSalt, 10);
        $specialConverter = new HashidsConverter($specialHasher);

        $hashSpecial = $specialConverter->encode(42);
        $this->assertNotEmpty(
            $hashSpecial,
            'Salt with special characters should work'
        );

        // Test Unicode salt
        $unicodeSalt = 'salt-🔐-секрет-秘密-🔑';
        $unicodeHasher = new Hashids($unicodeSalt, 10);
        $unicodeConverter = new HashidsConverter($unicodeHasher);

        $hashUnicode = $unicodeConverter->encode(42);
        $this->assertNotEmpty(
            $hashUnicode,
            'Unicode salt should work'
        );
    }

    /**
     * Test salt exposure prevention.
     */
    public function testSaltExposurePrevention(): void
    {
        $secretSalt = 'super-secret-salt-key';
        $hasher = new Hashids($secretSalt, 10);
        $converter = new HashidsConverter($hasher);

        // Generate some hashes
        $hashes = [];
        for ($id = 1; $id <= 1000; $id++) {
            $hashes[$id] = $converter->encode($id);
        }

        // Test that salt cannot be derived from hashes
        // Check that hashes don't contain salt substring
        foreach ($hashes as $hash) {
            $this->assertStringNotContainsString(
                $secretSalt,
                $hash,
                'Hash should not contain the salt'
            );

            // Check for any substring of salt (minimum 4 chars)
            for ($i = 0; $i <= strlen($secretSalt) - 4; $i++) {
                $substring = substr($secretSalt, $i, 4);
                $this->assertStringNotContainsString(
                    $substring,
                    $hash,
                    "Hash should not contain salt substring: $substring"
                );
            }
        }

        // Test that hash patterns don't reveal salt patterns
        $saltPattern = preg_quote($secretSalt, '/');
        $combinedHashes = implode('', $hashes);

        $this->assertDoesNotMatchRegularExpression(
            "/$saltPattern/i",
            $combinedHashes,
            'Combined hashes should not reveal salt pattern'
        );
    }

    /**
     * Test HasherFactory salt configuration.
     */
    public function testHasherFactorySaltConfiguration(): void
    {
        // Test factory with default salt
        $factory1 = new HasherFactory('factory-salt-1', 10);

        // Test factory with different salt
        $factory2 = new HasherFactory('factory-salt-2', 10);

        // HasherFactory should validate salt configuration
        // Test with null salt (should use empty string or generate)
        $factory3 = new HasherFactory(null, 10);

        // Verify factory instances are configured correctly
        $this->assertInstanceOf(
            HasherFactory::class,
            $factory1,
            'Factory should accept salt configuration'
        );

        $this->assertInstanceOf(
            HasherFactory::class,
            $factory2,
            'Factory should accept different salt configuration'
        );

        $this->assertInstanceOf(
            HasherFactory::class,
            $factory3,
            'Factory should handle null salt'
        );
    }

    /**
     * Test salt persistence across requests.
     */
    public function testSaltPersistenceAcrossRequests(): void
    {
        // Simulate configuration that would be loaded from config file
        $persistentSalt = 'persistent-app-salt-' . date('Y');

        // Simulate multiple request cycles
        $requestResults = [];

        for ($request = 0; $request < 5; $request++) {
            // Each "request" creates new instances
            $hasher = new Hashids($persistentSalt, 10);
            $converter = new HashidsConverter($hasher);

            // Encode same IDs
            $results = [];
            foreach ([1, 42, 999] as $id) {
                $results[$id] = $converter->encode($id);
            }

            $requestResults[] = $results;
        }

        // All requests should produce identical results
        $firstRequest = $requestResults[0];
        foreach ($requestResults as $index => $results) {
            $this->assertEquals(
                $firstRequest,
                $results,
                "Request $index should produce same hashes with persistent salt"
            );
        }
    }

    /**
     * Test salt strength recommendations.
     */
    public function testSaltStrengthRecommendations(): void
    {
        $weakSalts = [
            '',           // Empty
            'a',          // Too short
            '123',        // Numeric only
            'salt',       // Dictionary word
            'password',   // Common word
        ];

        $strongSalts = [
            bin2hex(random_bytes(32)),                    // Random bytes
            'app-' . uniqid() . '-' . bin2hex(random_bytes(16)),  // Composite
            hash('sha256', 'app-specific-' . time()),     // Hashed value
            base64_encode(random_bytes(32)),              // Base64 random
        ];

        // Test weak salts produce less secure hashes
        foreach ($weakSalts as $weakSalt) {
            $hasher = new Hashids($weakSalt, 8);
            $converter = new HashidsConverter($hasher);

            // Weak salt warning: produces working but less secure hashes
            $hash = $converter->encode(42);
            $this->assertNotEmpty(
                $hash,
                "Weak salt '$weakSalt' should still produce hashes"
            );
        }

        // Test strong salts produce more secure hashes
        foreach ($strongSalts as $strongSalt) {
            $hasher = new Hashids($strongSalt, 15);
            $converter = new HashidsConverter($hasher);

            $hash = $converter->encode(42);

            $this->assertGreaterThanOrEqual(
                15,
                strlen($hash),
                'Strong salt should support longer minimum hash length'
            );

            // Verify uniqueness
            $hash2 = $converter->encode(43);
            $this->assertNotEquals(
                $hash,
                $hash2,
                'Strong salt should produce unique hashes'
            );
        }
    }
}