<?php

namespace Subtext\Persistables\Console\Utilities;

use DirectoryIterator;
use InvalidArgumentException;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;

class NamespaceIterator
{
    private string $rootPath;

    public function __construct(string $rootPath)
    {
        $this->rootPath = $rootPath;
    }

    public function getPsrClassNames(string $namespace): array
    {
        return [];
    }

    private function getFilenameArray(string $path, bool $recursive, bool $relative): array
    {
        $path = $this->validatePath($path);
        $data = [];
        foreach ($this->loopDirectory($path, $recursive) as $fileInfo) {
            $filename = $fileInfo->getPathname();
            if ($relative) {
                $filename = str_replace($path, '', $filename);
            }
            array_push($data, $filename);
        }
        return $data;
    }

    private function getLoop(string $path): DirectoryIterator
    {
        return new DirectoryIterator($path);
    }

    private function getRecursiveLoop(string $path): RecursiveIteratorIterator
    {
        return new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($path)
        );
    }

    private function loopDirectory(string $path, bool $recursive = false): iterable
    {
        if ($recursive) {
            $loop = $this->getRecursiveLoop($path);
        } else {
            $loop = $this->getLoop($path);
        }
        while ($loop->valid()) {
            $fileInfo = $loop->current();
            if ($fileInfo->isFile() && !$this->isDot($fileInfo)) {
                yield $fileInfo;
            }
            $loop->next();
        }
    }

    /**
     * @param string $path
     *
     * @return string
     * @throws InvalidArgumentException
     */
    private function validatePath(string $path): string
    {
        if (!is_dir($path)) {
            throw new InvalidArgumentException(
                "The supplied path is not a valid directory: $path"
            );
        }
        return realpath($path) . '/';
    }

    private function isDot(SplFileInfo $info): bool
    {
        return in_array(
            strtolower($info->getFilename()),
            ['.','..','.ds_store','thumbs.db','desktop.ini']
        );
    }

    private function findNamespaceInstallPath(string $namespace, array $psr4): ?array
    {
        $namespace = trim($namespace, '\\') . '\\';

        foreach ($psr4 as $prefix => $paths) {
            if (str_starts_with($namespace, $prefix) || str_starts_with($prefix, $namespace)) {
                return [
                    'prefix' => $prefix,
                    'paths' => $paths,
                ];
            }
        }

        return null;
    }

}
