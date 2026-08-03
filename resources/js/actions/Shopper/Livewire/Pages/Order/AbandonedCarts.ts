import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition } from './../../../../../wayfinder'
/**
* @see \Shopper\Livewire\Pages\Order\AbandonedCarts::__invoke
* @see vendor/shopper/framework/src/Livewire/Pages/Order/AbandonedCarts.php:7
* @route '/cpanel/orders/abandoned-carts'
*/
const AbandonedCarts = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: AbandonedCarts.url(options),
    method: 'get',
})

AbandonedCarts.definition = {
    methods: ["get","head"],
    url: '/cpanel/orders/abandoned-carts',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \Shopper\Livewire\Pages\Order\AbandonedCarts::__invoke
* @see vendor/shopper/framework/src/Livewire/Pages/Order/AbandonedCarts.php:7
* @route '/cpanel/orders/abandoned-carts'
*/
AbandonedCarts.url = (options?: RouteQueryOptions) => {
    return AbandonedCarts.definition.url + queryParams(options)
}

/**
* @see \Shopper\Livewire\Pages\Order\AbandonedCarts::__invoke
* @see vendor/shopper/framework/src/Livewire/Pages/Order/AbandonedCarts.php:7
* @route '/cpanel/orders/abandoned-carts'
*/
AbandonedCarts.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: AbandonedCarts.url(options),
    method: 'get',
})

/**
* @see \Shopper\Livewire\Pages\Order\AbandonedCarts::__invoke
* @see vendor/shopper/framework/src/Livewire/Pages/Order/AbandonedCarts.php:7
* @route '/cpanel/orders/abandoned-carts'
*/
AbandonedCarts.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: AbandonedCarts.url(options),
    method: 'head',
})

/**
* @see \Shopper\Livewire\Pages\Order\AbandonedCarts::__invoke
* @see vendor/shopper/framework/src/Livewire/Pages/Order/AbandonedCarts.php:7
* @route '/cpanel/orders/abandoned-carts'
*/
const AbandonedCartsForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: AbandonedCarts.url(options),
    method: 'get',
})

/**
* @see \Shopper\Livewire\Pages\Order\AbandonedCarts::__invoke
* @see vendor/shopper/framework/src/Livewire/Pages/Order/AbandonedCarts.php:7
* @route '/cpanel/orders/abandoned-carts'
*/
AbandonedCartsForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: AbandonedCarts.url(options),
    method: 'get',
})

/**
* @see \Shopper\Livewire\Pages\Order\AbandonedCarts::__invoke
* @see vendor/shopper/framework/src/Livewire/Pages/Order/AbandonedCarts.php:7
* @route '/cpanel/orders/abandoned-carts'
*/
AbandonedCartsForm.head = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: AbandonedCarts.url({
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

AbandonedCarts.form = AbandonedCartsForm

export default AbandonedCarts