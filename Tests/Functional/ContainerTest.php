<?php

declare(strict_types=1);

namespace Jul6Art\PdfBundle\Tests\Functional;

use Jul6Art\PdfBundle\Archive\PdfArchiverInterface;
use Jul6Art\PdfBundle\Locale\LocaleSwitcher;
use Jul6Art\PdfBundle\Renderer\HtmlToPdfRendererInterface;
use Jul6Art\PdfBundle\Template\TemplateWhitelistResolver;
use PHPUnit\Framework\Attributes\CoversNothing;
use Twig\Environment;

/**
 * The first test to write, and the one that keeps paying: a real container, built with the bundle
 * registered.
 *
 * It catches what no unit test can — a services.yaml that does not parse, a reference to a service
 * that does not exist, a configuration node the extension reads under another name. Every one of
 * those is invisible until something boots.
 */
#[CoversNothing]
final class ContainerTest extends AbstractFunctionalTestCase
{
    public function testTheBundleBoots(): void
    {
        self::assertTrue($this->boot()->getParameter('pdf.enabled'));
    }

    /**
     * `enabled: false` must leave the bundle installed and inert — an application should be able
     * to switch it off without uninstalling it, and without its optional dependencies becoming
     * required.
     */
    public function testItCanBeDisabled(): void
    {
        self::assertFalse($this->boot('test', ['enabled' => false])->hasParameter('pdf.enabled'));
    }

    public function testTheRendererIsWiredBehindItsInterface(): void
    {
        self::assertInstanceOf(HtmlToPdfRendererInterface::class, $this->boot()->get(HtmlToPdfRendererInterface::class));
    }

    public function testTheArchiverIsWiredBehindItsInterface(): void
    {
        self::assertInstanceOf(PdfArchiverInterface::class, $this->boot()->get(PdfArchiverInterface::class));
    }

    public function testTheTemplateResolverIsRegistered(): void
    {
        self::assertInstanceOf(TemplateWhitelistResolver::class, $this->boot()->get(TemplateWhitelistResolver::class));
    }

    public function testTheLocaleSwitcherReceivesTheApplicationTranslator(): void
    {
        self::assertInstanceOf(LocaleSwitcher::class, $this->boot()->get(LocaleSwitcher::class));
    }

    /**
     * D-3 (`docs/analyse/pdf_bundle.md` § 6): configured in three projects, called in one. Proving
     * the extension actually registers when Twig is present is the only way this bundle's own test
     * suite can rule out repeating that gap.
     */
    public function testPdfAssetExtensionRegistersItsFunctionsWhenTwigIsPresent(): void
    {
        $twig = $this->boot('test', [], withTwig: true)->get('twig');

        self::assertInstanceOf(Environment::class, $twig);
        self::assertNotNull($twig->getFunction('pdf_image_path'));
        self::assertNotNull($twig->getFunction('pdf_image_data_uri'));
    }
}
