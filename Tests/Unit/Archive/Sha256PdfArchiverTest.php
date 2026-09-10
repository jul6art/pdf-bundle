<?php

declare(strict_types=1);

namespace Jul6Art\PdfBundle\Tests\Unit\Archive;

use Jul6Art\PdfBundle\Archive\Sha256PdfArchiver;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(Sha256PdfArchiver::class)]
final class Sha256PdfArchiverTest extends TestCase
{
    private string $dir;

    #[\Override]
    protected function setUp(): void
    {
        $this->dir = sys_get_temp_dir().'/pdf-bundle-archiver-test-'.bin2hex(random_bytes(4));
    }

    #[\Override]
    protected function tearDown(): void
    {
        if (!is_dir($this->dir)) {
            return;
        }

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($this->dir, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST,
        );

        foreach ($iterator as $file) {
            if (!$file instanceof \SplFileInfo) {
                continue;
            }

            $file->isDir() ? @rmdir($file->getPathname()) : @unlink($file->getPathname());
        }

        @rmdir($this->dir);
    }

    public function testHashIsSha256OfTheBytes(): void
    {
        $archiver = new Sha256PdfArchiver();

        self::assertSame(hash('sha256', 'bytes'), $archiver->hash('bytes'));
    }

    public function testStoreCreatesParentDirectoriesAndWritesTheBytes(): void
    {
        $archiver = new Sha256PdfArchiver();
        $path = $this->dir.'/nested/invoice.pdf';

        $archiver->store('%PDF-bytes%', $path);

        self::assertFileExists($path);
        self::assertSame('%PDF-bytes%', file_get_contents($path));
    }

    public function testVerifyIsTrueWhenTheFileStillMatchesTheHash(): void
    {
        $archiver = new Sha256PdfArchiver();
        $path = $this->dir.'/invoice.pdf';
        $archiver->store('%PDF-bytes%', $path);

        self::assertTrue($archiver->verify($path, $archiver->hash('%PDF-bytes%')));
    }

    public function testVerifyIsFalseWhenTheFileWasTampered(): void
    {
        $archiver = new Sha256PdfArchiver();
        $path = $this->dir.'/invoice.pdf';
        $archiver->store('%PDF-original%', $path);
        file_put_contents($path, '%PDF-tampered%');

        self::assertFalse($archiver->verify($path, $archiver->hash('%PDF-original%')));
    }

    public function testVerifyIsFalseWhenTheFileDoesNotExist(): void
    {
        $archiver = new Sha256PdfArchiver();

        self::assertFalse($archiver->verify($this->dir.'/missing.pdf', $archiver->hash('anything')));
    }
}
