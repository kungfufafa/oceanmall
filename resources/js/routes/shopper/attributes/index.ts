import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition } from './../../../wayfinder'
/**
* @see \Shopper\Livewire\Pages\Attribute\Browse::__invoke
* @see vendor/shopper/framework/src/Livewire/Pages/Attribute/Browse.php:7
* @route '/cpanel/products/attributes'
*/
export const index = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
})

index.definition = {
    methods: ["get","head"],
    url: '/cpanel/products/attributes',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \Shopper\Livewire\Pages\Attribute\Browse::__invoke
* @see vendor/shopper/framework/src/Livewire/Pages/Attribute/Browse.php:7
* @route '/cpanel/products/attributes'
*/
index.url = (options?: RouteQueryOptions) => {
    return index.definition.url + queryParams(options)
}

/**
* @see \Shopper\Livewire\Pages\Attribute\Browse::__invoke
* @see vendor/shopper/framework/src/Livewire/Pages/Attribute/Browse.php:7
* @route '/cpanel/products/attributes'
*/
index.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
})

/**
* @see \Shopper\Livewire\Pages\Attribute\Browse::__invoke
* @see vendor/shopper/framework/src/Livewire/Pages/Attribute/Browse.php:7
* @route '/cpanel/products/attributes'
*/
index.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: index.url(options),
    method: 'head',
})

/**
* @see \Shopper\Livewire\Pages\Attribute\Browse::__invoke
* @see vendor/shopper/framework/src/Livewire/Pages/Attribute/Browse.php:7
* @route '/cpanel/products/attributes'
*/
const indexForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: index.url(options),
    method: 'get',
})

/**
* @see \Shopper\Livewire\Pages\Attribute\Browse::__invoke
* @see vendor/shopper/framework/src/Livewire/Pages/Attribute/Browse.php:7
* @route '/cpanel/products/attributes'
*/
indexForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: index.url(options),
    method: 'get',
})

/**
* @see \Shopper\Livewire\Pages\Attribute\Browse::__invoke
* @see vendor/shopper/framework/src/Livewire/Pages/Attribute/Browse.php:7
* @route '/cpanel/products/attributes'
*/
indexForm.head = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: index.url({
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

index.form = indexForm

const attributes = {
    index: Object.assign(index, index),
}

export default attributes