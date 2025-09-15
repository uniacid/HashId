<?php

declare(strict_types=1);

namespace App\Controller;

use Pgs\HashIdBundle\Attribute\Hash;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Example controller demonstrating multiple hasher usage for different security contexts.
 * 
 * This shows how to use different hashers for different types of resources
 * based on their security requirements.
 */
class MultipleHasherController extends AbstractController
{
    /**
     * Public article view - uses 'public' hasher for SEO-friendly URLs.
     * URLs will be short and readable: /article/x9aA2
     */
    #[Route('/article/{id}', name: 'article_view')]
    #[Hash('id', hasher: 'public')]
    public function viewArticle(int $id): Response
    {
        // The $id parameter is automatically decoded from the hash
        $article = $this->getArticle($id);
        
        return $this->render('article/view.html.twig', [
            'article' => $article,
        ]);
    }
    
    /**
     * User profile - uses 'secure' hasher for sensitive data.
     * URLs will be longer and more secure: /user/profile/4w9aA11avMx2Qp8
     */
    #[Route('/user/profile/{userId}', name: 'user_profile')]
    #[Hash('userId', hasher: 'secure')]
    public function userProfile(int $userId): Response
    {
        // Enhanced security for user-related resources
        $user = $this->getUser($userId);
        
        return $this->render('user/profile.html.twig', [
            'user' => $user,
        ]);
    }
    
    /**
     * API resource endpoint - uses 'api' hasher for consistency.
     * URLs will use uppercase alphabet: /api/resource/X9AA2M7K3P
     */
    #[Route('/api/resource/{resourceId}', name: 'api_resource')]
    #[Hash('resourceId', hasher: 'api')]
    public function apiResource(int $resourceId): Response
    {
        // API-specific hasher configuration
        $resource = $this->getResource($resourceId);
        
        return $this->json(['resource' => $resource]);
    }
    
    /**
     * Admin panel - uses 'admin' hasher for maximum security.
     * URLs will be very long: /admin/user/x9aA2m7k3pQr8sT4vW6yZ1
     */
    #[Route('/admin/user/{userId}/edit', name: 'admin_user_edit')]
    #[Hash('userId', hasher: 'admin')]
    public function adminEditUser(int $userId): Response
    {
        // Maximum security for admin operations
        $this->denyAccessUnlessGranted('ROLE_ADMIN');
        
        $user = $this->getUser($userId);
        
        return $this->render('admin/user/edit.html.twig', [
            'user' => $user,
        ]);
    }
    
    /**
     * Order details with multiple parameters - uses 'secure' hasher.
     * Both parameters will be encoded with the same secure hasher.
     */
    #[Route('/order/{orderId}/user/{userId}', name: 'order_details')]
    #[Hash(['orderId', 'userId'], hasher: 'secure')]
    public function orderDetails(int $orderId, int $userId): Response
    {
        // Multiple parameters with same hasher
        $order = $this->getOrder($orderId, $userId);
        
        return $this->render('order/details.html.twig', [
            'order' => $order,
        ]);
    }
    
    /**
     * Default hasher example - when no hasher is specified.
     * Uses the 'default' hasher configuration.
     */
    #[Route('/product/{productId}', name: 'product_view')]
    #[Hash('productId')]  // No hasher specified, uses 'default'
    public function viewProduct(int $productId): Response
    {
        $product = $this->getProduct($productId);
        
        return $this->render('product/view.html.twig', [
            'product' => $product,
        ]);
    }
    
    /**
     * Example using legacy annotation format (backward compatibility).
     * 
     * @Route("/legacy/{id}", name="legacy_route")
     * @Hash({"id"}, hasher="public")
     */
    public function legacyAction(int $id): Response
    {
        // This works with the old annotation format too
        return $this->json(['id' => $id]);
    }
    
    // Helper methods (would typically be in services/repositories)
    
    private function getArticle(int $id): array
    {
        return ['id' => $id, 'title' => 'Article ' . $id];
    }
    
    private function getUser(int $id): array
    {
        return ['id' => $id, 'name' => 'User ' . $id];
    }
    
    private function getResource(int $id): array
    {
        return ['id' => $id, 'data' => 'Resource ' . $id];
    }
    
    private function getOrder(int $orderId, int $userId): array
    {
        return ['orderId' => $orderId, 'userId' => $userId, 'total' => 100.00];
    }
    
    private function getProduct(int $id): array
    {
        return ['id' => $id, 'name' => 'Product ' . $id];
    }
}