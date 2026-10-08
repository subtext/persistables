<?php

namespace Subtext\Persistables\Console\Schemas\Attributes;

use DirectoryIterator;
use Generator;
use InvalidArgumentException;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use ReflectionException;
use SplFileInfo;
use Subtext\Persistables\Databases\Meta\Collection;
use Subtext\Persistables\Databases\Meta\Factory;
use Subtext\Persistables\Exceptions\SchemaDefinitionException;
use Subtext\Persistables\Persistable;

class Reader
{
    protected string $rootPath;
    protected Factory $factory;
    protected ?Namespaces\Collection $namespaces = null;

    public function __construct(string $root)
    {
        $this->rootPath = $root;
        $this->factory  = Factory::getInstance();
    }

    /**
     * @throws InvalidArgumentException
     * @throws ReflectionException
     * @throws SchemaDefinitionException
     */
    public function getMetaCollection(string $path): Collection
    {
        if (!file_exists($path)) {
            throw new InvalidArgumentException(sprintf(
                'Path to persistables "%s" does not exist',
                $path
            ));
        }
        $collection = new Collection();
        foreach ($this->loopDirectory($path, true) as $class) {
            $collection->set($class, $this->factory->get($class));
        }
        return $collection;
    }

    private function loopDirectory(string $path, bool $recursive = false): Generator
    {
        if ($recursive) {
            $loop = $this->getRecursiveLoop($path);
        } else {
            $loop = $this->getLoop($path);
        }
        while ($loop->valid()) {
            $fileInfo = $loop->current();
            if ($fileInfo->isFile() && !$this->isDot($fileInfo)) {
                if ($fileInfo->getExtension() === 'php') {
                    $fqdn = $this->getFullyQualifiedDomainName($fileInfo);
                    if (is_subclass_of($fqdn, Persistable::class)) {
                        yield $fqdn;
                    }
                }
            }
            $loop->next();
        }
    }

    private function getRecursiveLoop(string $path): RecursiveIteratorIterator
    {
        return new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($path)
        );
    }

    private function getLoop(string $path): DirectoryIterator
    {
        return new DirectoryIterator($path);
    }

    private function isDot(SplFileInfo $fileInfo): bool
    {
        return in_array(
            strtolower($fileInfo->getFilename()),
            ['.','..','.ds_store','thumbs.db','desktop.ini']
        );
    }

    private function getFullyQualifiedDomainName(SplFileInfo $fileInfo): string
    {
        $fqdn  = null;
        $ns    = $this->getNamespaces($this->rootPath);
        $class = $fileInfo->getBasename('.' . $fileInfo->getExtension());
        if ($namespace = $ns->getNamespace(str_replace($this->rootPath, '', $fileInfo->getPath()))) {
            $fqdn = $namespace . '\\' . $class;
        }
        if ($fqdn === null) {
            throw new InvalidArgumentException(sprintf(
                'Could not find the fqdn at "%s"',
                $fileInfo->getFilename()
            ));
        }
        return $fqdn;
    }

    private function getNamespaces(string $root): Namespaces\Collection
    {
        if ($this->namespaces === null) {
            if (!file_exists($root . '/composer.json')) {
                throw new InvalidArgumentException(sprintf(
                    'Could not find the composer.json file at "%s"',
                    $root
                ));
            }
            $json = json_decode(
                file_get_contents($root . '/composer.json'),
                flags: JSON_THROW_ON_ERROR
            );
            $this->namespaces = new Namespaces\Collection();
            $psr4             = $json['autoload']['psr-4'] ?? [];
            foreach ($psr4 as $namespace => $path) {
                $this->namespaces->set($namespace, $path);
            }

        }
        return $this->namespaces;
    }
}
