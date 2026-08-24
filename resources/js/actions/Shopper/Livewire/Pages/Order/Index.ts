import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition } from './../../../../../wayfinder'
/**
* @see \Shopper\Livewire\Pages\Order\Index::__invoke
* @see vendor/shopper/framework/src/Livewire/Pages/Order/Index.php:7
* @route '/cpanel/orders'
*/
const Index = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: Index.url(options),
    method: 'get',
})

Index.definition = {
    methods: ["get","head"],
    url: '/cpanel/orders',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \Shopper\Livewire\Pages\Order\Index::__invoke
* @see vendor/shopper/framework/src/Livewire/Pages/Order/Index.php:7
* @route '/cpanel/orders'
*/
Index.url = (options?: RouteQueryOptions) => {
    return Index.definition.url + queryParams(options)
}

/**
* @see \Shopper\Livewire\Pages\Order\Index::__invoke
* @see vendor/shopper/framework/src/Livewire/Pages/Order/Index.php:7
* @route '/cpanel/orders'
*/
Index.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: Index.url(options),
    method: 'get',
})

/**
* @see \Shopper\Livewire\Pages\Order\Index::__invoke
* @see vendor/shopper/framework/src/Livewire/Pages/Order/Index.php:7
* @route '/cpanel/orders'
*/
Index.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: Index.url(options),
    method: 'head',
})

/**
* @see \Shopper\Livewire\Pages\Order\Index::__invoke
* @see vendor/shopper/framework/src/Livewire/Pages/Order/Index.php:7
* @route '/cpanel/orders'
*/
const IndexForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: Index.url(options),
    method: 'get',
})

/**
* @see \Shopper\Livewire\Pages\Order\Index::__invoke
* @see vendor/shopper/framework/src/Livewire/Pages/Order/Index.php:7
* @route '/cpanel/orders'
*/
IndexForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: Index.url(options),
    method: 'get',
})

/**
* @see \Shopper\Livewire\Pages\Order\Index::__invoke
* @see vendor/shopper/framework/src/Livewire/Pages/Order/Index.php:7
* @route '/cpanel/orders'
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