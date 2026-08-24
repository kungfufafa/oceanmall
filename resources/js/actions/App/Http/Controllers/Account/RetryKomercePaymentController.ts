import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition, applyUrlDefaults } from './../../../../../wayfinder'
/**
* @see \App\Http\Controllers\Account\RetryKomercePaymentController::__invoke
* @see app/Http/Controllers/Account/RetryKomercePaymentController.php:15
* @route '/account/orders/{order}/retry-payment'
*/
const RetryKomercePaymentController = (args: { order: number | { id: number } } | [order: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: RetryKomercePaymentController.url(args, options),
    method: 'post',
})

RetryKomercePaymentController.definition = {
    methods: ["post"],
    url: '/account/orders/{order}/retry-payment',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\Account\RetryKomercePaymentController::__invoke
* @see app/Http/Controllers/Account/RetryKomercePaymentController.php:15
* @route '/account/orders/{order}/retry-payment'
*/
RetryKomercePaymentController.url = (args: { order: number | { id: number } } | [order: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions) => {
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

    return RetryKomercePaymentController.definition.url
            .replace('{order}', parsedArgs.order.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\Account\RetryKomercePaymentController::__invoke
* @see app/Http/Controllers/Account/RetryKomercePaymentController.php:15
* @route '/account/orders/{order}/retry-payment'
*/
RetryKomercePaymentController.post = (args: { order: number | { id: number } } | [order: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: RetryKomercePaymentController.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\Account\RetryKomercePaymentController::__invoke
* @see app/Http/Controllers/Account/RetryKomercePaymentController.php:15
* @route '/account/orders/{order}/retry-payment'
*/
const RetryKomercePaymentControllerForm = (args: { order: number | { id: number } } | [order: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: RetryKomercePaymentController.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\Account\RetryKomercePaymentController::__invoke
* @see app/Http/Controllers/Account/RetryKomercePaymentController.php:15
* @route '/account/orders/{order}/retry-payment'
*/
RetryKomercePaymentControllerForm.post = (args: { order: number | { id: number } } | [order: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: RetryKomercePaymentController.url(args, options),
    method: 'post',
})

RetryKomercePaymentController.form = RetryKomercePaymentControllerForm

export default RetryKomercePaymentController