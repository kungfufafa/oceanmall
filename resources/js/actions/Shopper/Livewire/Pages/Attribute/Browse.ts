import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition } from './../../../../../wayfinder'
/**
* @see \Shopper\Livewire\Pages\Attribute\Browse::__invoke
* @see vendor/shopper/framework/src/Livewire/Pages/Attribute/Browse.php:7
* @route '/cpanel/products/attributes'
*/
const Browse = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: Browse.url(options),
    method: 'get',
})

Browse.definition = {
    methods: ["get","head"],
    url: '/cpanel/products/attributes',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \Shopper\Livewire\Pages\Attribute\Browse::__invoke
* @see vendor/shopper/framework/src/Livewire/Pages/Attribute/Browse.php:7
* @route '/cpanel/products/attributes'
*/
Browse.url = (options?: RouteQueryOptions) => {
    return Browse.definition.url + queryParams(options)
}

/**
* @see \Shopper\Livewire\Pages\Attribute\Browse::__invoke
* @see vendor/shopper/framework/src/Livewire/Pages/Attribute/Browse.php:7
* @route '/cpanel/products/attributes'
*/
Browse.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: Browse.url(options),
    method: 'get',
})

/**
* @see \Shopper\Livewire\Pages\Attribute\Browse::__invoke
* @see vendor/shopper/framework/src/Livewire/Pages/Attribute/Browse.php:7
* @route '/cpanel/products/attributes'
*/
Browse.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: Browse.url(options),
    method: 'head',
})

/**
* @see \Shopper\Livewire\Pages\Attribute\Browse::__invoke
* @see vendor/shopper/framework/src/Livewire/Pages/Attribute/Browse.php:7
* @route '/cpanel/products/attributes'
*/
const BrowseForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: Browse.url(options),
    method: 'get',
})

/**
* @see \Shopper\Livewire\Pages\Attribute\Browse::__invoke
* @see vendor/shopper/framework/src/Livewire/Pages/Attribute/Browse.php:7
* @route '/cpanel/products/attributes'
*/
BrowseForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: Browse.url(options),
    method: 'get',
})

/**
* @see \Shopper\Livewire\Pages\Attribute\Browse::__invoke
* @see vendor/shopper/framework/src/Livewire/Pages/Attribute/Browse.php:7
* @route '/cpanel/products/attributes'
*/
BrowseForm.head = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: Browse.url({
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

Browse.form = BrowseForm

export default Browse