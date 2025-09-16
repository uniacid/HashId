<?php

namespace Pgs\HashIdBundle\Processor;

class ParametersProcessor
{
    private $converter;
    private $annotationProvider;
    protected $config;
    
    public function __construct($converter, $annotationProvider, array $config)
    {
        $this->converter = $converter;
        $this->annotationProvider = $annotationProvider;
        $this->config = $config;
    }
    
    public function getConverter()
    {
        return $this->converter;
    }
    
    public function setConfig(array $config)
    {
        $this->config = $config;
    }
}
?>
-----
<?php

namespace Pgs\HashIdBundle\Processor;

use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;

#[AutoconfigureTag('pgs_hashid.service')]
class ParametersProcessor
{
    public function __construct(
        private readonly $converter,
        private readonly $annotationProvider,
        protected array $config
    ) {
    }
    
    public function getConverter(): mixed
    {
        return $this->converter;
    }
    
    public function setConfig(array $config)
    {
        $this->config = $config;
    }
}
?>