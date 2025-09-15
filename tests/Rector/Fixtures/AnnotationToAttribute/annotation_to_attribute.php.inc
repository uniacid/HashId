<?php

namespace Pgs\HashIdBundle\Tests\Rector\Fixtures\AnnotationToAttribute;

use Pgs\HashIdBundle\Annotation\Hash;

class BeforeController
{
    /**
     * @Hash("id")
     */
    public function showAction(int $id): Response
    {
        return new Response();
    }
    
    /**
     * @Hash({"userId", "orderId"})
     */
    public function detailAction(int $userId, int $orderId): Response
    {
        return new Response();
    }
}
?>
-----
<?php

namespace Pgs\HashIdBundle\Tests\Rector\Fixtures\AnnotationToAttribute;

use Pgs\HashIdBundle\Annotation\Hash;

class BeforeController
{
    #[Hash("id")]
    public function showAction(int $id): Response
    {
        return new Response();
    }
    
    #[Hash(["userId", "orderId"])]
    public function detailAction(int $userId, int $orderId): Response
    {
        return new Response();
    }
}
?>