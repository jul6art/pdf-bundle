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
    ) {
    }

    public static function default(): self
    {
        return new self();
    }
}
