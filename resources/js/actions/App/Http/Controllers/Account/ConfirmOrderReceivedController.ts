import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition, applyUrlDefaults } from './../../../../../wayfinder'
/**
* @see \App\Http\Controllers\Account\ConfirmOrderReceivedController::__invoke
* @see app/Http/Controllers/Account/ConfirmOrderReceivedController.php:15
* @route '/account/orders/{order}/confirm-received'
*/
const ConfirmOrderReceivedController = (args: { order: number | { id: number } } | [order: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: ConfirmOrderReceivedController.url(args, options),
    method: 'post',
})

ConfirmOrderReceivedController.definition = {
    methods: ["post"],
    url: '/account/orders/{order}/confirm-received',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\Account\ConfirmOrderReceivedController::__invoke
* @see app/Http/Controllers/Account/ConfirmOrderReceivedController.php:15
* @route '/account/orders/{order}/confirm-received'
*/
ConfirmOrderReceivedController.url = (args: { order: number | { id: number } } | [order: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions) => {
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

    return ConfirmOrderReceivedController.definition.url
            .replace('{order}', parsedArgs.order.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\Account\ConfirmOrderReceivedController::__invoke
* @see app/Http/Controllers/Account/ConfirmOrderReceivedController.php:15
* @route '/account/orders/{order}/confirm-received'
*/
ConfirmOrderReceivedController.post = (args: { order: number | { id: number } } | [order: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: ConfirmOrderReceivedController.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\Account\ConfirmOrderReceivedController::__invoke
* @see app/Http/Controllers/Account/ConfirmOrderReceivedController.php:15
* @route '/account/orders/{order}/confirm-received'
*/
const ConfirmOrderReceivedControllerForm = (args: { order: number | { id: number } } | [order: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: ConfirmOrderReceivedController.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\Account\ConfirmOrderReceivedController::__invoke
* @see app/Http/Controllers/Account/ConfirmOrderReceivedController.php:15
* @route '/account/orders/{order}/confirm-received'
*/
ConfirmOrderReceivedControllerForm.post = (args: { order: number | { id: number } } | [order: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: ConfirmOrderReceivedController.url(args, options),
    method: 'post',
})

ConfirmOrderReceivedController.form = ConfirmOrderReceivedControllerForm

export default ConfirmOrderReceivedController