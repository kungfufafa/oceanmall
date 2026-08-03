import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition } from './../../../../../wayfinder'
/**
* @see \Shopper\Livewire\Pages\Settings\Zones::__invoke
* @see vendor/shopper/framework/src/Livewire/Pages/Settings/Zones.php:7
* @route '/cpanel/setting/zones'
*/
const Zones = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: Zones.url(options),
    method: 'get',
})

Zones.definition = {
    methods: ["get","head"],
    url: '/cpanel/setting/zones',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \Shopper\Livewire\Pages\Settings\Zones::__invoke
* @see vendor/shopper/framework/src/Livewire/Pages/Settings/Zones.php:7
* @route '/cpanel/setting/zones'
*/
Zones.url = (options?: RouteQueryOptions) => {
    return Zones.definition.url + queryParams(options)
}

/**
* @see \Shopper\Livewire\Pages\Settings\Zones::__invoke
* @see vendor/shopper/framework/src/Livewire/Pages/Settings/Zones.php:7
* @route '/cpanel/setting/zones'
*/
Zones.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: Zones.url(options),
    method: 'get',
})

/**
* @see \Shopper\Livewire\Pages\Settings\Zones::__invoke
* @see vendor/shopper/framework/src/Livewire/Pages/Settings/Zones.php:7
* @route '/cpanel/setting/zones'
*/
Zones.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: Zones.url(options),
    method: 'head',
})

/**
* @see \Shopper\Livewire\Pages\Settings\Zones::__invoke
* @see vendor/shopper/framework/src/Livewire/Pages/Settings/Zones.php:7
* @route '/cpanel/setting/zones'
*/
const ZonesForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: Zones.url(options),
    method: 'get',
})

/**
* @see \Shopper\Livewire\Pages\Settings\Zones::__invoke
* @see vendor/shopper/framework/src/Livewire/Pages/Settings/Zones.php:7
* @route '/cpanel/setting/zones'
*/
ZonesForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: Zones.url(options),
    method: 'get',
})

/**
* @see \Shopper\Livewire\Pages\Settings\Zones::__invoke
* @see vendor/shopper/framework/src/Livewire/Pages/Settings/Zones.php:7
* @route '/cpanel/setting/zones'
*/
ZonesForm.head = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: Zones.url({
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

Zones.form = ZonesForm

export default Zones