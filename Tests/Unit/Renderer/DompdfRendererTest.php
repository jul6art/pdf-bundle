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

    /**
     * A reproducible render gives the SAME bytes for the same HTML and seed — what lets a consumer
     * checksum a generated file and regenerate it identically later (cegeta ADR-0032).
     */
    public function testAReproducibleRenderGivesTheSameBytesForTheSameSeed(): void
    {
        $renderer = new DompdfRenderer();
        $options = new PdfRenderOptions(reproducibleSeed: 'job-0f3a');

        $first = $renderer->render('<p>Sheet</p>', $options);
        usleep(1_100_000);
        $second = $renderer->render('<p>Sheet</p>', $options);

        self::assertSame(hash('sha256', $first), hash('sha256', $second));
        self::assertNotSame(
            hash('sha256', $first),
            hash('sha256', $renderer->render('<p>Sheet</p>', new PdfRenderOptions(reproducibleSeed: 'job-other'))),
            'Another seed gives another document identifier.',
        );
    }

    /** Without a seed, nothing changes: every render is a distinct document, as before. */
    public function testWithoutASeedEachRenderIsADistinctDocument(): void
    {
        $renderer = new DompdfRenderer();

        self::assertNotSame(hash('sha256', $renderer->render('<p>Sheet</p>')), hash('sha256', $renderer->render('<p>Sheet</p>')));
    }
}
