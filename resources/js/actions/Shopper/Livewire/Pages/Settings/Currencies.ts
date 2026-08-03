import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition } from './../../../../../wayfinder'
/**
* @see \Shopper\Livewire\Pages\Settings\Currencies::__invoke
* @see vendor/shopper/framework/src/Livewire/Pages/Settings/Currencies.php:7
* @route '/cpanel/setting/currencies'
*/
const Currencies = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: Currencies.url(options),
    method: 'get',
})

Currencies.definition = {
    methods: ["get","head"],
    url: '/cpanel/setting/currencies',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \Shopper\Livewire\Pages\Settings\Currencies::__invoke
* @see vendor/shopper/framework/src/Livewire/Pages/Settings/Currencies.php:7
* @route '/cpanel/setting/currencies'
*/
Currencies.url = (options?: RouteQueryOptions) => {
    return Currencies.definition.url + queryParams(options)
}

/**
* @see \Shopper\Livewire\Pages\Settings\Currencies::__invoke
* @see vendor/shopper/framework/src/Livewire/Pages/Settings/Currencies.php:7
* @route '/cpanel/setting/currencies'
*/
Currencies.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: Currencies.url(options),
    method: 'get',
})

/**
* @see \Shopper\Livewire\Pages\Settings\Currencies::__invoke
* @see vendor/shopper/framework/src/Livewire/Pages/Settings/Currencies.php:7
* @route '/cpanel/setting/currencies'
*/
Currencies.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: Currencies.url(options),
    method: 'head',
})

/**
* @see \Shopper\Livewire\Pages\Settings\Currencies::__invoke
* @see vendor/shopper/framework/src/Livewire/Pages/Settings/Currencies.php:7
* @route '/cpanel/setting/currencies'
*/
const CurrenciesForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: Currencies.url(options),
    method: 'get',
})

/**
* @see \Shopper\Livewire\Pages\Settings\Currencies::__invoke
* @see vendor/shopper/framework/src/Livewire/Pages/Settings/Currencies.php:7
* @route '/cpanel/setting/currencies'
*/
CurrenciesForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: Currencies.url(options),
    method: 'get',
})

/**
* @see \Shopper\Livewire\Pages\Settings\Currencies::__invoke
* @see vendor/shopper/framework/src/Livewire/Pages/Settings/Currencies.php:7
* @route '/cpanel/setting/currencies'
*/
CurrenciesForm.head = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: Currencies.url({
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

Currencies.form = CurrenciesForm

export default Currencies