<?php

namespace Pgs\HashIdBundle\Service;

class HasherFactory
{
    private $hashidsConverter;
    private $config;
    
    public function __construct(HashidsConverter $hashidsConverter, array $config)
    {
        $this->hashidsConverter = $hashidsConverter;
        $this->config = $config;
    }
    
    public function getConverter()
    {
        return $this->hashidsConverter;
    }
}
?>
-----
<?php

namespace Pgs\HashIdBundle\Service;

use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;

#[AutoconfigureTag('pgs_hashid.service')]
class HasherFactory
{
    public function __construct(
        private readonly HashidsConverter $hashidsConverter,
        private readonly array $config
    ) {
    }
    
    public function getConverter(): mixed
    {
        return $this->hashidsConverter;
    }
}
?>