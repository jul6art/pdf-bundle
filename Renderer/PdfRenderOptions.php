<?php

declare(strict_types=1);

namespace Jul6Art\PdfBundle\Renderer;

/**
 * Immutable render options. Replaces the seven identical
 * `new Options(); $options->set('isRemoteEnabled', false); $options->set('defaultFont', 'DejaVu Sans');`
 * blocks found duplicated across two consuming projects.
 *
 * `isRemoteEnabled` is deliberately absent here — see {@see DompdfRenderer}.
 */
final class PdfRenderOptions
{
    public function __construct(
        public readonly string $paperSize = 'A4',
        public readonly string $orientation = 'portrait',
        public readonly string $defaultFont = 'DejaVu Sans',
        /**
         * When set, the same HTML rendered with the same seed gives the SAME bytes: the document
         * identifier and dates are derived from it instead of the clock and a random number. For a
         * consumer that checksums a generated file and must regenerate it identically. Null (the
         * default) keeps every render a distinct document.
         */
        public readonly ?string $reproducibleSeed = null,
    ) {
    }

    public static function default(): self
    {
        return new self();
    }
}
