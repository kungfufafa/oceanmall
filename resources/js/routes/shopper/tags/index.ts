import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition } from './../../../wayfinder'
/**
* @see \Shopper\Livewire\Pages\Tag\Index::__invoke
* @see vendor/shopper/framework/src/Livewire/Pages/Tag/Index.php:7
* @route '/cpanel/products/tags'
*/
export const index = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
})

index.definition = {
    methods: ["get","head"],
    url: '/cpanel/products/tags',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \Shopper\Livewire\Pages\Tag\Index::__invoke
* @see vendor/shopper/framework/src/Livewire/Pages/Tag/Index.php:7
* @route '/cpanel/products/tags'
*/
index.url = (options?: RouteQueryOptions) => {
    return index.definition.url + queryParams(options)
}

/**
* @see \Shopper\Livewire\Pages\Tag\Index::__invoke
* @see vendor/shopper/framework/src/Livewire/Pages/Tag/Index.php:7
* @route '/cpanel/products/tags'
*/
index.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
})

/**
* @see \Shopper\Livewire\Pages\Tag\Index::__invoke
* @see vendor/shopper/framework/src/Livewire/Pages/Tag/Index.php:7
* @route '/cpanel/products/tags'
*/
index.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: index.url(options),
    method: 'head',
})

/**
* @see \Shopper\Livewire\Pages\Tag\Index::__invoke
* @see vendor/shopper/framework/src/Livewire/Pages/Tag/Index.php:7
* @route '/cpanel/products/tags'
*/
const indexForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: index.url(options),
    method: 'get',
})

/**
* @see \Shopper\Livewire\Pages\Tag\Index::__invoke
* @see vendor/shopper/framework/src/Livewire/Pages/Tag/Index.php:7
* @route '/cpanel/products/tags'
*/
indexForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: index.url(options),
    method: 'get',
})

/**
* @see \Shopper\Livewire\Pages\Tag\Index::__invoke
* @see vendor/shopper/framework/src/Livewire/Pages/Tag/Index.php:7
* @route '/cpanel/products/tags'
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

const tags = {
    index: Object.assign(index, index),
}

export default tags