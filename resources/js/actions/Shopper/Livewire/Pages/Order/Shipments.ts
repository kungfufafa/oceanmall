import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition } from './../../../../../wayfinder'
/**
* @see \Shopper\Livewire\Pages\Order\Shipments::__invoke
* @see vendor/shopper/framework/src/Livewire/Pages/Order/Shipments.php:7
* @route '/cpanel/orders/shipments'
*/
const Shipments = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: Shipments.url(options),
    method: 'get',
})

Shipments.definition = {
    methods: ["get","head"],
    url: '/cpanel/orders/shipments',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \Shopper\Livewire\Pages\Order\Shipments::__invoke
* @see vendor/shopper/framework/src/Livewire/Pages/Order/Shipments.php:7
* @route '/cpanel/orders/shipments'
*/
Shipments.url = (options?: RouteQueryOptions) => {
    return Shipments.definition.url + queryParams(options)
}

/**
* @see \Shopper\Livewire\Pages\Order\Shipments::__invoke
* @see vendor/shopper/framework/src/Livewire/Pages/Order/Shipments.php:7
* @route '/cpanel/orders/shipments'
*/
Shipments.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: Shipments.url(options),
    method: 'get',
})

/**
* @see \Shopper\Livewire\Pages\Order\Shipments::__invoke
* @see vendor/shopper/framework/src/Livewire/Pages/Order/Shipments.php:7
* @route '/cpanel/orders/shipments'
*/
Shipments.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: Shipments.url(options),
    method: 'head',
})

/**
* @see \Shopper\Livewire\Pages\Order\Shipments::__invoke
* @see vendor/shopper/framework/src/Livewire/Pages/Order/Shipments.php:7
* @route '/cpanel/orders/shipments'
*/
const ShipmentsForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: Shipments.url(options),
    method: 'get',
})

/**
* @see \Shopper\Livewire\Pages\Order\Shipments::__invoke
* @see vendor/shopper/framework/src/Livewire/Pages/Order/Shipments.php:7
* @route '/cpanel/orders/shipments'
*/
ShipmentsForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: Shipments.url(options),
    method: 'get',
})

/**
* @see \Shopper\Livewire\Pages\Order\Shipments::__invoke
* @see vendor/shopper/framework/src/Livewire/Pages/Order/Shipments.php:7
* @route '/cpanel/orders/shipments'
*/
ShipmentsForm.head = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: Shipments.url({
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

Shipments.form = ShipmentsForm

export default Shipments