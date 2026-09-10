<?php

declare(strict_types=1);

namespace Jul6Art\PdfBundle\Template;

/**
 * Generalises the pattern found in `superp`'s `ErpPdfTemplateResolver`: a
 * requested template code that is not on the whitelist falls back to a
 * known-good default, silently — a hostile or stale tenant setting can
 * never steer the renderer onto an arbitrary path.
 *
 * The bundle owns only this decision. Building the actual Twig path (which
 * directory, which naming convention) stays the project's job — it is
 * business layout, not a generic mechanism.
 */
final class TemplateWhitelistResolver
{
    /**
     * @param list<string> $allowed
     */
    public function resolve(?string $requested, array $allowed, string $default): string
    {
        return null !== $requested && \in_array($requested, $allowed, true) ? $requested : $default;
    }
}
