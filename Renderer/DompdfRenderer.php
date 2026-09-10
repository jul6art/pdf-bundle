<?php

declare(strict_types=1);

namespace Jul6Art\PdfBundle\Renderer;

use Dompdf\Dompdf;
use Dompdf\Options;

/**
 * The only implementation today — both consuming projects had already
 * settled on Dompdf independently, at the same locked version (`v3.1.6`),
 * for the same reason: it renders acceptably from server-authored HTML/CSS
 * without a headless browser or an external service to operate.
 *
 * `isRemoteEnabled` is hard-coded to `false` and carries no option to
 * override it: it is the only defence in the whole chain against a worker
 * process fetching an attacker-controlled URL (SSRF) while rasterising a
 * document. A consumer with a real need to fetch remote assets writes its
 * own {@see HtmlToPdfRendererInterface} implementation — which makes that
 * choice an explicit, reviewable line instead of a flipped default.
 */
final class DompdfRenderer implements HtmlToPdfRendererInterface
{
    #[\Override]
    public function render(string $html, ?PdfRenderOptions $options = null): string
    {
        $options ??= PdfRenderOptions::default();

        $dompdfOptions = new Options();
        $dompdfOptions->set('isRemoteEnabled', false);
        $dompdfOptions->set('defaultFont', $options->defaultFont);

        $dompdf = new Dompdf($dompdfOptions);
        $dompdf->loadHtml($html, 'UTF-8');
        $dompdf->setPaper($options->paperSize, $options->orientation);
        $dompdf->render();

        return PdfOutputGuard::assertNotEmpty($dompdf->output());
    }
}
