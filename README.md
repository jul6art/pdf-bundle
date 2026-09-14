<p align="center">
    <a href="https://devinthehood.com"><img src="https://github.com/jul6art/symfony-skeleton-generator/blob/master/public/img/logo.png?raw=true" alt="logo dev in the hood" width="400"></a>
</p>

HTML to PDF rendering for Symfony
=================================

<p align="left">
    <a href="https://opensource.org/licenses/MIT" target="_blank"><img src="https://img.shields.io/badge/License-MIT-yellow.svg" alt="License"></a>
    <img src="https://img.shields.io/static/v1?label=stable&message=v1&color=0ea5e9" alt="Version">
</p>

HTML to PDF rendering for Symfony

Requirements
------------

- PHP ^8.5
- Symfony ^7.4 || ^8.0

Installation
------------

```shell
composer require jul6art/pdf-bundle
```

Then register it in `config/bundles.php` (Flex does this for you):

```php
Jul6Art\PdfBundle\PdfBundle::class => ['all' => true],
```

Configuration
-------------

```yaml
# config/packages/pdf.yaml
pdf:
    # Leaves the bundle installed and inert when false.
    enabled: true
```

`pdf.enabled` is also exposed as a container parameter.

Usage
-----

### Rendering HTML as PDF

Inject `HtmlToPdfRendererInterface`. It takes an already-rendered HTML string — building that
string (a Twig template, brand colours, legal mentions) stays entirely your code:

```php
use Jul6Art\PdfBundle\Renderer\HtmlToPdfRendererInterface;
use Jul6Art\PdfBundle\Renderer\PdfRenderOptions;

final class InvoicePdfGenerator
{
    public function __construct(
        private readonly HtmlToPdfRendererInterface $renderer,
        private readonly Environment $twig,
    ) {
    }

    public function generate(Invoice $invoice): string
    {
        $html = $this->twig->render('pdf/invoice.html.twig', ['invoice' => $invoice]);

        // Options are optional — A4 portrait, DejaVu Sans, is the default.
        return $this->renderer->render($html, new PdfRenderOptions(orientation: 'landscape'));
    }
}
```

`render()` either returns real PDF bytes or throws `PdfRenderException` — it never returns an empty
string. Dompdf itself returns `false` (and, on some failures, `''`) instead of raising; catching
that at the source is the reason this method exists rather than a bare `new Dompdf()` at each call
site. **`isRemoteEnabled` is hard-coded to `false` and not configurable** — the only defence in the
chain against a worker fetching an attacker-controlled URL while rasterising a document. A real
need to fetch remote assets means writing your own `HtmlToPdfRendererInterface` implementation,
which makes that choice an explicit, reviewable line instead of a flipped default.

### Archiving with an integrity hash

`PdfArchiverInterface` is the hash/store/verify triplet a PDF generator almost always needs once it
archives its output — the path convention (per-organization, per-year, public or private disk)
stays yours:

```php
use Jul6Art\PdfBundle\Archive\PdfArchiverInterface;

final class InvoicePdfGenerator
{
    public function __construct(
        private readonly HtmlToPdfRendererInterface $renderer,
        private readonly PdfArchiverInterface $archiver,
        private readonly string $uploadBasePath,
    ) {
    }

    public function storeForInvoice(Invoice $invoice): string
    {
        if (null !== $invoice->getPdfPath() && is_file($invoice->getPdfPath())) {
            return $invoice->getPdfPath(); // Immutable once archived — never re-render.
        }

        $bytes = $this->renderer->render(/* … */);
        $path = sprintf('%s/%s/%s.pdf', $this->uploadBasePath, $invoice->getOrganization()->getSlug(), $invoice->getNumber());

        $this->archiver->store($bytes, $path);
        $invoice->setPdfPath($path);
        $invoice->setPdfHash($this->archiver->hash($bytes));

        return $path;
    }

    /** For an audit cron that walks every archived PDF and flags tampered files. */
    public function verifyArchive(Invoice $invoice): bool
    {
        return $this->archiver->verify($invoice->getPdfPath(), $invoice->getPdfHash());
    }
}
```

The hash and the path live on **your** entity — `PdfArchiverInterface` owns none of that
persistence, in keeping with the bundle owning no entity of its own.

### Picking a template from a whitelist

`TemplateWhitelistResolver` generalises one pattern: a per-tenant setting that names a template
must never steer the renderer onto an arbitrary path. An unrecognised value falls back to a known
default, silently:

```php
use Jul6Art\PdfBundle\Template\TemplateWhitelistResolver;

$template = $resolver->resolve(
    $organization->getInvoiceTemplateSetting(), // whatever a tenant configured, untrusted
    ['default', 'modern', 'minimal'],
    'default',
);

$path = sprintf('pdf/invoice/%s.html.twig', $template);
```

Building the actual Twig path is your job — the bundle only owns the whitelist decision.

### Rendering in the recipient's locale

`LocaleSwitcher` temporarily switches the translator's locale for a callback and restores it
afterwards, even if the callback throws — typically rendering a document in its recipient's
language regardless of the current request's locale:

```php
use Jul6Art\PdfBundle\Locale\LocaleSwitcher;

$html = $this->localeSwitcher->withLocale(
    $invoice->getCustomer()->getLocale(),
    fn (): string => $this->twig->render('pdf/invoice.html.twig', ['invoice' => $invoice]),
);
```

### Printing an image inside a PDF

Twig's `asset()` yields an HTTP URL relative to the current request — the renderer has no base to
resolve a relative one, and does not fetch remote URLs (`isRemoteEnabled: false`, above). Use the
two functions this bundle registers instead:

1. **The functions**: `pdf_image_path(organization.logoPath)` (a filesystem path) or
   `pdf_image_data_uri(organization.logoPath)` (a base64 `data:` URI — prefer this one for small
   images: logos, headers, footers).
2. **What registers them**: `Jul6Art\PdfBundle\Asset\PdfAssetExtension`, tagged `twig.extension`
   from `PdfExtension::load()` — **only when `symfony/twig-bundle` is installed**. Without Twig
   there is nothing to render a PDF template with anyway, so the extension is simply absent rather
   than failing to load.
3. **What to configure**: `pdf.public_dir` (default `%kernel.project_dir%/public`) — the filesystem
   root both functions resolve a stored path against.
4. **The trap**: a file under `pdf_image_data_uri`'s minimum size (100 bytes) returns `null` rather
   than a broken data URI. A truncated upload would otherwise render as a **silent white square** —
   worse than no image, because nothing signals the failure. One real consumer configured
   `pdf.public_dir` and never called either function: its organization logo never printed on a
   single PDF, despite a comment in the template claiming it did. Call the function; configuring
   the directory alone does nothing.

```twig
<img src="{{ pdf_image_data_uri(organization.logoPath) }}">
```

Quality assurance
-----------------

```shell
composer qa            # cs-check + rector-check + phpstan (level max) + phpunit
```

Run `composer qa`, not the single tool you have in mind: the CI's "Coding standards" job runs
Rector too, and its `lowest deps` job installs the minimum of every constraint — which is where
this ecosystem has repeatedly found what a local run could not.

`extra.symfony.require` states which Symfony line this bundle targets; the CI enforces it with
`SYMFONY_REQUIRE` on both the highest and the lowest job. A local `composer install` may still
resolve a newer Symfony, which broadens what you exercise rather than narrowing it — but it means
the toolchain can propose something that only makes sense on one branch. `rector.php` skips one
such rule already, with the reason written next to it.

Whatever you do, keep the code free of classes that exist on only one of the declared branches.
A bundle promising `^7.4 || ^8.0` has to hold both.

License
-------

HTML to PDF rendering for Symfony is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).

&copy; 2026 [Jul6Art](https://devinthehood.com/)
