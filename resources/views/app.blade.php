<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}"  @class(['dark' => ($appearance ?? 'light') == 'dark'])>
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        {{-- Storefront is light-first: only apply dark when explicitly chosen --}}
        <script>
            (function() {
                const appearance = '{{ $appearance ?? "light" }}';

                if (appearance === 'dark') {
                    document.documentElement.classList.add('dark');
                } else {
                    document.documentElement.classList.remove('dark');
                }
            })();
        </script>

        <style>
            html {
                background-color: oklch(1 0 0);
                color-scheme: light;
            }

            html.dark {
                background-color: oklch(0.145 0 0);
                color-scheme: dark;
            }
        </style>

        <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('/images/logo-icon.png') }}" />
        <link rel="icon" type="image/png" sizes="16x16" href="{{ asset('/images/logo-icon.png') }}" />
        <link rel="shortcut icon" href="{{ asset('/images/logo-icon.png') }}" />
        <link rel="manifest" href="{{ asset('build/manifest.webmanifest') }}" />

        @fonts

        @vite(['resources/css/app.css', 'resources/js/app.ts', "resources/js/pages/{$page['component']}.vue"])
        <x-inertia::head>
            <title>{{ config('app.name', 'OceanMall') }}</title>
        </x-inertia::head>
    </head>
    <body class="font-sans antialiased">
        <x-inertia::app />
    </body>
</html>
