import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition, applyUrlDefaults } from './../../../wayfinder'
import shipments from './shipments'
/**
* @see \App\Http\Controllers\Account\OrderController::show
* @see app/Http/Controllers/Account/OrderController.php:39
* @route '/account/orders/{order}'
*/
export const show = (args: { order: number | { id: number } } | [order: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: show.url(args, options),
    method: 'get',
})

show.definition = {
    methods: ["get","head"],
    url: '/account/orders/{order}',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\Account\OrderController::show
* @see app/Http/Controllers/Account/OrderController.php:39
* @route '/account/orders/{order}'
*/
show.url = (args: { order: number | { id: number } } | [order: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions) => {
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

    return show.definition.url
            .replace('{order}', parsedArgs.order.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\Account\OrderController::show
* @see app/Http/Controllers/Account/OrderController.php:39
* @route '/account/orders/{order}'
*/
show.get = (args: { order: number | { id: number } } | [order: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: show.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Account\OrderController::show
* @see app/Http/Controllers/Account/OrderController.php:39
* @route '/account/orders/{order}'
*/
show.head = (args: { order: number | { id: number } } | [order: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: show.url(args, options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\Account\OrderController::show
* @see app/Http/Controllers/Account/OrderController.php:39
* @route '/account/orders/{order}'
*/
const showForm = (args: { order: number | { id: number } } | [order: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: show.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Account\OrderController::show
* @see app/Http/Controllers/Account/OrderController.php:39
* @route '/account/orders/{order}'
*/
showForm.get = (args: { order: number | { id: number } } | [order: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: show.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Account\OrderController::show
* @see app/Http/Controllers/Account/OrderController.php:39
* @route '/account/orders/{order}'
*/
showForm.head = (args: { order: number | { id: number } } | [order: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: show.url(args, {
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

show.form = showForm

/**
* @see \App\Http\Controllers\Account\RetryKomercePaymentController::__invoke
* @see app/Http/Controllers/Account/RetryKomercePaymentController.php:15
* @route '/account/orders/{order}/retry-payment'
*/
export const retryPayment = (args: { order: number | { id: number } } | [order: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: retryPayment.url(args, options),
    method: 'post',
})

retryPayment.definition = {
    methods: ["post"],
    url: '/account/orders/{order}/retry-payment',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\Account\RetryKomercePaymentController::__invoke
* @see app/Http/Controllers/Account/RetryKomercePaymentController.php:15
* @route '/account/orders/{order}/retry-payment'
*/
retryPayment.url = (args: { order: number | { id: number } } | [order: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions) => {
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

    return retryPayment.definition.url
            .replace('{order}', parsedArgs.order.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\Account\RetryKomercePaymentController::__invoke
* @see app/Http/Controllers/Account/RetryKomercePaymentController.php:15
* @route '/account/orders/{order}/retry-payment'
*/
retryPayment.post = (args: { order: number | { id: number } } | [order: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: retryPayment.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\Account\RetryKomercePaymentController::__invoke
* @see app/Http/Controllers/Account/RetryKomercePaymentController.php:15
* @route '/account/orders/{order}/retry-payment'
*/
const retryPaymentForm = (args: { order: number | { id: number } } | [order: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: retryPayment.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\Account\RetryKomercePaymentController::__invoke
* @see app/Http/Controllers/Account/RetryKomercePaymentController.php:15
* @route '/account/orders/{order}/retry-payment'
*/
retryPaymentForm.post = (args: { order: number | { id: number } } | [order: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: retryPayment.url(args, options),
    method: 'post',
})

retryPayment.form = retryPaymentForm

/**
* @see \App\Http\Controllers\Account\SyncKomercePaymentStatusController::__invoke
* @see app/Http/Controllers/Account/SyncKomercePaymentStatusController.php:16
* @route '/account/orders/{order}/sync-payment'
*/
export const syncPayment = (args: { order: number | { id: number } } | [order: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: syncPayment.url(args, options),
    method: 'post',
})

syncPayment.definition = {
    methods: ["post"],
    url: '/account/orders/{order}/sync-payment',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\Account\SyncKomercePaymentStatusController::__invoke
* @see app/Http/Controllers/Account/SyncKomercePaymentStatusController.php:16
* @route '/account/orders/{order}/sync-payment'
*/
syncPayment.url = (args: { order: number | { id: number } } | [order: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions) => {
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

    return syncPayment.definition.url
            .replace('{order}', parsedArgs.order.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\Account\SyncKomercePaymentStatusController::__invoke
* @see app/Http/Controllers/Account/SyncKomercePaymentStatusController.php:16
* @route '/account/orders/{order}/sync-payment'
*/
syncPayment.post = (args: { order: number | { id: number } } | [order: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: syncPayment.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\Account\SyncKomercePaymentStatusController::__invoke
* @see app/Http/Controllers/Account/SyncKomercePaymentStatusController.php:16
* @route '/account/orders/{order}/sync-payment'
*/
const syncPaymentForm = (args: { order: number | { id: number } } | [order: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: syncPayment.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\Account\SyncKomercePaymentStatusController::__invoke
* @see app/Http/Controllers/Account/SyncKomercePaymentStatusController.php:16
* @route '/account/orders/{order}/sync-payment'
*/
syncPaymentForm.post = (args: { order: number | { id: number } } | [order: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: syncPayment.url(args, options),
    method: 'post',
})

syncPayment.form = syncPaymentForm

/**
* @see \App\Http\Controllers\Account\ConfirmOrderReceivedController::__invoke
* @see app/Http/Controllers/Account/ConfirmOrderReceivedController.php:16
* @route '/account/orders/{order}/confirm-received'
*/
export const confirmReceived = (args: { order: number | { id: number } } | [order: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: confirmReceived.url(args, options),
    method: 'post',
})

confirmReceived.definition = {
    methods: ["post"],
    url: '/account/orders/{order}/confirm-received',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\Account\ConfirmOrderReceivedController::__invoke
* @see app/Http/Controllers/Account/ConfirmOrderReceivedController.php:16
* @route '/account/orders/{order}/confirm-received'
*/
confirmReceived.url = (args: { order: number | { id: number } } | [order: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions) => {
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

    return confirmReceived.definition.url
            .replace('{order}', parsedArgs.order.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\Account\ConfirmOrderReceivedController::__invoke
* @see app/Http/Controllers/Account/ConfirmOrderReceivedController.php:16
* @route '/account/orders/{order}/confirm-received'
*/
confirmReceived.post = (args: { order: number | { id: number } } | [order: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: confirmReceived.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\Account\ConfirmOrderReceivedController::__invoke
* @see app/Http/Controllers/Account/ConfirmOrderReceivedController.php:16
* @route '/account/orders/{order}/confirm-received'
*/
const confirmReceivedForm = (args: { order: number | { id: number } } | [order: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: confirmReceived.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\Account\ConfirmOrderReceivedController::__invoke
* @see app/Http/Controllers/Account/ConfirmOrderReceivedController.php:16
* @route '/account/orders/{order}/confirm-received'
*/
confirmReceivedForm.post = (args: { order: number | { id: number } } | [order: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: confirmReceived.url(args, options),
    method: 'post',
})

confirmReceived.form = confirmReceivedForm

const orders = {
    show: Object.assign(show, show),
    retryPayment: Object.assign(retryPayment, retryPayment),
    syncPayment: Object.assign(syncPayment, syncPayment),
    shipments: Object.assign(shipments, shipments),
    confirmReceived: Object.assign(confirmReceived, confirmReceived),
}

export default orders