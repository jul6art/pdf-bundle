<?php

declare(strict_types=1);

namespace Jul6Art\PdfBundle\Renderer;

/**
 * Thrown when Dompdf produced no usable output — `Dompdf::output()` returns
 * `false` on some internal failures and, on others, an empty string; it
 * never raises on its own. Archiving that unchecked freezes a 0-byte file
 * behind a valid SHA-256 hash of the empty string.
 *
 * Two independent consumers hit this gap the same way before this bundle
 * existed: one guarded it (`cereezer`'s `ReportGenerationException`, ADR-0011),
 * the other never did (`superp`'s seven PDF generators, D-1).
 */
final class PdfRenderException extends \RuntimeException
{
    public static function emptyOutput(): self
    {
        return new self('Dompdf produced no output.');
    }
}
