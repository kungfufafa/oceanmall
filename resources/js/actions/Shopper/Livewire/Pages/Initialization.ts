import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition } from './../../../../wayfinder'
/**
* @see \Shopper\Livewire\Pages\Initialization::__invoke
* @see vendor/shopper/framework/src/Livewire/Pages/Initialization.php:7
* @route '/cpanel/initialize'
*/
const Initialization = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: Initialization.url(options),
    method: 'get',
})

Initialization.definition = {
    methods: ["get","head"],
    url: '/cpanel/initialize',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \Shopper\Livewire\Pages\Initialization::__invoke
* @see vendor/shopper/framework/src/Livewire/Pages/Initialization.php:7
* @route '/cpanel/initialize'
*/
Initialization.url = (options?: RouteQueryOptions) => {
    return Initialization.definition.url + queryParams(options)
}

/**
* @see \Shopper\Livewire\Pages\Initialization::__invoke
* @see vendor/shopper/framework/src/Livewire/Pages/Initialization.php:7
* @route '/cpanel/initialize'
*/
Initialization.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: Initialization.url(options),
    method: 'get',
})

/**
* @see \Shopper\Livewire\Pages\Initialization::__invoke
* @see vendor/shopper/framework/src/Livewire/Pages/Initialization.php:7
* @route '/cpanel/initialize'
*/
Initialization.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: Initialization.url(options),
    method: 'head',
})

/**
* @see \Shopper\Livewire\Pages\Initialization::__invoke
* @see vendor/shopper/framework/src/Livewire/Pages/Initialization.php:7
* @route '/cpanel/initialize'
*/
const InitializationForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: Initialization.url(options),
    method: 'get',
})

/**
* @see \Shopper\Livewire\Pages\Initialization::__invoke
* @see vendor/shopper/framework/src/Livewire/Pages/Initialization.php:7
* @route '/cpanel/initialize'
*/
InitializationForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: Initialization.url(options),
    method: 'get',
})

/**
* @see \Shopper\Livewire\Pages\Initialization::__invoke
* @see vendor/shopper/framework/src/Livewire/Pages/Initialization.php:7
* @route '/cpanel/initialize'
*/
InitializationForm.head = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: Initialization.url({
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

Initialization.form = InitializationForm

export default Initialization