<?php

declare(strict_types=1);

namespace Jul6Art\PdfBundle\Renderer;

/**
 * Turns an already-rendered HTML string into PDF bytes.
 *
 * Deliberately narrow: this does not accept a Twig template or a context
 * array. Rendering the HTML — brand colours, badges, legal mentions, the
 * document's whole visual identity — is business content and stays in the
 * consuming project, exactly like the Twig "chrome" templates it comes from.
 */
interface HtmlToPdfRendererInterface
{
    /**
     * @throws PdfRenderException when the renderer produced no bytes
     */
    public function render(string $html, ?PdfRenderOptions $options = null): string;
}
