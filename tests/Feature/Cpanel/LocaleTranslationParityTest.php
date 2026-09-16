<?php

declare(strict_types=1);

namespace Tests\Feature\Cpanel;

use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

final class LocaleTranslationParityTest extends TestCase
{
    /**
     * App-specific keys published on top of the vendor Shopper translations
     * (they have no English counterpart inside vendor/shopper).
     *
     * @var array<string, list<string>>
     */
    private const APP_ADDED_KEY_PREFIXES = [
        'pages/settings/global.php' => ['location.rajaongkir_'],
    ];

    /**
     * @return array<string, array{0: string}>
     */
    public static function translationFilesProvider(): array
    {
        $files = [
            'errors.php',
            'forms.php',
            'layout.php',
            'modals.php',
            'notifications.php',
            'words.php',
            'pages/attributes.php',
            'pages/auth.php',
            'pages/brands.php',
            'pages/categories.php',
            'pages/collections.php',
            'pages/customers.php',
            'pages/dashboard.php',
            'pages/discounts.php',
            'pages/onboarding.php',
            'pages/orders.php',
            'pages/products.php',
            'pages/reviews.php',
            'pages/suppliers.php',
            'pages/tags.php',
            'pages/settings/carriers.php',
            'pages/settings/channels.php',
            'pages/settings/currencies.php',
            'pages/settings/global.php',
            'pages/settings/menu.php',
            'pages/settings/payments.php',
            'pages/settings/staff.php',
            'pages/settings/taxes.php',
            'pages/settings/zones.php',
        ];

        $data = [];
        foreach ($files as $file) {
            $data[$file] = [$file];
        }

        return $data;
    }

    #[DataProvider('translationFilesProvider')]
    public function test_indonesian_translation_file_matches_english_keys_and_placeholders(string $relativePath): void
    {
        $enPath = base_path("vendor/shopper/framework/resources/lang/en/{$relativePath}");
        $idPath = base_path("lang/vendor/shopper/id/{$relativePath}");

        $this->assertFileExists($enPath, "English source file missing: {$relativePath}");
        $this->assertFileExists($idPath, "Indonesian translation file missing: {$relativePath}");

        $enData = require $enPath;
        $idData = require $idPath;

        $this->assertIsArray($enData);
        $this->assertIsArray($idData);

        $enKeys = array_keys($this->arrayDotKeys($enData));
        $idKeys = $this->withoutAppAddedKeys(
            array_keys($this->arrayDotKeys($idData)),
            $relativePath,
        );

        sort($enKeys);
        sort($idKeys);

        $this->assertSame($enKeys, $idKeys, "Key mismatch in {$relativePath}");

        // Check placeholder parity
        $enPlaceholders = $this->extractPlaceholders($enData);
        $idPlaceholders = $this->extractPlaceholders($idData);
        $idPlaceholders = array_intersect_key(
            $idPlaceholders,
            array_flip($this->withoutAppAddedKeys(array_keys($idPlaceholders), $relativePath)),
        );

        $this->assertSame($enPlaceholders, $idPlaceholders, "Placeholder mismatch in {$relativePath}");
    }

    /**
     * @param  list<string>  $keys
     * @return list<string>
     */
    private function withoutAppAddedKeys(array $keys, string $relativePath): array
    {
        $prefixes = self::APP_ADDED_KEY_PREFIXES[$relativePath] ?? [];

        if ($prefixes === []) {
            return $keys;
        }

        return array_values(array_filter(
            $keys,
            static function (string $key) use ($prefixes): bool {
                foreach ($prefixes as $prefix) {
                    if (str_starts_with($key, $prefix)) {
                        return false;
                    }
                }

                return true;
            },
        ));
    }

    /**
     * @param  array<string, mixed>  $array
     * @return array<string, mixed>
     */
    private function arrayDotKeys(array $array, string $prefix = ''): array
    {
        $results = [];
        foreach ($array as $key => $value) {
            $fullKey = $prefix !== '' ? "{$prefix}.{$key}" : (string) $key;
            if (is_array($value)) {
                $results = array_merge($results, $this->arrayDotKeys($value, $fullKey));
            } else {
                $results[$fullKey] = $value;
            }
        }

        return $results;
    }

    /**
     * @param  array<string, mixed>  $array
     * @return array<string, list<string>>
     */
    private function extractPlaceholders(array $array, string $prefix = ''): array
    {
        $placeholders = [];
        foreach ($array as $key => $value) {
            $fullKey = $prefix !== '' ? "{$prefix}.{$key}" : (string) $key;
            if (is_array($value)) {
                $placeholders = array_merge($placeholders, $this->extractPlaceholders($value, $fullKey));
            } elseif (is_string($value)) {
                preg_match_all('/:([a-zA-Z_]+)/', $value, $matches);
                $tokens = $matches[1] ?? [];
                sort($tokens);
                $placeholders[$fullKey] = array_values(array_unique($tokens));
            }
        }

        ksort($placeholders);

        return $placeholders;
    }
}
