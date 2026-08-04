import { queryParams, type RouteQueryOptions, type RouteDefinition, applyUrlDefaults } from './../../../../wayfinder'
/**
* @see \App\Http\Controllers\Cpanel\PrintShipmentLabelController::__invoke
* @see app/Http/Controllers/Cpanel/PrintShipmentLabelController.php:23
* @route '/cpanel/orders/{order}/fulfillment/label'
*/
export const printLabel = (args: { order: number | { id: number } } | [order: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: printLabel.url(args, options),
    method: 'get',
})

printLabel.definition = {
    methods: ["get","head"],
    url: '/cpanel/orders/{order}/fulfillment/label',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\Cpanel\PrintShipmentLabelController::__invoke
* @see app/Http/Controllers/Cpanel/PrintShipmentLabelController.php:23
* @route '/cpanel/orders/{order}/fulfillment/label'
*/
printLabel.url = (args: { order: number | { id: number } } | [order: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions) => {
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

    return printLabel.definition.url
            .replace('{order}', parsedArgs.order.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\Cpanel\PrintShipmentLabelController::__invoke
* @see app/Http/Controllers/Cpanel/PrintShipmentLabelController.php:23
* @route '/cpanel/orders/{order}/fulfillment/label'
*/
printLabel.get = (args: { order: number | { id: number } } | [order: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: printLabel.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Cpanel\PrintShipmentLabelController::__invoke
* @see app/Http/Controllers/Cpanel/PrintShipmentLabelController.php:23
* @route '/cpanel/orders/{order}/fulfillment/label'
*/
printLabel.head = (args: { order: number | { id: number } } | [order: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: printLabel.url(args, options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\Cpanel\OverrideAllocationController::__invoke
* @see app/Http/Controllers/Cpanel/OverrideAllocationController.php:18
* @route '/cpanel/orders/{order}/fulfillment/override-allocation'
*/
export const overrideAllocation = (args: { order: number | { id: number } } | [order: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: overrideAllocation.url(args, options),
    method: 'post',
})

overrideAllocation.definition = {
    methods: ["post"],
    url: '/cpanel/orders/{order}/fulfillment/override-allocation',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\Cpanel\OverrideAllocationController::__invoke
* @see app/Http/Controllers/Cpanel/OverrideAllocationController.php:18
* @route '/cpanel/orders/{order}/fulfillment/override-allocation'
*/
overrideAllocation.url = (args: { order: number | { id: number } } | [order: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions) => {
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

    return overrideAllocation.definition.url
            .replace('{order}', parsedArgs.order.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\Cpanel\OverrideAllocationController::__invoke
* @see app/Http/Controllers/Cpanel/OverrideAllocationController.php:18
* @route '/cpanel/orders/{order}/fulfillment/override-allocation'
*/
overrideAllocation.post = (args: { order: number | { id: number } } | [order: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: overrideAllocation.url(args, options),
    method: 'post',
})

const fulfillment = {
    printLabel: Object.assign(printLabel, printLabel),
    overrideAllocation: Object.assign(overrideAllocation, overrideAllocation),
}

export default fulfillment