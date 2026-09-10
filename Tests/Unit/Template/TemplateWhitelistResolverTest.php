<?php

declare(strict_types=1);

namespace Jul6Art\PdfBundle\Tests\Unit\Template;

use Jul6Art\PdfBundle\Template\TemplateWhitelistResolver;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(TemplateWhitelistResolver::class)]
final class TemplateWhitelistResolverTest extends TestCase
{
    public function testReturnsTheRequestedValueWhenAllowed(): void
    {
        $resolver = new TemplateWhitelistResolver();

        self::assertSame('modern', $resolver->resolve('modern', ['default', 'modern', 'minimal'], 'default'));
    }

    public function testFallsBackToTheDefaultWhenNotAllowed(): void
    {
        $resolver = new TemplateWhitelistResolver();

        self::assertSame('default', $resolver->resolve('hostile-value', ['default', 'modern', 'minimal'], 'default'));
    }

    public function testFallsBackToTheDefaultWhenNull(): void
    {
        $resolver = new TemplateWhitelistResolver();

        self::assertSame('default', $resolver->resolve(null, ['default', 'modern', 'minimal'], 'default'));
    }
}
