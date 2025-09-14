<?php

namespace Pgs\HashIdBundle\Tests\Rector\Fixtures;

use Pgs\HashIdBundle\Annotation\Hash;
use Symfony\Component\Routing\Annotation\Route;

class BeforeTransformation
{
    /**
     * @Route("/demo/{id}")
     * @Hash("id")
     */
    public function singleParameter(int $id): void
    {
    }
    
    /**
     * @Route("/demo/{id}/{other}")
     * @Hash({"id", "other"})
     */
    public function multipleParameters(int $id, int $other): void
    {
    }
    
    /**
     * @Route("/demo/mixed/{id}")
     * @Hash("id")
     * @param int $id
     */
    public function withDocBlock(int $id): void
    {
    }
}

-----

<?php

namespace Pgs\HashIdBundle\Tests\Rector\Fixtures;

use Pgs\HashIdBundle\Annotation\Hash;
use Symfony\Component\Routing\Annotation\Route;

class BeforeTransformation
{
    #[Route('/demo/{id}')]
    #[Hash('id')]
    public function singleParameter(int $id): void
    {
    }
    
    #[Route('/demo/{id}/{other}')]
    #[Hash(['id', 'other'])]
    public function multipleParameters(int $id, int $other): void
    {
    }
    
    /**
     * @param int $id
     */
    #[Route('/demo/mixed/{id}')]
    #[Hash('id')]
    public function withDocBlock(int $id): void
    {
    }
}