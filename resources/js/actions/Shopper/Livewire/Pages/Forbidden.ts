import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition } from './../../../../wayfinder'
/**
* @see \Shopper\Livewire\Pages\Forbidden::__invoke
* @see vendor/shopper/framework/src/Livewire/Pages/Forbidden.php:7
* @route '/cpanel/forbidden'
*/
const Forbidden = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: Forbidden.url(options),
    method: 'get',
})

Forbidden.definition = {
    methods: ["get","head"],
    url: '/cpanel/forbidden',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \Shopper\Livewire\Pages\Forbidden::__invoke
* @see vendor/shopper/framework/src/Livewire/Pages/Forbidden.php:7
* @route '/cpanel/forbidden'
*/
Forbidden.url = (options?: RouteQueryOptions) => {
    return Forbidden.definition.url + queryParams(options)
}

/**
* @see \Shopper\Livewire\Pages\Forbidden::__invoke
* @see vendor/shopper/framework/src/Livewire/Pages/Forbidden.php:7
* @route '/cpanel/forbidden'
*/
Forbidden.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: Forbidden.url(options),
    method: 'get',
})

/**
* @see \Shopper\Livewire\Pages\Forbidden::__invoke
* @see vendor/shopper/framework/src/Livewire/Pages/Forbidden.php:7
* @route '/cpanel/forbidden'
*/
Forbidden.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: Forbidden.url(options),
    method: 'head',
})

/**
* @see \Shopper\Livewire\Pages\Forbidden::__invoke
* @see vendor/shopper/framework/src/Livewire/Pages/Forbidden.php:7
* @route '/cpanel/forbidden'
*/
const ForbiddenForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: Forbidden.url(options),
    method: 'get',
})

/**
* @see \Shopper\Livewire\Pages\Forbidden::__invoke
* @see vendor/shopper/framework/src/Livewire/Pages/Forbidden.php:7
* @route '/cpanel/forbidden'
*/
ForbiddenForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: Forbidden.url(options),
    method: 'get',
})

/**
* @see \Shopper\Livewire\Pages\Forbidden::__invoke
* @see vendor/shopper/framework/src/Livewire/Pages/Forbidden.php:7
* @route '/cpanel/forbidden'
*/
ForbiddenForm.head = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: Forbidden.url({
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

Forbidden.form = ForbiddenForm

export default Forbidden