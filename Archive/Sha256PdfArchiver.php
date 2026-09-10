<?php

declare(strict_types=1);

namespace Jul6Art\PdfBundle\Archive;

/**
 * The hash/store/verify triplet found duplicated five times across
 * `superp`'s PDF generators (Invoice, Quote, PurchaseOrder, Payslip,
 * HrDocument), always identical.
 */
final class Sha256PdfArchiver implements PdfArchiverInterface
{
    #[\Override]
    public function hash(string $bytes): string
    {
        return \hash('sha256', $bytes);
    }

    #[\Override]
    public function store(string $bytes, string $path): void
    {
        $dir = \dirname($path);

        if (!\is_dir($dir) && !@\mkdir($dir, 0775, true) && !\is_dir($dir)) {
            throw new \RuntimeException(\sprintf('Unable to create archive directory "%s".', $dir));
        }

        \file_put_contents($path, $bytes);
    }

    #[\Override]
    public function verify(string $path, string $expectedHash): bool
    {
        if (!\is_file($path)) {
            return false;
        }

        $actual = \hash_file('sha256', $path);

        return false !== $actual && \hash_equals($expectedHash, $actual);
    }
}
