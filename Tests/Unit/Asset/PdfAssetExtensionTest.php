<?php

declare(strict_types=1);

namespace Jul6Art\PdfBundle\Tests\Unit\Asset;

use Jul6Art\PdfBundle\Asset\PdfAssetExtension;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Twig\TwigFunction;

#[CoversClass(PdfAssetExtension::class)]
final class PdfAssetExtensionTest extends TestCase
{
    private string $publicDir;

    #[\Override]
    protected function setUp(): void
    {
        $this->publicDir = sys_get_temp_dir().'/pdf-bundle-asset-test-'.bin2hex(random_bytes(4));
        mkdir($this->publicDir);
    }

    #[\Override]
    protected function tearDown(): void
    {
        array_map(unlink(...), glob($this->publicDir.'/*') ?: []);
        @rmdir($this->publicDir);
    }

    public function testResolveReturnsNullForEmptyInput(): void
    {
        $extension = new PdfAssetExtension($this->publicDir);

        self::assertNull($extension->resolve(null));
        self::assertNull($extension->resolve(''));
    }

    public function testResolveJoinsThePublicDirAndStripsALeadingSlash(): void
    {
        $extension = new PdfAssetExtension($this->publicDir);

        self::assertSame($this->publicDir.'/logo.png', $extension->resolve('/logo.png'));
        self::assertSame($this->publicDir.'/logo.png', $extension->resolve('logo.png'));
    }

    public function testDataUriReturnsNullWhenTheFileDoesNotExist(): void
    {
        $extension = new PdfAssetExtension($this->publicDir);

        self::assertNull($extension->dataUri('missing.png'));
    }

    /**
     * A truncated upload must not become a silently white square in the PDF — see the class
     * docblock. Below the threshold, the function refuses rather than emitting a broken image.
     */
    public function testDataUriRefusesAFileBelowTheMinimumSize(): void
    {
        file_put_contents($this->publicDir.'/tiny.png', 'x');
        $extension = new PdfAssetExtension($this->publicDir);

        self::assertNull($extension->dataUri('tiny.png'));
    }

    public function testDataUriEncodesARealFileAsBase64(): void
    {
        $bytes = str_repeat('a', 200);
        file_put_contents($this->publicDir.'/logo.png', $bytes);
        $extension = new PdfAssetExtension($this->publicDir);

        $uri = $extension->dataUri('logo.png');

        self::assertNotNull($uri);
        self::assertStringStartsWith('data:', $uri);
        self::assertStringContainsString(';base64,'.base64_encode($bytes), $uri);
    }

    public function testExposesBothTwigFunctions(): void
    {
        $names = array_map(
            static fn (TwigFunction $f): string => $f->getName(),
            new PdfAssetExtension($this->publicDir)->getFunctions(),
        );

        self::assertSame(['pdf_image_path', 'pdf_image_data_uri'], $names);
    }
}
