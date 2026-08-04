import { queryParams, type RouteQueryOptions, type RouteDefinition, applyUrlDefaults } from './../../../wayfinder'
import fulfillment from './fulfillment'
/**
* @see \Shopper\Livewire\Pages\Order\Index::__invoke
* @see vendor/shopper/framework/src/Livewire/Pages/Order/Index.php:7
* @route '/cpanel/orders'
*/
export const index = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
})

index.definition = {
    methods: ["get","head"],
    url: '/cpanel/orders',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \Shopper\Livewire\Pages\Order\Index::__invoke
* @see vendor/shopper/framework/src/Livewire/Pages/Order/Index.php:7
* @route '/cpanel/orders'
*/
index.url = (options?: RouteQueryOptions) => {
    return index.definition.url + queryParams(options)
}

/**
* @see \Shopper\Livewire\Pages\Order\Index::__invoke
* @see vendor/shopper/framework/src/Livewire/Pages/Order/Index.php:7
* @route '/cpanel/orders'
*/
index.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
})

/**
* @see \Shopper\Livewire\Pages\Order\Index::__invoke
* @see vendor/shopper/framework/src/Livewire/Pages/Order/Index.php:7
* @route '/cpanel/orders'
*/
index.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: index.url(options),
    method: 'head',
})

/**
* @see \Shopper\Livewire\Pages\Order\Shipments::__invoke
* @see vendor/shopper/framework/src/Livewire/Pages/Order/Shipments.php:7
* @route '/cpanel/orders/shipments'
*/
export const shipments = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: shipments.url(options),
    method: 'get',
})

shipments.definition = {
    methods: ["get","head"],
    url: '/cpanel/orders/shipments',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \Shopper\Livewire\Pages\Order\Shipments::__invoke
* @see vendor/shopper/framework/src/Livewire/Pages/Order/Shipments.php:7
* @route '/cpanel/orders/shipments'
*/
shipments.url = (options?: RouteQueryOptions) => {
    return shipments.definition.url + queryParams(options)
}

/**
* @see \Shopper\Livewire\Pages\Order\Shipments::__invoke
* @see vendor/shopper/framework/src/Livewire/Pages/Order/Shipments.php:7
* @route '/cpanel/orders/shipments'
*/
shipments.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: shipments.url(options),
    method: 'get',
})

/**
* @see \Shopper\Livewire\Pages\Order\Shipments::__invoke
* @see vendor/shopper/framework/src/Livewire/Pages/Order/Shipments.php:7
* @route '/cpanel/orders/shipments'
*/
shipments.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: shipments.url(options),
    method: 'head',
})

/**
* @see \Shopper\Livewire\Pages\Order\AbandonedCarts::__invoke
* @see vendor/shopper/framework/src/Livewire/Pages/Order/AbandonedCarts.php:7
* @route '/cpanel/orders/abandoned-carts'
*/
export const abandonedCarts = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: abandonedCarts.url(options),
    method: 'get',
})

abandonedCarts.definition = {
    methods: ["get","head"],
    url: '/cpanel/orders/abandoned-carts',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \Shopper\Livewire\Pages\Order\AbandonedCarts::__invoke
* @see vendor/shopper/framework/src/Livewire/Pages/Order/AbandonedCarts.php:7
* @route '/cpanel/orders/abandoned-carts'
*/
abandonedCarts.url = (options?: RouteQueryOptions) => {
    return abandonedCarts.definition.url + queryParams(options)
}

/**
* @see \Shopper\Livewire\Pages\Order\AbandonedCarts::__invoke
* @see vendor/shopper/framework/src/Livewire/Pages/Order/AbandonedCarts.php:7
* @route '/cpanel/orders/abandoned-carts'
*/
abandonedCarts.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: abandonedCarts.url(options),
    method: 'get',
})

/**
* @see \Shopper\Livewire\Pages\Order\AbandonedCarts::__invoke
* @see vendor/shopper/framework/src/Livewire/Pages/Order/AbandonedCarts.php:7
* @route '/cpanel/orders/abandoned-carts'
*/
abandonedCarts.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: abandonedCarts.url(options),
    method: 'head',
})

/**
* @see \Shopper\Livewire\Pages\Order\Detail::__invoke
* @see vendor/shopper/framework/src/Livewire/Pages/Order/Detail.php:7
* @route '/cpanel/orders/{order}/detail'
*/
export const detail = (args: { order: string | number } | [order: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: detail.url(args, options),
    method: 'get',
})

detail.definition = {
    methods: ["get","head"],
    url: '/cpanel/orders/{order}/detail',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \Shopper\Livewire\Pages\Order\Detail::__invoke
* @see vendor/shopper/framework/src/Livewire/Pages/Order/Detail.php:7
* @route '/cpanel/orders/{order}/detail'
*/
detail.url = (args: { order: string | number } | [order: string | number ] | string | number, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { order: args }
    }

    if (Array.isArray(args)) {
        args = {
            order: args[0],
        }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
        order: args.order,
    }

    return detail.definition.url
            .replace('{order}', parsedArgs.order.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \Shopper\Livewire\Pages\Order\Detail::__invoke
* @see vendor/shopper/framework/src/Livewire/Pages/Order/Detail.php:7
* @route '/cpanel/orders/{order}/detail'
*/
detail.get = (args: { order: string | number } | [order: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: detail.url(args, options),
    method: 'get',
})

/**
* @see \Shopper\Livewire\Pages\Order\Detail::__invoke
* @see vendor/shopper/framework/src/Livewire/Pages/Order/Detail.php:7
* @route '/cpanel/orders/{order}/detail'
*/
detail.head = (args: { order: string | number } | [order: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: detail.url(args, options),
    method: 'head',
})

const orders = {
    index: Object.assign(index, index),
    shipments: Object.assign(shipments, shipments),
    abandonedCarts: Object.assign(abandonedCarts, abandonedCarts),
    detail: Object.assign(detail, detail),
    fulfillment: Object.assign(fulfillment, fulfillment),
}

export default orders