<?php

declare(strict_types=1);

namespace Jul6Art\PdfBundle\Tests\Unit\Renderer;

use Jul6Art\PdfBundle\Renderer\DompdfRenderer;
use Jul6Art\PdfBundle\Renderer\PdfRenderOptions;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(DompdfRenderer::class)]
final class DompdfRendererTest extends TestCase
{
    public function testRendersRealPdfBytes(): void
    {
        $pdf = new DompdfRenderer()->render('<p>Hello</p>');

        self::assertStringStartsWith('%PDF-', $pdf);
    }

    public function testAcceptsCustomOptions(): void
    {
        $pdf = new DompdfRenderer()->render(
            '<p>Landscape</p>',
            new PdfRenderOptions(paperSize: 'A5', orientation: 'landscape'),
        );

        self::assertStringStartsWith('%PDF-', $pdf);
    }

    /**
     * The single line of defence against SSRF while rasterising a document: a remote image must
     * never be fetched, regardless of options passed in. Not configurable — see the class docblock.
     */
    public function testNeverFetchesRemoteContent(): void
    {
        $start = microtime(true);

        // A non-routable address (RFC 5737 TEST-NET-1): if this were fetched, the connection
        // attempt would need to time out first, taking several seconds.
        $pdf = new DompdfRenderer()->render('<img src="http://192.0.2.1/should-not-be-fetched.png">');

        self::assertLessThan(2.0, microtime(true) - $start, 'A remote image must never be fetched.');
        self::assertStringStartsWith('%PDF-', $pdf);
    }
}
