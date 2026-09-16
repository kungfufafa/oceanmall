import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition, applyUrlDefaults } from './../../../../../wayfinder'
/**
* @see \App\Http\Controllers\Account\CancelOrderController::__invoke
* @see app/Http/Controllers/Account/CancelOrderController.php:16
* @route '/account/orders/{order}/cancel'
*/
const CancelOrderController = (args: { order: number | { id: number } } | [order: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: CancelOrderController.url(args, options),
    method: 'post',
})

CancelOrderController.definition = {
    methods: ["post"],
    url: '/account/orders/{order}/cancel',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\Account\CancelOrderController::__invoke
* @see app/Http/Controllers/Account/CancelOrderController.php:16
* @route '/account/orders/{order}/cancel'
*/
CancelOrderController.url = (args: { order: number | { id: number } } | [order: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions) => {
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

    return CancelOrderController.definition.url
            .replace('{order}', parsedArgs.order.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\Account\CancelOrderController::__invoke
* @see app/Http/Controllers/Account/CancelOrderController.php:16
* @route '/account/orders/{order}/cancel'
*/
CancelOrderController.post = (args: { order: number | { id: number } } | [order: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: CancelOrderController.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\Account\CancelOrderController::__invoke
* @see app/Http/Controllers/Account/CancelOrderController.php:16
* @route '/account/orders/{order}/cancel'
*/
const CancelOrderControllerForm = (args: { order: number | { id: number } } | [order: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: CancelOrderController.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\Account\CancelOrderController::__invoke
* @see app/Http/Controllers/Account/CancelOrderController.php:16
* @route '/account/orders/{order}/cancel'
*/
CancelOrderControllerForm.post = (args: { order: number | { id: number } } | [order: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: CancelOrderController.url(args, options),
    method: 'post',
})

CancelOrderController.form = CancelOrderControllerForm

export default CancelOrderController