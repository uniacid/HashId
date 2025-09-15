<?php

namespace Pgs\HashIdBundle\Tests\Rector\Fixtures\ReadOnlyProperties;

class BeforeConfiguration
{
    private string $salt;
    private int $minLength;
    
    public function __construct(string $salt, int $minLength)
    {
        $this->salt = $salt;
        $this->minLength = $minLength;
    }
    
    public function getSalt(): string
    {
        return $this->salt;
    }
    
    public function getMinLength(): int
    {
        return $this->minLength;
    }
}
?>
-----
<?php

namespace Pgs\HashIdBundle\Tests\Rector\Fixtures\ReadOnlyProperties;

class BeforeConfiguration
{
    public function __construct(
        private readonly string $salt,
        private readonly int $minLength
    ) {
    }
    
    public function getSalt(): string
    {
        return $this->salt;
    }
    
    public function getMinLength(): int
    {
        return $this->minLength;
    }
}
?>