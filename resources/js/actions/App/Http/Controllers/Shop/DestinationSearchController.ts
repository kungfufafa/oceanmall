import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition } from './../../../../../wayfinder'
/**
* @see \App\Http\Controllers\Shop\DestinationSearchController::__invoke
* @see app/Http/Controllers/Shop/DestinationSearchController.php:15
* @route '/checkout/destinations'
*/
const DestinationSearchController = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: DestinationSearchController.url(options),
    method: 'get',
})

DestinationSearchController.definition = {
    methods: ["get","head"],
    url: '/checkout/destinations',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\Shop\DestinationSearchController::__invoke
* @see app/Http/Controllers/Shop/DestinationSearchController.php:15
* @route '/checkout/destinations'
*/
DestinationSearchController.url = (options?: RouteQueryOptions) => {
    return DestinationSearchController.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\Shop\DestinationSearchController::__invoke
* @see app/Http/Controllers/Shop/DestinationSearchController.php:15
* @route '/checkout/destinations'
*/
DestinationSearchController.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: DestinationSearchController.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Shop\DestinationSearchController::__invoke
* @see app/Http/Controllers/Shop/DestinationSearchController.php:15
* @route '/checkout/destinations'
*/
DestinationSearchController.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: DestinationSearchController.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\Shop\DestinationSearchController::__invoke
* @see app/Http/Controllers/Shop/DestinationSearchController.php:15
* @route '/checkout/destinations'
*/
const DestinationSearchControllerForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: DestinationSearchController.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Shop\DestinationSearchController::__invoke
* @see app/Http/Controllers/Shop/DestinationSearchController.php:15
* @route '/checkout/destinations'
*/
DestinationSearchControllerForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: DestinationSearchController.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Shop\DestinationSearchController::__invoke
* @see app/Http/Controllers/Shop/DestinationSearchController.php:15
* @route '/checkout/destinations'
*/
DestinationSearchControllerForm.head = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: DestinationSearchController.url({
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

DestinationSearchController.form = DestinationSearchControllerForm

export default DestinationSearchController