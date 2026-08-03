import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition, applyUrlDefaults } from './../../../../wayfinder'
/**
* @see \Shopper\Http\Controllers\AssetController::__invoke
* @see vendor/shopper/framework/src/Http/Controllers/AssetController.php:11
* @route '/cpanel/assets/{file}'
*/
const AssetController = (args: { file: string | number } | [file: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: AssetController.url(args, options),
    method: 'get',
})

AssetController.definition = {
    methods: ["get","head"],
    url: '/cpanel/assets/{file}',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \Shopper\Http\Controllers\AssetController::__invoke
* @see vendor/shopper/framework/src/Http/Controllers/AssetController.php:11
* @route '/cpanel/assets/{file}'
*/
AssetController.url = (args: { file: string | number } | [file: string | number ] | string | number, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { file: args }
    }

    if (Array.isArray(args)) {
        args = {
            file: args[0],
        }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
        file: args.file,
    }

    return AssetController.definition.url
            .replace('{file}', parsedArgs.file.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \Shopper\Http\Controllers\AssetController::__invoke
* @see vendor/shopper/framework/src/Http/Controllers/AssetController.php:11
* @route '/cpanel/assets/{file}'
*/
AssetController.get = (args: { file: string | number } | [file: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: AssetController.url(args, options),
    method: 'get',
})

/**
* @see \Shopper\Http\Controllers\AssetController::__invoke
* @see vendor/shopper/framework/src/Http/Controllers/AssetController.php:11
* @route '/cpanel/assets/{file}'
*/
AssetController.head = (args: { file: string | number } | [file: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: AssetController.url(args, options),
    method: 'head',
})

/**
* @see \Shopper\Http\Controllers\AssetController::__invoke
* @see vendor/shopper/framework/src/Http/Controllers/AssetController.php:11
* @route '/cpanel/assets/{file}'
*/
const AssetControllerForm = (args: { file: string | number } | [file: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: AssetController.url(args, options),
    method: 'get',
})

/**
* @see \Shopper\Http\Controllers\AssetController::__invoke
* @see vendor/shopper/framework/src/Http/Controllers/AssetController.php:11
* @route '/cpanel/assets/{file}'
*/
AssetControllerForm.get = (args: { file: string | number } | [file: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: AssetController.url(args, options),
    method: 'get',
})

/**
* @see \Shopper\Http\Controllers\AssetController::__invoke
* @see vendor/shopper/framework/src/Http/Controllers/AssetController.php:11
* @route '/cpanel/assets/{file}'
*/
AssetControllerForm.head = (args: { file: string | number } | [file: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: AssetController.url(args, {
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

AssetController.form = AssetControllerForm

export default AssetController