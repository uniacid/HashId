<?php

namespace Pgs\HashIdBundle\Tests\Rector\Fixtures;

use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/hash-id/demo")
 */
class RouteController
{
    /**
     * @Route("/encode", requirements={"id"="\d+"})
     */
    public function encode(int $id): void
    {
    }
    
    /**
     * @Route("/decode/{id}/{other}", methods={"GET", "POST"})
     */
    public function decode(int $id, int $other): void
    {
    }
    
    /**
     * @Route(
     *     "/complex/{id}",
     *     name="complex_route",
     *     requirements={"id"="\d+"},
     *     methods={"GET"}
     * )
     */
    public function complex(int $id): void
    {
    }
}

-----

<?php

namespace Pgs\HashIdBundle\Tests\Rector\Fixtures;

use Symfony\Component\Routing\Annotation\Route;

#[Route('/hash-id/demo')]
class RouteController
{
    #[Route('/encode', requirements: ['id' => '\d+'])]
    public function encode(int $id): void
    {
    }
    
    #[Route('/decode/{id}/{other}', methods: ['GET', 'POST'])]
    public function decode(int $id, int $other): void
    {
    }
    
    #[Route(
        path: '/complex/{id}',
        name: 'complex_route',
        requirements: ['id' => '\d+'],
        methods: ['GET']
    )]
    public function complex(int $id): void
    {
    }
}