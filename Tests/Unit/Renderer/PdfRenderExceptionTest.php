<?php

declare(strict_types=1);

namespace Jul6Art\PdfBundle\Tests\Unit\Renderer;

use Jul6Art\PdfBundle\Renderer\PdfRenderException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(PdfRenderException::class)]
final class PdfRenderExceptionTest extends TestCase
{
    public function testEmptyOutputCarriesAnExplicitMessage(): void
    {
        self::assertSame('Dompdf produced no output.', PdfRenderException::emptyOutput()->getMessage());
    }
}
