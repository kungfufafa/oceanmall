import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition } from './../../../wayfinder'
/**
* @see \Shopper\Livewire\Pages\Brand\Index::__invoke
* @see vendor/shopper/framework/src/Livewire/Pages/Brand/Index.php:7
* @route '/cpanel/brands'
*/
export const index = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
})

index.definition = {
    methods: ["get","head"],
    url: '/cpanel/brands',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \Shopper\Livewire\Pages\Brand\Index::__invoke
* @see vendor/shopper/framework/src/Livewire/Pages/Brand/Index.php:7
* @route '/cpanel/brands'
*/
index.url = (options?: RouteQueryOptions) => {
    return index.definition.url + queryParams(options)
}

/**
* @see \Shopper\Livewire\Pages\Brand\Index::__invoke
* @see vendor/shopper/framework/src/Livewire/Pages/Brand/Index.php:7
* @route '/cpanel/brands'
*/
index.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
})

/**
* @see \Shopper\Livewire\Pages\Brand\Index::__invoke
* @see vendor/shopper/framework/src/Livewire/Pages/Brand/Index.php:7
* @route '/cpanel/brands'
*/
index.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: index.url(options),
    method: 'head',
})

/**
* @see \Shopper\Livewire\Pages\Brand\Index::__invoke
* @see vendor/shopper/framework/src/Livewire/Pages/Brand/Index.php:7
* @route '/cpanel/brands'
*/
const indexForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: index.url(options),
    method: 'get',
})

/**
* @see \Shopper\Livewire\Pages\Brand\Index::__invoke
* @see vendor/shopper/framework/src/Livewire/Pages/Brand/Index.php:7
* @route '/cpanel/brands'
*/
indexForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: index.url(options),
    method: 'get',
})

/**
* @see \Shopper\Livewire\Pages\Brand\Index::__invoke
* @see vendor/shopper/framework/src/Livewire/Pages/Brand/Index.php:7
* @route '/cpanel/brands'
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

const brands = {
    index: Object.assign(index, index),
}

export default brands