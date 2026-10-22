<?php

namespace Subtext\Persistables\Console\Schemas\Attributes;

use ArrayObject;
use DirectoryIterator;
use Generator;
use InvalidArgumentException;
use JsonException;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use ReflectionException;
use SplFileInfo;
use Subtext\Persistables\Databases\Meta\Collection;
use Subtext\Persistables\Databases\Meta\Factory;
use Subtext\Persistables\Exceptions\SchemaDefinitionException;
use Subtext\Persistables\Persistable;
use Symfony\Component\Filesystem\Path;
use Throwable;

class Reader
{
    protected string $rootPath;
    protected Factory $factory;
    protected ?Namespaces\Collection $namespaces = null;
    protected ?Errors\Collection $errors = null;

    public function __construct(string $root)
    {
        $this->rootPath = $root;
        $this->factory  = Factory::getInstance();
    }

    /**
     * @throws InvalidArgumentException
     */
    public function getMetaCollection(): Collection
    {
        $collection = new Collection();
        foreach ($this->getNamespaces() as $namespace => $path) {
            if (Path::isRelative($path)) {
                $path = Path::join($this->rootPath, $path);
            }
            if (!file_exists($path)) {
                throw new InvalidArgumentException(sprintf(
                    'Path to persistables "%s" does not exist',
                    $path
                ));
            }
            foreach ($this->loopDirectory($path, $namespace, true) as $class) {
                try {
                    $collection->set($class, $this->factory->get($class));
                } catch (Throwable $e) {
                    $this->getMetaErrors()->append($e);
                }
            }
        }
        return $collection;
    }

    public function getMetaErrors(): Errors\Collection
    {
        if ($this->errors === null) {
            $this->errors = new Errors\Collection();
        }
        return $this->errors;
    }

    public function resetMetaErrors(): void
    {
        $this->errors = null;
    }

    private function loopDirectory(string $path, string $ns, bool $recursive = false): Generator
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
                    $rel   = Path::makeRelative($fileInfo->getPathname(), $path);
                    $class = str_replace('/', '\\', substr($rel, 0, -4));
                    $fqdn  = $ns . $class;
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

    /**
     * @throws InvalidArgumentException
     */
    private function getNamespaces(): Namespaces\Collection
    {
        if ($this->namespaces === null) {
            $composer = Path::join($this->rootPath, 'composer.json');
            if (!file_exists($composer) || !is_readable($composer)) {
                throw new InvalidArgumentException(sprintf(
                    'Could not find or read the composer.json file at "%s"',
                    $composer
                ));
            }
            try {
                $json = json_decode(
                    file_get_contents($this->rootPath . '/composer.json'),
                    flags: JSON_THROW_ON_ERROR
                );
            } catch(JsonException $e) {
                throw new InvalidArgumentException(sprintf(
                    'Could not parse the composer.json at "%s" to JSON notation',
                    $composer
                ));
            }
            $psr4 = $json?->autoload?->{'psr-4'};
            $this->namespaces = new Namespaces\Collection();
            if (is_object($psr4)) {
                $psr4 = new ArrayObject($psr4);
                foreach ($psr4 as $namespace => $path) {
                    $this->namespaces->set($namespace, $path);
                }
            }
        }
        return $this->namespaces;
    }
}
