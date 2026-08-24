import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition } from './../../../../../wayfinder'
/**
* @see \App\Livewire\Shopper\Pages\OrderShipments::__invoke
* @see app/Livewire/Shopper/Pages/OrderShipments.php:7
* @route '/cpanel/orders/shipments'
*/
const OrderShipments = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: OrderShipments.url(options),
    method: 'get',
})

OrderShipments.definition = {
    methods: ["get","head"],
    url: '/cpanel/orders/shipments',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Livewire\Shopper\Pages\OrderShipments::__invoke
* @see app/Livewire/Shopper/Pages/OrderShipments.php:7
* @route '/cpanel/orders/shipments'
*/
OrderShipments.url = (options?: RouteQueryOptions) => {
    return OrderShipments.definition.url + queryParams(options)
}

/**
* @see \App\Livewire\Shopper\Pages\OrderShipments::__invoke
* @see app/Livewire/Shopper/Pages/OrderShipments.php:7
* @route '/cpanel/orders/shipments'
*/
OrderShipments.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: OrderShipments.url(options),
    method: 'get',
})

/**
* @see \App\Livewire\Shopper\Pages\OrderShipments::__invoke
* @see app/Livewire/Shopper/Pages/OrderShipments.php:7
* @route '/cpanel/orders/shipments'
*/
OrderShipments.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: OrderShipments.url(options),
    method: 'head',
})

/**
* @see \App\Livewire\Shopper\Pages\OrderShipments::__invoke
* @see app/Livewire/Shopper/Pages/OrderShipments.php:7
* @route '/cpanel/orders/shipments'
*/
const OrderShipmentsForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: OrderShipments.url(options),
    method: 'get',
})

/**
* @see \App\Livewire\Shopper\Pages\OrderShipments::__invoke
* @see app/Livewire/Shopper/Pages/OrderShipments.php:7
* @route '/cpanel/orders/shipments'
*/
OrderShipmentsForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: OrderShipments.url(options),
    method: 'get',
})

/**
* @see \App\Livewire\Shopper\Pages\OrderShipments::__invoke
* @see app/Livewire/Shopper/Pages/OrderShipments.php:7
* @route '/cpanel/orders/shipments'
*/
OrderShipmentsForm.head = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: OrderShipments.url({
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

OrderShipments.form = OrderShipmentsForm

export default OrderShipments