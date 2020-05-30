<?php

namespace EmagTechLabs\CacheBundle\Tests;

use Doctrine\Common\Annotations\AnnotationRegistry;
use EmagTechLabs\CacheBundle\EmagCacheBundle;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\HttpKernel\Kernel;

class StaticMethodCacheTest extends KernelTestCase
{
    protected static function getKernelClass()
    {
        return get_class(
            new class('test_static_method', []) extends Kernel {
                public function registerBundles()
                {
                    return [
                        new EmagCacheBundle(),
                    ];
                }

                public function registerContainerConfiguration(LoaderInterface $loader)
                {
                    $loader->load(__DIR__.'/config/config_static.yml');
                }

                public function __construct($environment, $debug)
                {
                    parent::__construct($environment, $debug);

                    $loader = require __DIR__.'/../vendor/autoload.php';

                    AnnotationRegistry::registerLoader([$loader, 'loadClass']);
                    $this->rootDir = __DIR__.'/app/';
                }
            }
        );
    }

    public function tearDown()
    {
        static::$class = null;
    }

    /**
     * @expectedException \Symfony\Component\DependencyInjection\Exception\BadMethodCallException
     * @expectedExceptionMessage Static methods can not be cached!
     */
    public function testStaticMethod()
    {
        static::$class = null;

        self::bootKernel(['environment' => 'test_static_method']);
    }
}
