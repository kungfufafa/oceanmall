import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition, applyUrlDefaults } from './../../../../../wayfinder'
/**
* @see \App\Http\Controllers\Account\SyncKomercePaymentStatusController::__invoke
* @see app/Http/Controllers/Account/SyncKomercePaymentStatusController.php:16
* @route '/account/orders/{order}/sync-payment'
*/
const SyncKomercePaymentStatusController = (args: { order: number | { id: number } } | [order: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: SyncKomercePaymentStatusController.url(args, options),
    method: 'post',
})

SyncKomercePaymentStatusController.definition = {
    methods: ["post"],
    url: '/account/orders/{order}/sync-payment',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\Account\SyncKomercePaymentStatusController::__invoke
* @see app/Http/Controllers/Account/SyncKomercePaymentStatusController.php:16
* @route '/account/orders/{order}/sync-payment'
*/
SyncKomercePaymentStatusController.url = (args: { order: number | { id: number } } | [order: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions) => {
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

    return SyncKomercePaymentStatusController.definition.url
            .replace('{order}', parsedArgs.order.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\Account\SyncKomercePaymentStatusController::__invoke
* @see app/Http/Controllers/Account/SyncKomercePaymentStatusController.php:16
* @route '/account/orders/{order}/sync-payment'
*/
SyncKomercePaymentStatusController.post = (args: { order: number | { id: number } } | [order: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: SyncKomercePaymentStatusController.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\Account\SyncKomercePaymentStatusController::__invoke
* @see app/Http/Controllers/Account/SyncKomercePaymentStatusController.php:16
* @route '/account/orders/{order}/sync-payment'
*/
const SyncKomercePaymentStatusControllerForm = (args: { order: number | { id: number } } | [order: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: SyncKomercePaymentStatusController.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\Account\SyncKomercePaymentStatusController::__invoke
* @see app/Http/Controllers/Account/SyncKomercePaymentStatusController.php:16
* @route '/account/orders/{order}/sync-payment'
*/
SyncKomercePaymentStatusControllerForm.post = (args: { order: number | { id: number } } | [order: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: SyncKomercePaymentStatusController.url(args, options),
    method: 'post',
})

SyncKomercePaymentStatusController.form = SyncKomercePaymentStatusControllerForm

export default SyncKomercePaymentStatusController