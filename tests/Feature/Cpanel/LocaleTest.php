<?php

declare(strict_types=1);

namespace Tests\Feature\Cpanel;

use Livewire\Livewire;

use Shopper\Livewire\Components\LocaleSwitcher;
use Tests\TestCase;

final class LocaleTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        app('translator')->addNamespace('shopper', base_path('vendor/shopper/framework/resources/lang'));
    }

    public function test_shopper_admin_locales_config_contains_en_and_id_and_not_fr(): void
    {
        $locales = config('shopper.admin.locales');

        $this->assertIsArray($locales);
        $this->assertArrayHasKey('en', $locales);
        $this->assertArrayHasKey('id', $locales);
        $this->assertArrayNotHasKey('fr', $locales);
        $this->assertSame('English', $locales['en']['label']);
        $this->assertSame('Bahasa Indonesia', $locales['id']['label']);
        $this->assertSame('id', $locales['id']['flag']);
    }

    public function test_shopper_words_translate_to_indonesian_by_default(): void
    {
        $translated = __('shopper::pages/dashboard.menu', [], 'id');
        $this->assertSame('Dasbor', $translated);

        $translatedProduct = __('shopper::words.product', [], 'id');
        $this->assertSame('Produk', $translatedProduct);
    }

    public function test_locale_switcher_changes_session_locale_to_english(): void
    {
        Livewire::test(LocaleSwitcher::class)
            ->call('switchLocale', 'en')
            ->assertSessionHas('shopper_locale', 'en');
    }

    public function test_locale_switcher_rejects_unregistered_locale_fr(): void
    {
        Livewire::test(LocaleSwitcher::class)
            ->call('switchLocale', 'fr')
            ->assertSessionMissing('shopper_locale');
    }
}
