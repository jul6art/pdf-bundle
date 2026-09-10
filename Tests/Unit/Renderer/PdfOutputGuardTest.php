<?php

declare(strict_types=1);

namespace Jul6Art\PdfBundle\Tests\Unit\Renderer;

use Jul6Art\PdfBundle\Renderer\PdfOutputGuard;
use Jul6Art\PdfBundle\Renderer\PdfRenderException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(PdfOutputGuard::class)]
final class PdfOutputGuardTest extends TestCase
{
    public function testThrowsOnFalseOutput(): void
    {
        $this->expectException(PdfRenderException::class);

        PdfOutputGuard::assertNotEmpty(false);
    }

    public function testThrowsOnEmptyStringOutput(): void
    {
        $this->expectException(PdfRenderException::class);

        PdfOutputGuard::assertNotEmpty('');
    }

    public function testReturnsRealOutputUnchanged(): void
    {
        self::assertSame('%PDF-1.7 …bytes…', PdfOutputGuard::assertNotEmpty('%PDF-1.7 …bytes…'));
    }
}
