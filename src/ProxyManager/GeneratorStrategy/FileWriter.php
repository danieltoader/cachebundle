<?php

declare(strict_types=1);

namespace Emag\CacheBundle\ProxyManager\GeneratorStrategy;

use ProxyManager\GeneratorStrategy\GeneratorStrategyInterface;
use Zend\Code\Generator\ClassGenerator;
use ProxyManager\Exception\FileNotWritableException;
use ProxyManager\FileLocator\FileLocatorInterface;

/**
 * Class FileWriter
 *
 * @package Emag\CacheBundle\ProxyManager\GeneratorStrategy
 */
class FileWriter implements GeneratorStrategyInterface
{

    /**
     * @var \ProxyManager\FileLocator\FileLocatorInterface
     */
    protected $fileLocator;

    /**
     * @var callable
     */
    private $emptyErrorHandler;

    /**
     * @param \ProxyManager\FileLocator\FileLocatorInterface $fileLocator
     */
    public function __construct(FileLocatorInterface $fileLocator)
    {
        $this->fileLocator = $fileLocator;
        $this->emptyErrorHandler = function () {
        };
    }

    /**
     * Write generated code to disk and return the class code
     *
     * {@inheritDoc}
     *
     * @throws FileNotWritableException
     */
    public function generate(ClassGenerator $classGenerator): string
    {
        $className = trim($classGenerator->getNamespaceName(), '\\')
          .'\\'.trim($classGenerator->getName(), '\\');
        $generatedCode = $classGenerator->generate();
        $fileName = $this->fileLocator->getProxyFileName($className);

        set_error_handler($this->emptyErrorHandler);

        try {
            $this->writeFile("<?php\n\n".$generatedCode, $fileName);

            return $generatedCode;
        } finally {
            restore_error_handler();
        }
    }

    /**
     * Writes the source file in such a way that race conditions are avoided when the same file is written
     * multiple times in a short time period
     *
     * @param string $source
     * @param string $location
     *
     * @throws FileNotWritableException
     */
    private function writeFile(string $source, string $location)
    {
        $tmpFileName = tempnam($location, 'temporaryProxyManagerFile');

        file_put_contents($tmpFileName, $source);
        chmod($tmpFileName, 0666 & ~umask());

        if (!rename($tmpFileName, $location)) {
            unlink($tmpFileName);

            throw FileNotWritableException::fromInvalidMoveOperation($tmpFileName, $location);
        }
    }
}
