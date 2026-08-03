import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition } from './../../../../../wayfinder'
/**
* @see \Shopper\Livewire\Pages\Settings\LegalPage::__invoke
* @see vendor/shopper/framework/src/Livewire/Pages/Settings/LegalPage.php:7
* @route '/cpanel/setting/legal'
*/
const LegalPage = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: LegalPage.url(options),
    method: 'get',
})

LegalPage.definition = {
    methods: ["get","head"],
    url: '/cpanel/setting/legal',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \Shopper\Livewire\Pages\Settings\LegalPage::__invoke
* @see vendor/shopper/framework/src/Livewire/Pages/Settings/LegalPage.php:7
* @route '/cpanel/setting/legal'
*/
LegalPage.url = (options?: RouteQueryOptions) => {
    return LegalPage.definition.url + queryParams(options)
}

/**
* @see \Shopper\Livewire\Pages\Settings\LegalPage::__invoke
* @see vendor/shopper/framework/src/Livewire/Pages/Settings/LegalPage.php:7
* @route '/cpanel/setting/legal'
*/
LegalPage.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: LegalPage.url(options),
    method: 'get',
})

/**
* @see \Shopper\Livewire\Pages\Settings\LegalPage::__invoke
* @see vendor/shopper/framework/src/Livewire/Pages/Settings/LegalPage.php:7
* @route '/cpanel/setting/legal'
*/
LegalPage.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: LegalPage.url(options),
    method: 'head',
})

/**
* @see \Shopper\Livewire\Pages\Settings\LegalPage::__invoke
* @see vendor/shopper/framework/src/Livewire/Pages/Settings/LegalPage.php:7
* @route '/cpanel/setting/legal'
*/
const LegalPageForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: LegalPage.url(options),
    method: 'get',
})

/**
* @see \Shopper\Livewire\Pages\Settings\LegalPage::__invoke
* @see vendor/shopper/framework/src/Livewire/Pages/Settings/LegalPage.php:7
* @route '/cpanel/setting/legal'
*/
LegalPageForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: LegalPage.url(options),
    method: 'get',
})

/**
* @see \Shopper\Livewire\Pages\Settings\LegalPage::__invoke
* @see vendor/shopper/framework/src/Livewire/Pages/Settings/LegalPage.php:7
* @route '/cpanel/setting/legal'
*/
LegalPageForm.head = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: LegalPage.url({
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

LegalPage.form = LegalPageForm

export default LegalPage