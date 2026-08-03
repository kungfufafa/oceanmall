import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition } from './../../../../../wayfinder'
/**
* @see \Shopper\Livewire\Pages\Settings\General::__invoke
* @see vendor/shopper/framework/src/Livewire/Pages/Settings/General.php:7
* @route '/cpanel/setting/general'
*/
const General = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: General.url(options),
    method: 'get',
})

General.definition = {
    methods: ["get","head"],
    url: '/cpanel/setting/general',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \Shopper\Livewire\Pages\Settings\General::__invoke
* @see vendor/shopper/framework/src/Livewire/Pages/Settings/General.php:7
* @route '/cpanel/setting/general'
*/
General.url = (options?: RouteQueryOptions) => {
    return General.definition.url + queryParams(options)
}

/**
* @see \Shopper\Livewire\Pages\Settings\General::__invoke
* @see vendor/shopper/framework/src/Livewire/Pages/Settings/General.php:7
* @route '/cpanel/setting/general'
*/
General.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: General.url(options),
    method: 'get',
})

/**
* @see \Shopper\Livewire\Pages\Settings\General::__invoke
* @see vendor/shopper/framework/src/Livewire/Pages/Settings/General.php:7
* @route '/cpanel/setting/general'
*/
General.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: General.url(options),
    method: 'head',
})

/**
* @see \Shopper\Livewire\Pages\Settings\General::__invoke
* @see vendor/shopper/framework/src/Livewire/Pages/Settings/General.php:7
* @route '/cpanel/setting/general'
*/
const GeneralForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: General.url(options),
    method: 'get',
})

/**
* @see \Shopper\Livewire\Pages\Settings\General::__invoke
* @see vendor/shopper/framework/src/Livewire/Pages/Settings/General.php:7
* @route '/cpanel/setting/general'
*/
GeneralForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: General.url(options),
    method: 'get',
})

/**
* @see \Shopper\Livewire\Pages\Settings\General::__invoke
* @see vendor/shopper/framework/src/Livewire/Pages/Settings/General.php:7
* @route '/cpanel/setting/general'
*/
GeneralForm.head = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: General.url({
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

General.form = GeneralForm

export default General