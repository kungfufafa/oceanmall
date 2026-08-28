import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition } from './../../../../../../wayfinder'
/**
* @see \App\Http\Controllers\Api\V1\DestinationController::__invoke
* @see app/Http/Controllers/Api/V1/DestinationController.php:15
* @route '/api/v1/checkout/destinations'
*/
const DestinationController = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: DestinationController.url(options),
    method: 'get',
})

DestinationController.definition = {
    methods: ["get","head"],
    url: '/api/v1/checkout/destinations',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\Api\V1\DestinationController::__invoke
* @see app/Http/Controllers/Api/V1/DestinationController.php:15
* @route '/api/v1/checkout/destinations'
*/
DestinationController.url = (options?: RouteQueryOptions) => {
    return DestinationController.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\Api\V1\DestinationController::__invoke
* @see app/Http/Controllers/Api/V1/DestinationController.php:15
* @route '/api/v1/checkout/destinations'
*/
DestinationController.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: DestinationController.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Api\V1\DestinationController::__invoke
* @see app/Http/Controllers/Api/V1/DestinationController.php:15
* @route '/api/v1/checkout/destinations'
*/
DestinationController.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: DestinationController.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\Api\V1\DestinationController::__invoke
* @see app/Http/Controllers/Api/V1/DestinationController.php:15
* @route '/api/v1/checkout/destinations'
*/
const DestinationControllerForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: DestinationController.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Api\V1\DestinationController::__invoke
* @see app/Http/Controllers/Api/V1/DestinationController.php:15
* @route '/api/v1/checkout/destinations'
*/
DestinationControllerForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: DestinationController.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Api\V1\DestinationController::__invoke
* @see app/Http/Controllers/Api/V1/DestinationController.php:15
* @route '/api/v1/checkout/destinations'
*/
DestinationControllerForm.head = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: DestinationController.url({
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

DestinationController.form = DestinationControllerForm

export default DestinationController