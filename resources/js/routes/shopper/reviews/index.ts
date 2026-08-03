import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition } from './../../../wayfinder'
/**
* @see \Shopper\Livewire\Pages\Reviews\Index::__invoke
* @see vendor/shopper/framework/src/Livewire/Pages/Reviews/Index.php:7
* @route '/cpanel/reviews'
*/
export const index = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
})

index.definition = {
    methods: ["get","head"],
    url: '/cpanel/reviews',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \Shopper\Livewire\Pages\Reviews\Index::__invoke
* @see vendor/shopper/framework/src/Livewire/Pages/Reviews/Index.php:7
* @route '/cpanel/reviews'
*/
index.url = (options?: RouteQueryOptions) => {
    return index.definition.url + queryParams(options)
}

/**
* @see \Shopper\Livewire\Pages\Reviews\Index::__invoke
* @see vendor/shopper/framework/src/Livewire/Pages/Reviews/Index.php:7
* @route '/cpanel/reviews'
*/
index.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
})

/**
* @see \Shopper\Livewire\Pages\Reviews\Index::__invoke
* @see vendor/shopper/framework/src/Livewire/Pages/Reviews/Index.php:7
* @route '/cpanel/reviews'
*/
index.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: index.url(options),
    method: 'head',
})

/**
* @see \Shopper\Livewire\Pages\Reviews\Index::__invoke
* @see vendor/shopper/framework/src/Livewire/Pages/Reviews/Index.php:7
* @route '/cpanel/reviews'
*/
const indexForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: index.url(options),
    method: 'get',
})

/**
* @see \Shopper\Livewire\Pages\Reviews\Index::__invoke
* @see vendor/shopper/framework/src/Livewire/Pages/Reviews/Index.php:7
* @route '/cpanel/reviews'
*/
indexForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: index.url(options),
    method: 'get',
})

/**
* @see \Shopper\Livewire\Pages\Reviews\Index::__invoke
* @see vendor/shopper/framework/src/Livewire/Pages/Reviews/Index.php:7
* @route '/cpanel/reviews'
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

const reviews = {
    index: Object.assign(index, index),
}

export default reviews