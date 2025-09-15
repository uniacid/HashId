<?php

namespace Pgs\HashIdBundle\Tests\Rector\Fixtures\PropertyPromotion;

class BeforeService
{
    private string $salt;
    private int $minLength;
    private string $alphabet;
    
    public function __construct(string $salt, int $minLength, string $alphabet)
    {
        $this->salt = $salt;
        $this->minLength = $minLength;
        $this->alphabet = $alphabet;
    }
}
?>
-----
<?php

namespace Pgs\HashIdBundle\Tests\Rector\Fixtures\PropertyPromotion;

class BeforeService
{
    public function __construct(
        private string $salt,
        private int $minLength,
        private string $alphabet
    ) {
    }
}
?>