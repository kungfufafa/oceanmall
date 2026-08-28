import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition, applyUrlDefaults } from './../../../../../../wayfinder'
/**
* @see \App\Http\Controllers\Api\V1\NotificationController::index
* @see app/Http/Controllers/Api/V1/NotificationController.php:14
* @route '/api/v1/notifications'
*/
export const index = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
})

index.definition = {
    methods: ["get","head"],
    url: '/api/v1/notifications',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\Api\V1\NotificationController::index
* @see app/Http/Controllers/Api/V1/NotificationController.php:14
* @route '/api/v1/notifications'
*/
index.url = (options?: RouteQueryOptions) => {
    return index.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\Api\V1\NotificationController::index
* @see app/Http/Controllers/Api/V1/NotificationController.php:14
* @route '/api/v1/notifications'
*/
index.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Api\V1\NotificationController::index
* @see app/Http/Controllers/Api/V1/NotificationController.php:14
* @route '/api/v1/notifications'
*/
index.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: index.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\Api\V1\NotificationController::index
* @see app/Http/Controllers/Api/V1/NotificationController.php:14
* @route '/api/v1/notifications'
*/
const indexForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: index.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Api\V1\NotificationController::index
* @see app/Http/Controllers/Api/V1/NotificationController.php:14
* @route '/api/v1/notifications'
*/
indexForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: index.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Api\V1\NotificationController::index
* @see app/Http/Controllers/Api/V1/NotificationController.php:14
* @route '/api/v1/notifications'
*/
indexForm.head = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: index.url({
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

index.form = indexForm

/**
* @see \App\Http\Controllers\Api\V1\NotificationController::markAllRead
* @see app/Http/Controllers/Api/V1/NotificationController.php:54
* @route '/api/v1/notifications/read-all'
*/
export const markAllRead = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: markAllRead.url(options),
    method: 'post',
})

markAllRead.definition = {
    methods: ["post"],
    url: '/api/v1/notifications/read-all',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\Api\V1\NotificationController::markAllRead
* @see app/Http/Controllers/Api/V1/NotificationController.php:54
* @route '/api/v1/notifications/read-all'
*/
markAllRead.url = (options?: RouteQueryOptions) => {
    return markAllRead.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\Api\V1\NotificationController::markAllRead
* @see app/Http/Controllers/Api/V1/NotificationController.php:54
* @route '/api/v1/notifications/read-all'
*/
markAllRead.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: markAllRead.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\Api\V1\NotificationController::markAllRead
* @see app/Http/Controllers/Api/V1/NotificationController.php:54
* @route '/api/v1/notifications/read-all'
*/
const markAllReadForm = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: markAllRead.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\Api\V1\NotificationController::markAllRead
* @see app/Http/Controllers/Api/V1/NotificationController.php:54
* @route '/api/v1/notifications/read-all'
*/
markAllReadForm.post = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: markAllRead.url(options),
    method: 'post',
})

markAllRead.form = markAllReadForm

/**
* @see \App\Http\Controllers\Api\V1\NotificationController::markRead
* @see app/Http/Controllers/Api/V1/NotificationController.php:43
* @route '/api/v1/notifications/{notification}/read'
*/
export const markRead = (args: { notification: string | number } | [notification: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: markRead.url(args, options),
    method: 'post',
})

markRead.definition = {
    methods: ["post"],
    url: '/api/v1/notifications/{notification}/read',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\Api\V1\NotificationController::markRead
* @see app/Http/Controllers/Api/V1/NotificationController.php:43
* @route '/api/v1/notifications/{notification}/read'
*/
markRead.url = (args: { notification: string | number } | [notification: string | number ] | string | number, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { notification: args }
    }

    if (Array.isArray(args)) {
        args = {
            notification: args[0],
        }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
        notification: args.notification,
    }

    return markRead.definition.url
            .replace('{notification}', parsedArgs.notification.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\Api\V1\NotificationController::markRead
* @see app/Http/Controllers/Api/V1/NotificationController.php:43
* @route '/api/v1/notifications/{notification}/read'
*/
markRead.post = (args: { notification: string | number } | [notification: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: markRead.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\Api\V1\NotificationController::markRead
* @see app/Http/Controllers/Api/V1/NotificationController.php:43
* @route '/api/v1/notifications/{notification}/read'
*/
const markReadForm = (args: { notification: string | number } | [notification: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: markRead.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\Api\V1\NotificationController::markRead
* @see app/Http/Controllers/Api/V1/NotificationController.php:43
* @route '/api/v1/notifications/{notification}/read'
*/
markReadForm.post = (args: { notification: string | number } | [notification: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: markRead.url(args, options),
    method: 'post',
})

markRead.form = markReadForm

const NotificationController = { index, markAllRead, markRead }

export default NotificationController