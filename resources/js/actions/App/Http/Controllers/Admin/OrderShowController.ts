import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition, applyUrlDefaults } from './../../../../../wayfinder'
/**
* @see \App\Http\Controllers\Admin\OrderShowController::__invoke
* @see app/Http/Controllers/Admin/OrderShowController.php:18
* @route '/admin/orders/{order}'
*/
const OrderShowController = (args: { order: number | { id: number } } | [order: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: OrderShowController.url(args, options),
    method: 'get',
})

OrderShowController.definition = {
    methods: ["get","head"],
    url: '/admin/orders/{order}',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\Admin\OrderShowController::__invoke
* @see app/Http/Controllers/Admin/OrderShowController.php:18
* @route '/admin/orders/{order}'
*/
OrderShowController.url = (args: { order: number | { id: number } } | [order: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { order: args }
    }

    if (typeof args === 'object' && !Array.isArray(args) && 'id' in args) {
        args = { order: args.id }
    }

    if (Array.isArray(args)) {
        args = {
            order: args[0],
        }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
        order: typeof args.order === 'object'
        ? args.order.id
        : args.order,
    }

    return OrderShowController.definition.url
            .replace('{order}', parsedArgs.order.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\Admin\OrderShowController::__invoke
* @see app/Http/Controllers/Admin/OrderShowController.php:18
* @route '/admin/orders/{order}'
*/
OrderShowController.get = (args: { order: number | { id: number } } | [order: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: OrderShowController.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Admin\OrderShowController::__invoke
* @see app/Http/Controllers/Admin/OrderShowController.php:18
* @route '/admin/orders/{order}'
*/
OrderShowController.head = (args: { order: number | { id: number } } | [order: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: OrderShowController.url(args, options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\Admin\OrderShowController::__invoke
* @see app/Http/Controllers/Admin/OrderShowController.php:18
* @route '/admin/orders/{order}'
*/
const OrderShowControllerForm = (args: { order: number | { id: number } } | [order: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: OrderShowController.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Admin\OrderShowController::__invoke
* @see app/Http/Controllers/Admin/OrderShowController.php:18
* @route '/admin/orders/{order}'
*/
OrderShowControllerForm.get = (args: { order: number | { id: number } } | [order: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: OrderShowController.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Admin\OrderShowController::__invoke
* @see app/Http/Controllers/Admin/OrderShowController.php:18
* @route '/admin/orders/{order}'
*/
OrderShowControllerForm.head = (args: { order: number | { id: number } } | [order: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: OrderShowController.url(args, {
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

OrderShowController.form = OrderShowControllerForm

export default OrderShowController