<?php

namespace Pgs\HashIdBundle\Tests\Rector\Fixtures\HashAnnotationToAttribute;

use Pgs\HashIdBundle\Annotation\Hash;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/api")
 * @Hash("id")
 */
class ApiController
{
    /**
     * @Route("/resource/{id}", name="api_resource")
     */
    public function resource(int $id)
    {
        return $id;
    }
}
?>
-----
<?php

namespace Pgs\HashIdBundle\Tests\Rector\Fixtures\HashAnnotationToAttribute;

use Pgs\HashIdBundle\Attribute\Hash;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/api")
 */
#[Hash('id')]
class ApiController
{
    /**
     * @Route("/resource/{id}", name="api_resource")
     */
    public function resource(int $id)
    {
        return $id;
    }
}
?>