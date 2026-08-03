import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition } from './../../../../../wayfinder'
/**
* @see \App\Http\Controllers\Shop\ZoneController::update
* @see app/Http/Controllers/Shop/ZoneController.php:14
* @route '/zone'
*/
export const update = (options?: RouteQueryOptions): RouteDefinition<'patch'> => ({
    url: update.url(options),
    method: 'patch',
})

update.definition = {
    methods: ["patch"],
    url: '/zone',
} satisfies RouteDefinition<["patch"]>

/**
* @see \App\Http\Controllers\Shop\ZoneController::update
* @see app/Http/Controllers/Shop/ZoneController.php:14
* @route '/zone'
*/
update.url = (options?: RouteQueryOptions) => {
    return update.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\Shop\ZoneController::update
* @see app/Http/Controllers/Shop/ZoneController.php:14
* @route '/zone'
*/
update.patch = (options?: RouteQueryOptions): RouteDefinition<'patch'> => ({
    url: update.url(options),
    method: 'patch',
})

/**
* @see \App\Http\Controllers\Shop\ZoneController::update
* @see app/Http/Controllers/Shop/ZoneController.php:14
* @route '/zone'
*/
const updateForm = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: update.url({
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'PATCH',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'post',
})

/**
* @see \App\Http\Controllers\Shop\ZoneController::update
* @see app/Http/Controllers/Shop/ZoneController.php:14
* @route '/zone'
*/
updateForm.patch = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: update.url({
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'PATCH',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'post',
})

update.form = updateForm

const ZoneController = { update }

export default ZoneController