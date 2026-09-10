<?php

declare(strict_types=1);

namespace Jul6Art\PdfBundle\Renderer;

/**
 * A pure static check, deliberately not a mock target: forcing Dompdf itself to fail on cue from a
 * black-box test is unreliable (even empty HTML input renders a valid blank PDF), so the invariant
 * — no renderer may return `false` or `''` without raising — is proven here instead, independent of
 * Dompdf's actual failure modes.
 */
final class PdfOutputGuard
{
    /**
     * @param string|false $output whatever a renderer produced
     *
     * @throws PdfRenderException when the renderer produced no bytes
     */
    public static function assertNotEmpty(string|false $output): string
    {
        if (false === $output || '' === $output) {
            throw PdfRenderException::emptyOutput();
        }

        return $output;
    }
}
