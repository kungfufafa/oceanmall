import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition, applyUrlDefaults } from './../../../../../wayfinder'
/**
* @see \App\Http\Controllers\Shop\StripePaymentController::__invoke
* @see app/Http/Controllers/Shop/StripePaymentController.php:15
* @route '/checkout/payment/{number}'
*/
const StripePaymentController = (args: { number: string | number } | [number: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: StripePaymentController.url(args, options),
    method: 'get',
})

StripePaymentController.definition = {
    methods: ["get","head"],
    url: '/checkout/payment/{number}',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\Shop\StripePaymentController::__invoke
* @see app/Http/Controllers/Shop/StripePaymentController.php:15
* @route '/checkout/payment/{number}'
*/
StripePaymentController.url = (args: { number: string | number } | [number: string | number ] | string | number, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { number: args }
    }

    if (Array.isArray(args)) {
        args = {
            number: args[0],
        }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
        number: args.number,
    }

    return StripePaymentController.definition.url
            .replace('{number}', parsedArgs.number.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\Shop\StripePaymentController::__invoke
* @see app/Http/Controllers/Shop/StripePaymentController.php:15
* @route '/checkout/payment/{number}'
*/
StripePaymentController.get = (args: { number: string | number } | [number: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: StripePaymentController.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Shop\StripePaymentController::__invoke
* @see app/Http/Controllers/Shop/StripePaymentController.php:15
* @route '/checkout/payment/{number}'
*/
StripePaymentController.head = (args: { number: string | number } | [number: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: StripePaymentController.url(args, options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\Shop\StripePaymentController::__invoke
* @see app/Http/Controllers/Shop/StripePaymentController.php:15
* @route '/checkout/payment/{number}'
*/
const StripePaymentControllerForm = (args: { number: string | number } | [number: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: StripePaymentController.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Shop\StripePaymentController::__invoke
* @see app/Http/Controllers/Shop/StripePaymentController.php:15
* @route '/checkout/payment/{number}'
*/
StripePaymentControllerForm.get = (args: { number: string | number } | [number: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: StripePaymentController.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Shop\StripePaymentController::__invoke
* @see app/Http/Controllers/Shop/StripePaymentController.php:15
* @route '/checkout/payment/{number}'
*/
StripePaymentControllerForm.head = (args: { number: string | number } | [number: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: StripePaymentController.url(args, {
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

StripePaymentController.form = StripePaymentControllerForm

export default StripePaymentController