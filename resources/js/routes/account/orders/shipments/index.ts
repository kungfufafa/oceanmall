import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition, applyUrlDefaults } from './../../../../wayfinder'
/**
* @see \App\Http\Controllers\Account\TrackShipmentController::__invoke
* @see app/Http/Controllers/Account/TrackShipmentController.php:17
* @route '/account/orders/{order}/shipments/{shipment}/track'
*/
export const track = (args: { order: number | { id: number }, shipment: number | { id: number } } | [order: number | { id: number }, shipment: number | { id: number } ], options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: track.url(args, options),
    method: 'post',
})

track.definition = {
    methods: ["post"],
    url: '/account/orders/{order}/shipments/{shipment}/track',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\Account\TrackShipmentController::__invoke
* @see app/Http/Controllers/Account/TrackShipmentController.php:17
* @route '/account/orders/{order}/shipments/{shipment}/track'
*/
track.url = (args: { order: number | { id: number }, shipment: number | { id: number } } | [order: number | { id: number }, shipment: number | { id: number } ], options?: RouteQueryOptions) => {
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

    return track.definition.url
            .replace('{order}', parsedArgs.order.toString())
            .replace('{shipment}', parsedArgs.shipment.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\Account\TrackShipmentController::__invoke
* @see app/Http/Controllers/Account/TrackShipmentController.php:17
* @route '/account/orders/{order}/shipments/{shipment}/track'
*/
track.post = (args: { order: number | { id: number }, shipment: number | { id: number } } | [order: number | { id: number }, shipment: number | { id: number } ], options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: track.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\Account\TrackShipmentController::__invoke
* @see app/Http/Controllers/Account/TrackShipmentController.php:17
* @route '/account/orders/{order}/shipments/{shipment}/track'
*/
const trackForm = (args: { order: number | { id: number }, shipment: number | { id: number } } | [order: number | { id: number }, shipment: number | { id: number } ], options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: track.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\Account\TrackShipmentController::__invoke
* @see app/Http/Controllers/Account/TrackShipmentController.php:17
* @route '/account/orders/{order}/shipments/{shipment}/track'
*/
trackForm.post = (args: { order: number | { id: number }, shipment: number | { id: number } } | [order: number | { id: number }, shipment: number | { id: number } ], options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: track.url(args, options),
    method: 'post',
})

track.form = trackForm

const shipments = {
    track: Object.assign(track, track),
}

export default shipments