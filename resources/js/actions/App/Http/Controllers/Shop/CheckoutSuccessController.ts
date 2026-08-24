import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition, applyUrlDefaults } from './../../../../../wayfinder'
/**
* @see \App\Http\Controllers\Shop\CheckoutSuccessController::__invoke
* @see app/Http/Controllers/Shop/CheckoutSuccessController.php:17
* @route '/checkout/success/{order}'
*/
const CheckoutSuccessController = (args: { order: number | { id: number } } | [order: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: CheckoutSuccessController.url(args, options),
    method: 'get',
})

CheckoutSuccessController.definition = {
    methods: ["get","head"],
    url: '/checkout/success/{order}',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\Shop\CheckoutSuccessController::__invoke
* @see app/Http/Controllers/Shop/CheckoutSuccessController.php:17
* @route '/checkout/success/{order}'
*/
CheckoutSuccessController.url = (args: { order: number | { id: number } } | [order: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions) => {
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

    return CheckoutSuccessController.definition.url
            .replace('{order}', parsedArgs.order.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\Shop\CheckoutSuccessController::__invoke
* @see app/Http/Controllers/Shop/CheckoutSuccessController.php:17
* @route '/checkout/success/{order}'
*/
CheckoutSuccessController.get = (args: { order: number | { id: number } } | [order: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: CheckoutSuccessController.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Shop\CheckoutSuccessController::__invoke
* @see app/Http/Controllers/Shop/CheckoutSuccessController.php:17
* @route '/checkout/success/{order}'
*/
CheckoutSuccessController.head = (args: { order: number | { id: number } } | [order: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: CheckoutSuccessController.url(args, options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\Shop\CheckoutSuccessController::__invoke
* @see app/Http/Controllers/Shop/CheckoutSuccessController.php:17
* @route '/checkout/success/{order}'
*/
const CheckoutSuccessControllerForm = (args: { order: number | { id: number } } | [order: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: CheckoutSuccessController.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Shop\CheckoutSuccessController::__invoke
* @see app/Http/Controllers/Shop/CheckoutSuccessController.php:17
* @route '/checkout/success/{order}'
*/
CheckoutSuccessControllerForm.get = (args: { order: number | { id: number } } | [order: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: CheckoutSuccessController.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Shop\CheckoutSuccessController::__invoke
* @see app/Http/Controllers/Shop/CheckoutSuccessController.php:17
* @route '/checkout/success/{order}'
*/
CheckoutSuccessControllerForm.head = (args: { order: number | { id: number } } | [order: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: CheckoutSuccessController.url(args, {
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

CheckoutSuccessController.form = CheckoutSuccessControllerForm

export default CheckoutSuccessController