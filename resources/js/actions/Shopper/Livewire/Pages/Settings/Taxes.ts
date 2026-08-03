import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition } from './../../../../../wayfinder'
/**
* @see \Shopper\Livewire\Pages\Settings\Taxes::__invoke
* @see vendor/shopper/framework/src/Livewire/Pages/Settings/Taxes.php:7
* @route '/cpanel/setting/taxes'
*/
const Taxes = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: Taxes.url(options),
    method: 'get',
})

Taxes.definition = {
    methods: ["get","head"],
    url: '/cpanel/setting/taxes',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \Shopper\Livewire\Pages\Settings\Taxes::__invoke
* @see vendor/shopper/framework/src/Livewire/Pages/Settings/Taxes.php:7
* @route '/cpanel/setting/taxes'
*/
Taxes.url = (options?: RouteQueryOptions) => {
    return Taxes.definition.url + queryParams(options)
}

/**
* @see \Shopper\Livewire\Pages\Settings\Taxes::__invoke
* @see vendor/shopper/framework/src/Livewire/Pages/Settings/Taxes.php:7
* @route '/cpanel/setting/taxes'
*/
Taxes.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: Taxes.url(options),
    method: 'get',
})

/**
* @see \Shopper\Livewire\Pages\Settings\Taxes::__invoke
* @see vendor/shopper/framework/src/Livewire/Pages/Settings/Taxes.php:7
* @route '/cpanel/setting/taxes'
*/
Taxes.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: Taxes.url(options),
    method: 'head',
})

/**
* @see \Shopper\Livewire\Pages\Settings\Taxes::__invoke
* @see vendor/shopper/framework/src/Livewire/Pages/Settings/Taxes.php:7
* @route '/cpanel/setting/taxes'
*/
const TaxesForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: Taxes.url(options),
    method: 'get',
})

/**
* @see \Shopper\Livewire\Pages\Settings\Taxes::__invoke
* @see vendor/shopper/framework/src/Livewire/Pages/Settings/Taxes.php:7
* @route '/cpanel/setting/taxes'
*/
TaxesForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: Taxes.url(options),
    method: 'get',
})

/**
* @see \Shopper\Livewire\Pages\Settings\Taxes::__invoke
* @see vendor/shopper/framework/src/Livewire/Pages/Settings/Taxes.php:7
* @route '/cpanel/setting/taxes'
*/
TaxesForm.head = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: Taxes.url({
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

Taxes.form = TaxesForm

export default Taxes