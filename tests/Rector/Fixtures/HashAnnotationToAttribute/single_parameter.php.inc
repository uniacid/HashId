<?php

namespace Pgs\HashIdBundle\Tests\Rector\Fixtures\HashAnnotationToAttribute;

use Pgs\HashIdBundle\Annotation\Hash;
use Symfony\Component\Routing\Annotation\Route;

class OrderController
{
    /**
     * @Route("/order/{id}", name="order_show")
     * @Hash("id")
     */
    public function show(int $id)
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

class OrderController
{
    /**
     * @Route("/order/{id}", name="order_show")
     */
    #[Hash('id')]
    public function show(int $id)
    {
        return $id;
    }
}
?>