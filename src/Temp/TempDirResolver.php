<?php

declare(strict_types=1);

namespace Efabrica\PHPStanLatte\Temp;

use FilesystemIterator;
use Nette\Utils\FileSystem;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use RuntimeException;
use SplFileInfo;

final class TempDirResolver
{
    private const MAX_AGE = 7 * 24 * 60 * 60; // one week
    private const MAX_SIZE = 100 * 1024 * 1024; // 100 MB

    private string $tmpDir;

    public function __construct(?string $tmpDir)
    {
        $tmpDir = $tmpDir ? rtrim($tmpDir, DIRECTORY_SEPARATOR) : sys_get_temp_dir() . '/phpstan-latte';

        if (is_dir($tmpDir) &&
           (time() - (int)filemtime($tmpDir) > self::MAX_AGE ||
           $this->getDirTotalSize($tmpDir) > self::MAX_SIZE)
        ) {
            $this->pruneDir($tmpDir);
        }

        if (!is_dir($tmpDir)) {
            Filesystem::createDir($tmpDir);
        }
        $tmpDir = realpath($tmpDir) ?: $tmpDir;
        if (!is_writable($tmpDir)) {
            throw new RuntimeException(sprintf('Temp dir "%s" is not writable', $tmpDir));
        }
        $this->tmpDir = $tmpDir;
    }

    public function resolveCompileDir(): string
    {
        return $this->tmpDir . DIRECTORY_SEPARATOR . 'compiled-templates' . DIRECTORY_SEPARATOR;
    }

    public function resolveAnalyseDir(): string
    {
        return $this->tmpDir . DIRECTORY_SEPARATOR . 'analysed-templates' . DIRECTORY_SEPARATOR;
    }

    public function resolveCollectorDir(): string
    {
        return $this->tmpDir . DIRECTORY_SEPARATOR . 'collector-cache' . DIRECTORY_SEPARATOR;
    }

    private function pruneDir(string $tmpDir): void
    {
        $ri = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($tmpDir, FilesystemIterator::SKIP_DOTS),
            RecursiveIteratorIterator::CHILD_FIRST
        );
        /** @var SplFileInfo $file */
        foreach ($ri as $file) {
            Filesystem::delete($file->getPathname());
        }
    }

    private function getDirTotalSize(string $tmpDir): int
    {
        $size = 0;
        /** @var SplFileInfo $file */
        foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($tmpDir, FilesystemIterator::SKIP_DOTS)) as $file) {
            $size += $file->getSize();
        }
        return $size;
    }
}
