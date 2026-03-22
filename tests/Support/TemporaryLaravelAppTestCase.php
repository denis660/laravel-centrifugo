<?php

declare(strict_types=1);

namespace denis660\Centrifugo\Test\Support;

use denis660\Centrifugo\Test\TestCase as PackageTestCase;
use FilesystemIterator;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

abstract class TemporaryLaravelAppTestCase extends PackageTestCase
{
    private static ?string $temporaryBasePath = null;

    private string $originalCwd;

    public static function applicationBasePath()
    {
        if (self::$temporaryBasePath === null) {
            self::$temporaryBasePath = sys_get_temp_dir().'/laravel-centrifugo-testbench-'.bin2hex(random_bytes(6));
        }

        return self::$temporaryBasePath;
    }

    public function setUp(): void
    {
        self::rebuildTemporaryBasePath();

        $this->originalCwd = getcwd() ?: dirname(__DIR__, 2);

        chdir(static::applicationBasePath());

        parent::setUp();
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        chdir($this->originalCwd);
    }

    public static function tearDownAfterClass(): void
    {
        parent::tearDownAfterClass();

        if (self::$temporaryBasePath !== null) {
            self::deleteDirectory(self::$temporaryBasePath);
        }
    }

    protected function appFilePath(string $path = ''): string
    {
        return rtrim(static::applicationBasePath(), '/').($path === '' ? '' : '/'.$path);
    }

    protected function readAppFile(string $path): string
    {
        return (string) file_get_contents($this->appFilePath($path));
    }

    protected function writeAppFile(string $path, string $contents): void
    {
        $directory = dirname($this->appFilePath($path));

        if (! is_dir($directory)) {
            mkdir($directory, 0777, true);
        }

        file_put_contents($this->appFilePath($path), $contents);
    }

    protected function deleteAppFile(string $path): void
    {
        $absolutePath = $this->appFilePath($path);

        if (is_file($absolutePath)) {
            unlink($absolutePath);
        }
    }

    private static function rebuildTemporaryBasePath(): void
    {
        $basePath = static::applicationBasePath();

        self::deleteDirectory($basePath);

        mkdir($basePath, 0777, true);

        self::copyDirectory(
            dirname(__DIR__, 2).'/vendor/orchestra/testbench-core/laravel',
            $basePath
        );
    }

    private static function copyDirectory(string $source, string $destination): void
    {
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($source, FilesystemIterator::SKIP_DOTS),
            RecursiveIteratorIterator::SELF_FIRST
        );

        foreach ($iterator as $item) {
            $target = $destination.'/'.substr($item->getPathname(), strlen($source) + 1);

            if ($item->isDir()) {
                if (! is_dir($target)) {
                    mkdir($target, 0777, true);
                }

                continue;
            }

            $directory = dirname($target);

            if (! is_dir($directory)) {
                mkdir($directory, 0777, true);
            }

            copy($item->getPathname(), $target);
        }
    }

    private static function deleteDirectory(string $path): void
    {
        if (! is_dir($path)) {
            return;
        }

        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($path, FilesystemIterator::SKIP_DOTS),
            RecursiveIteratorIterator::CHILD_FIRST
        );

        foreach ($iterator as $item) {
            if ($item->isDir()) {
                rmdir($item->getPathname());
            } else {
                unlink($item->getPathname());
            }
        }

        rmdir($path);
    }
}
