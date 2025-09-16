<?php

/**
 * HashId Bundle v3 to v4 Migration Examples
 *
 * This file demonstrates how to migrate from HashId Bundle v3 to v4
 * including configuration changes and controller updates.
 */

declare(strict_types=1);

namespace App\Controller;

use Pgs\HashIdBundle\Attribute\Hash;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Example: Migrating Controllers from v3 to v4
 */
class MigrationExampleController extends AbstractController
{
    // ============================================
    // BEFORE: HashId Bundle v3.x (Using Annotations)
    // ============================================
    
    /**
     * @Route("/order/{id}", name="order_show_v3")
     * @Hash("id")
     */
    public function showOrderV3(int $id): Response
    {
        // The $id parameter is automatically decoded from hash to integer
        return $this->json(['order_id' => $id]);
    }

    // ============================================
    // AFTER: HashId Bundle v4.x (Using PHP 8 Attributes)
    // ============================================
    
    #[Route('/order/{id}', name: 'order_show_v4')]
    #[Hash('id')]  // Uses the 'default' hasher
    public function showOrderV4(int $id): Response
    {
        // The $id parameter is automatically decoded from hash to integer
        return $this->json(['order_id' => $id]);
    }

    // ============================================
    // NEW IN v4: Using Multiple Hashers
    // ============================================
    
    #[Route('/user/{userId}', name: 'user_profile')]
    #[Hash('userId', hasher: 'secure')]  // Uses the 'secure' hasher for sensitive data
    public function userProfile(int $userId): Response
    {
        // Uses the 'secure' hasher configuration for enhanced security
        return $this->json(['user_id' => $userId]);
    }

    #[Route('/public/post/{postId}', name: 'public_post')]
    #[Hash('postId', hasher: 'public')]  // Uses the 'public' hasher for public content
    public function publicPost(int $postId): Response
    {
        // Uses the 'public' hasher with shorter hashes for better UX
        return $this->json(['post_id' => $postId]);
    }

    #[Route('/api/resource/{resourceId}', name: 'api_resource')]
    #[Hash('resourceId', hasher: 'api')]  // Uses the 'api' hasher for API consistency
    public function apiResource(int $resourceId): Response
    {
        // Uses the 'api' hasher with uppercase alphabet for API compatibility
        return $this->json(['resource_id' => $resourceId]);
    }

    // ============================================
    // MULTIPLE PARAMETERS WITH DIFFERENT HASHERS
    // ============================================
    
    #[Route('/order/{orderId}/user/{userId}', name: 'order_user')]
    #[Hash('orderId')]  // Uses 'default' hasher
    #[Hash('userId', hasher: 'secure')]  // Uses 'secure' hasher
    public function orderUser(int $orderId, int $userId): Response
    {
        // Different hashers for different security requirements
        return $this->json([
            'order_id' => $orderId,
            'user_id' => $userId,
        ]);
    }

    // ============================================
    // ARRAY SYNTAX (Multiple Parameters, Same Hasher)
    // ============================================
    
    #[Route('/compare/{id1}/{id2}', name: 'compare_items')]
    #[Hash(['id1', 'id2'])]  // Both use 'default' hasher
    public function compareItems(int $id1, int $id2): Response
    {
        return $this->json([
            'item1' => $id1,
            'item2' => $id2,
        ]);
    }

    #[Route('/secure/batch/{user1}/{user2}/{user3}', name: 'secure_batch')]
    #[Hash(['user1', 'user2', 'user3'], hasher: 'secure')]  // All use 'secure' hasher
    public function secureBatch(int $user1, int $user2, int $user3): Response
    {
        return $this->json([
            'users' => [$user1, $user2, $user3],
        ]);
    }
}

/**
 * Configuration Migration Checklist:
 *
 * 1. Update symfony/annotations to symfony/attributes (if using Symfony 5.2+)
 * 2. Replace @Hash annotations with #[Hash] attributes
 * 3. Move salt values to environment variables
 * 4. Configure multiple hashers if needed
 * 5. Update controllers to specify hashers for sensitive resources
 * 6. Test URL generation and parameter decoding
 * 7. Verify backward compatibility with existing URLs
 *
 * Benefits of Migration:
 * - Better security with environment variables
 * - Multiple security levels with different hashers
 * - Native PHP 8 attribute support
 * - Improved IDE autocompletion
 * - Type-safe configuration with validation
 * - Better performance with hasher caching
 */