<?php

declare(strict_types=1);

namespace Jul6Art\PdfBundle\Archive;

/**
 * Stores rendered PDF bytes on disk and lets a later audit prove they were
 * not tampered with.
 *
 * The path is always supplied by the caller — this interface owns none of
 * the archival convention. Two conventions already differ between the two
 * consuming projects observed (a public-ish per-organization archive vs a
 * private RH storage path), and both stay in the project, not the bundle.
 */
interface PdfArchiverInterface
{
    public function hash(string $bytes): string;

    /**
     * Writes `$bytes` to `$path`, creating parent directories as needed.
     */
    public function store(string $bytes, string $path): void;

    /**
     * True when the file at `$path` still matches `$expectedHash`.
     */
    public function verify(string $path, string $expectedHash): bool;
}
