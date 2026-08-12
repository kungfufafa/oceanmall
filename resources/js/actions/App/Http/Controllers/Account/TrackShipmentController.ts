import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition, applyUrlDefaults } from './../../../../../wayfinder'
/**
* @see \App\Http\Controllers\Account\TrackShipmentController::__invoke
* @see app/Http/Controllers/Account/TrackShipmentController.php:18
* @route '/account/orders/{order}/shipments/{shipment}/track'
*/
const TrackShipmentController = (args: { order: number | { id: number }, shipment: string | number | { id: string | number } } | [order: number | { id: number }, shipment: string | number | { id: string | number } ], options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: TrackShipmentController.url(args, options),
    method: 'post',
})

TrackShipmentController.definition = {
    methods: ["post"],
    url: '/account/orders/{order}/shipments/{shipment}/track',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\Account\TrackShipmentController::__invoke
* @see app/Http/Controllers/Account/TrackShipmentController.php:18
* @route '/account/orders/{order}/shipments/{shipment}/track'
*/
TrackShipmentController.url = (args: { order: number | { id: number }, shipment: string | number | { id: string | number } } | [order: number | { id: number }, shipment: string | number | { id: string | number } ], options?: RouteQueryOptions) => {
    if (Array.isArray(args)) {
        args = {
            order: args[0],
            shipment: args[1],
        }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
        order: typeof args.order === 'object'
        ? args.order.id
        : args.order,
        shipment: typeof args.shipment === 'object'
        ? args.shipment.id
        : args.shipment,
    }

    return TrackShipmentController.definition.url
            .replace('{order}', parsedArgs.order.toString())
            .replace('{shipment}', parsedArgs.shipment.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\Account\TrackShipmentController::__invoke
* @see app/Http/Controllers/Account/TrackShipmentController.php:18
* @route '/account/orders/{order}/shipments/{shipment}/track'
*/
TrackShipmentController.post = (args: { order: number | { id: number }, shipment: string | number | { id: string | number } } | [order: number | { id: number }, shipment: string | number | { id: string | number } ], options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: TrackShipmentController.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\Account\TrackShipmentController::__invoke
* @see app/Http/Controllers/Account/TrackShipmentController.php:18
* @route '/account/orders/{order}/shipments/{shipment}/track'
*/
const TrackShipmentControllerForm = (args: { order: number | { id: number }, shipment: string | number | { id: string | number } } | [order: number | { id: number }, shipment: string | number | { id: string | number } ], options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: TrackShipmentController.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\Account\TrackShipmentController::__invoke
* @see app/Http/Controllers/Account/TrackShipmentController.php:18
* @route '/account/orders/{order}/shipments/{shipment}/track'
*/
TrackShipmentControllerForm.post = (args: { order: number | { id: number }, shipment: string | number | { id: string | number } } | [order: number | { id: number }, shipment: string | number | { id: string | number } ], options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: TrackShipmentController.url(args, options),
    method: 'post',
})

TrackShipmentController.form = TrackShipmentControllerForm

export default TrackShipmentController