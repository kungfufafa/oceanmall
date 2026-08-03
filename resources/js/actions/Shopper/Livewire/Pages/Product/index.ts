import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition } from './../../../../../wayfinder'
/**
* @see \Shopper\Livewire\Pages\Product\Index::__invoke
* @see vendor/shopper/framework/src/Livewire/Pages/Product/Index.php:7
* @route '/cpanel/products'
*/
const Index = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: Index.url(options),
    method: 'get',
})

Index.definition = {
    methods: ["get","head"],
    url: '/cpanel/products',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \Shopper\Livewire\Pages\Product\Index::__invoke
* @see vendor/shopper/framework/src/Livewire/Pages/Product/Index.php:7
* @route '/cpanel/products'
*/
Index.url = (options?: RouteQueryOptions) => {
    return Index.definition.url + queryParams(options)
}

/**
* @see \Shopper\Livewire\Pages\Product\Index::__invoke
* @see vendor/shopper/framework/src/Livewire/Pages/Product/Index.php:7
* @route '/cpanel/products'
*/
Index.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: Index.url(options),
    method: 'get',
})

/**
* @see \Shopper\Livewire\Pages\Product\Index::__invoke
* @see vendor/shopper/framework/src/Livewire/Pages/Product/Index.php:7
* @route '/cpanel/products'
*/
Index.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: Index.url(options),
    method: 'head',
})

/**
* @see \Shopper\Livewire\Pages\Product\Index::__invoke
* @see vendor/shopper/framework/src/Livewire/Pages/Product/Index.php:7
* @route '/cpanel/products'
*/
const IndexForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: Index.url(options),
    method: 'get',
})

/**
* @see \Shopper\Livewire\Pages\Product\Index::__invoke
* @see vendor/shopper/framework/src/Livewire/Pages/Product/Index.php:7
* @route '/cpanel/products'
*/
IndexForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: Index.url(options),
    method: 'get',
})

/**
* @see \Shopper\Livewire\Pages\Product\Index::__invoke
* @see vendor/shopper/framework/src/Livewire/Pages/Product/Index.php:7
* @route '/cpanel/products'
*/
IndexForm.head = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: Index.url({
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

Index.form = IndexForm

export default Index