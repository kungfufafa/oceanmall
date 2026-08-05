import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition, applyUrlDefaults } from './../../../../../wayfinder'
/**
* @see \App\Http\Controllers\Cpanel\OverrideAllocationController::__invoke
* @see app/Http/Controllers/Cpanel/OverrideAllocationController.php:18
* @route '/cpanel/orders/{order}/fulfillment/override-allocation'
*/
const OverrideAllocationController = (args: { order: number | { id: number } } | [order: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: OverrideAllocationController.url(args, options),
    method: 'post',
})

OverrideAllocationController.definition = {
    methods: ["post"],
    url: '/cpanel/orders/{order}/fulfillment/override-allocation',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\Cpanel\OverrideAllocationController::__invoke
* @see app/Http/Controllers/Cpanel/OverrideAllocationController.php:18
* @route '/cpanel/orders/{order}/fulfillment/override-allocation'
*/
OverrideAllocationController.url = (args: { order: number | { id: number } } | [order: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions) => {
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

    return OverrideAllocationController.definition.url
            .replace('{order}', parsedArgs.order.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\Cpanel\OverrideAllocationController::__invoke
* @see app/Http/Controllers/Cpanel/OverrideAllocationController.php:18
* @route '/cpanel/orders/{order}/fulfillment/override-allocation'
*/
OverrideAllocationController.post = (args: { order: number | { id: number } } | [order: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: OverrideAllocationController.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\Cpanel\OverrideAllocationController::__invoke
* @see app/Http/Controllers/Cpanel/OverrideAllocationController.php:18
* @route '/cpanel/orders/{order}/fulfillment/override-allocation'
*/
const OverrideAllocationControllerForm = (args: { order: number | { id: number } } | [order: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: OverrideAllocationController.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\Cpanel\OverrideAllocationController::__invoke
* @see app/Http/Controllers/Cpanel/OverrideAllocationController.php:18
* @route '/cpanel/orders/{order}/fulfillment/override-allocation'
*/
OverrideAllocationControllerForm.post = (args: { order: number | { id: number } } | [order: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: OverrideAllocationController.url(args, options),
    method: 'post',
})

OverrideAllocationController.form = OverrideAllocationControllerForm

export default OverrideAllocationController