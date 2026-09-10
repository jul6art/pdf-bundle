<?php

declare(strict_types=1);

namespace Jul6Art\PdfBundle\DependencyInjection;

use Jul6Art\PdfBundle\Asset\PdfAssetExtension;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use Twig\Extension\AbstractExtension;

/**
 * Wires the bundle's services and turns its configuration into something the services can read.
 *
 * Two rules this ecosystem arrived at the hard way:
 *
 * 1. **A brick whose dependency is optional is registered conditionally**, from here, guarded by
 *    `class_exists()` / `interface_exists()` — never by an attribute on the class. An
 *    `#[AsDecorator]` or `#[AsDoctrineListener]` on a vendor class is only honoured if the
 *    application autoconfigures `vendor/`, which it should not, and it makes the class
 *    unloadable when the package is absent.
 * 2. **A service that needs another *service* to exist is checked in a compiler pass**, not
 *    here: an extension runs before the other bundles have configured anything, so
 *    `$container->has('some.service')` is always false at this point.
 */
class PdfExtension extends Extension
{
    #[\Override]
    public function load(array $configs, ContainerBuilder $container): void
    {
        $loader = new YamlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));
        $loader->load('services.yaml');

        $config = $this->processConfiguration(new Configuration(), $configs);

        if (false === ($config['enabled'] ?? true)) {
            return;
        }

        // Exposed as a container parameter so an application can branch on it, and so
        // `debug:container --parameter` tells the truth about what is active.
        $container->setParameter('pdf.enabled', true);

        // Rule 1 above: symfony/twig-bundle is require-dev + suggest, never require — the
        // renderer itself never touches Twig (cf. HtmlToPdfRendererInterface). Without Twig
        // installed, there is nothing for a PDF template to be written in anyway, so the
        // extension is simply absent rather than failing to load.
        if (class_exists(AbstractExtension::class)) {
            $container->register(PdfAssetExtension::class)
                ->setArgument('$publicDir', $config['public_dir'])
                ->addTag('twig.extension');
        }
    }
}
