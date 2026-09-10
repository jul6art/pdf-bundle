<?php

declare(strict_types=1);

namespace Jul6Art\PdfBundle\Tests\Unit\Locale;

use Jul6Art\PdfBundle\Locale\LocaleSwitcher;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Symfony\Contracts\Translation\LocaleAwareInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

#[CoversClass(LocaleSwitcher::class)]
final class LocaleSwitcherTest extends TestCase
{
    public function testSwitchesLocaleForTheDurationOfTheCallbackAndRestoresIt(): void
    {
        $translator = new class implements TranslatorInterface, LocaleAwareInterface {
            public string $locale = 'fr';
            /** @var list<string> */
            public array $seen = [];

            public function setLocale(string $locale): void
            {
                $this->seen[] = $locale;
                $this->locale = $locale;
            }

            public function getLocale(): string
            {
                return $this->locale;
            }

            /**
             * @param array<string, mixed> $parameters
             */
            public function trans(?string $id, array $parameters = [], ?string $domain = null, ?string $locale = null): string
            {
                return (string) $id;
            }
        };

        $switcher = new LocaleSwitcher($translator);

        $seenDuringCallback = $switcher->withLocale('en', static fn (): string => $translator->getLocale());

        self::assertSame('en', $seenDuringCallback);
        self::assertSame('fr', $translator->getLocale(), 'The original locale must be restored after the callback.');
        self::assertSame(['en', 'fr'], $translator->seen);
    }

    public function testRestoresTheLocaleEvenWhenTheCallbackThrows(): void
    {
        $translator = new class implements TranslatorInterface, LocaleAwareInterface {
            public string $locale = 'fr';

            public function setLocale(string $locale): void
            {
                $this->locale = $locale;
            }

            public function getLocale(): string
            {
                return $this->locale;
            }

            /**
             * @param array<string, mixed> $parameters
             */
            public function trans(?string $id, array $parameters = [], ?string $domain = null, ?string $locale = null): string
            {
                return (string) $id;
            }
        };

        $switcher = new LocaleSwitcher($translator);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('boom');

        try {
            $switcher->withLocale('en', static function (): never {
                throw new \RuntimeException('boom');
            });
        } finally {
            self::assertSame('fr', $translator->getLocale());
        }
    }

    public function testDoesNothingWhenTheRequestedLocaleIsEmpty(): void
    {
        $translator = new class implements TranslatorInterface, LocaleAwareInterface {
            public function setLocale(string $locale): void
            {
                throw new \LogicException('setLocale must not be called for an empty locale.');
            }

            public function getLocale(): string
            {
                return 'fr';
            }

            /**
             * @param array<string, mixed> $parameters
             */
            public function trans(?string $id, array $parameters = [], ?string $domain = null, ?string $locale = null): string
            {
                return (string) $id;
            }
        };

        $switcher = new LocaleSwitcher($translator);

        self::assertSame('ok', $switcher->withLocale('', static fn (): string => 'ok'));
    }

    public function testSkipsSwitchingWhenTheTranslatorIsNotLocaleAware(): void
    {
        $translator = new class implements TranslatorInterface {
            /**
             * @param array<string, mixed> $parameters
             */
            public function trans(?string $id, array $parameters = [], ?string $domain = null, ?string $locale = null): string
            {
                return (string) $id;
            }

            public function getLocale(): string
            {
                return 'fr';
            }
        };

        $switcher = new LocaleSwitcher($translator);

        self::assertSame('ok', $switcher->withLocale('en', static fn (): string => 'ok'));
    }
}
