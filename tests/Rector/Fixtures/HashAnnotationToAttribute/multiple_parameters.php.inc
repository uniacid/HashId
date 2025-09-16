<?php

namespace Pgs\HashIdBundle\Tests\Rector\Fixtures\HashAnnotationToAttribute;

use Pgs\HashIdBundle\Annotation\Hash;
use Symfony\Component\Routing\Annotation\Route;

class ProductController
{
    /**
     * @Route("/product/{categoryId}/{productId}", name="product_show")
     * @Hash({"categoryId", "productId"})
     */
    public function show(int $categoryId, int $productId)
    {
        return [$categoryId, $productId];
    }
}
?>
-----
<?php

namespace Pgs\HashIdBundle\Tests\Rector\Fixtures\HashAnnotationToAttribute;

use Pgs\HashIdBundle\Attribute\Hash;
use Symfony\Component\Routing\Annotation\Route;

class ProductController
{
    /**
     * @Route("/product/{categoryId}/{productId}", name="product_show")
     */
    #[Hash(['categoryId', 'productId'])]
    public function show(int $categoryId, int $productId)
    {
        return [$categoryId, $productId];
    }
}
?>