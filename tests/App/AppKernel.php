<?php declare(strict_types=1);

namespace Pgs\HashIdBundle\Tests\App;

use Symfony\Bundle\FrameworkBundle\FrameworkBundle;
use Pgs\HashIdBundle\PgsHashIdBundle;
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\HttpKernel\Kernel;

class AppKernel extends Kernel
{
    public function registerBundles(): iterable
    {
        $bundles = [
            FrameworkBundle::class => ['all' => true],
            PgsHashIdBundle::class => ['all' => true],
        ];
        foreach ($bundles as $class => $envs) {
            if (isset($envs['all']) || isset($envs[$this->environment])) {
                yield new $class();
            }
        }
    }

    public function registerContainerConfiguration(LoaderInterface $loader): void
    {
        $loader->load(__DIR__.'/config.yml');
    }

    public function getLogDir(): string
    {
        return \sys_get_temp_dir().'/logs';
    }

    public function getCacheDir(): string
    {
        return \sys_get_temp_dir().'/cache/'.$this->environment;
    }
}
