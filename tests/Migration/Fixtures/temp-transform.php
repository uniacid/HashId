<?php

namespace App\Controller;

use Pgs\HashIdBundle\Attribute\Hash;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

/**
 * @Route("/api")
 */
class SampleController extends AbstractController
{
    /**
     * @Route("/user/{id}", name="api_user")
     */
    #[Hash('id')]
    public function getUser(int $id)
    {
        return $this->json(['id' => $id]);
    }

    /**
     * @Route("/compare/{id}/{otherId}", name="api_compare")
     */
    #[Hash(['id', 'otherId'])]
    public function compareUsers(int $id, int $otherId)
    {
        return $this->json(['id' => $id, 'otherId' => $otherId]);
    }
}