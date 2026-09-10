<?php

declare(strict_types=1);

namespace Jul6Art\PdfBundle\Locale;

use Symfony\Contracts\Translation\LocaleAwareInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Temporarily switches the translator's locale for the duration of a
 * callback — typically rendering a document in its recipient's language
 * regardless of the current request's locale — and restores it afterwards
 * even if the callback throws.
 *
 * Already 100% generic in its original form (`superp`'s `PdfLocaleSwitcher`):
 * its only dependency is `TranslatorInterface`, nothing PDF-specific about
 * it. It lives in this bundle because rendering a PDF is its only consumer
 * today, not because the mechanism belongs here architecturally.
 */
final class LocaleSwitcher
{
    public function __construct(
        private readonly TranslatorInterface $translator,
    ) {
    }

    /**
     * @template T
     *
     * @param callable():T $callback
     *
     * @return T
     */
    public function withLocale(string $locale, callable $callback): mixed
    {
        if (!$this->translator instanceof LocaleAwareInterface) {
            return $callback();
        }

        $previous = $this->translator->getLocale();

        if ('' === $locale || $previous === $locale) {
            return $callback();
        }

        $this->translator->setLocale($locale);

        try {
            return $callback();
        } finally {
            $this->translator->setLocale($previous);
        }
    }
}
