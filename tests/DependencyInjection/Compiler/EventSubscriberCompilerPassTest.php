<?php declare(strict_types=1);

namespace DependencyInjection\Compiler;

use Pgs\HashIdBundle\DependencyInjection\Compiler\EventSubscriberCompilerPass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;

class EventSubscriberCompilerPassTest extends TestCase
{
    private EventSubscriberCompilerPass $pass;

    private ContainerBuilder $container;

    private Definition $paramConverterListenerDefinition;

    private Definition $decodeControllerParametersDefinition;

    protected function setUp(): void
    {
        $this->pass = new EventSubscriberCompilerPass();
        $this->container = new ContainerBuilder();

        $paramConverterListenerDefinition = new Definition();
        $paramConverterListenerDefinition->addTag('kernel.event_subscriber');
        $this->paramConverterListenerDefinition = $paramConverterListenerDefinition;
        $this->container->setDefinition(
            'sensio_framework_extra.converter.listener',
            $this->paramConverterListenerDefinition,
        );

        $this->decodeControllerParametersDefinition = new Definition();
        $this->container->setDefinition(
            'pgs_hash_id.service.decode_controller_parameters',
            $this->decodeControllerParametersDefinition,
        );
    }

    public function testProcessForExistingParamConverterListener(): void
    {
        $this->pass->process($this->container);
        $listenerDefinition = $this->container->getDefinition('sensio_framework_extra.converter.listener');
        self::assertFalse($listenerDefinition->hasTag('kernel.event_subscriber'));
    }
}
