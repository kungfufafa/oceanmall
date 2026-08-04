import { queryParams, type RouteQueryOptions, type RouteDefinition, applyUrlDefaults } from './../../../../../wayfinder'
/**
* @see \App\Http\Controllers\Admin\PrintShipmentLabelController::__invoke
* @see app/Http/Controllers/Admin/PrintShipmentLabelController.php:23
* @route '/cpanel/orders/{order}/fulfillment/label'
*/
const PrintShipmentLabelController = (args: { order: number | { id: number } } | [order: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: PrintShipmentLabelController.url(args, options),
    method: 'get',
})

PrintShipmentLabelController.definition = {
    methods: ["get","head"],
    url: '/cpanel/orders/{order}/fulfillment/label',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\Admin\PrintShipmentLabelController::__invoke
* @see app/Http/Controllers/Admin/PrintShipmentLabelController.php:23
* @route '/cpanel/orders/{order}/fulfillment/label'
*/
PrintShipmentLabelController.url = (args: { order: number | { id: number } } | [order: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions) => {
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

    return PrintShipmentLabelController.definition.url
            .replace('{order}', parsedArgs.order.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\Admin\PrintShipmentLabelController::__invoke
* @see app/Http/Controllers/Admin/PrintShipmentLabelController.php:23
* @route '/cpanel/orders/{order}/fulfillment/label'
*/
PrintShipmentLabelController.get = (args: { order: number | { id: number } } | [order: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: PrintShipmentLabelController.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Admin\PrintShipmentLabelController::__invoke
* @see app/Http/Controllers/Admin/PrintShipmentLabelController.php:23
* @route '/cpanel/orders/{order}/fulfillment/label'
*/
PrintShipmentLabelController.head = (args: { order: number | { id: number } } | [order: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: PrintShipmentLabelController.url(args, options),
    method: 'head',
})

export default PrintShipmentLabelController