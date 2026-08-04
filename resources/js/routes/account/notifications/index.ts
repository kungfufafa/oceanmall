import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition, applyUrlDefaults } from './../../../wayfinder'
/**
* @see \App\Http\Controllers\Account\NotificationController::read
* @see app/Http/Controllers/Account/NotificationController.php:39
* @route '/account/notifications/{notification}/read'
*/
export const read = (args: { notification: string | number } | [notification: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: read.url(args, options),
    method: 'post',
})

read.definition = {
    methods: ["post"],
    url: '/account/notifications/{notification}/read',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\Account\NotificationController::read
* @see app/Http/Controllers/Account/NotificationController.php:39
* @route '/account/notifications/{notification}/read'
*/
read.url = (args: { notification: string | number } | [notification: string | number ] | string | number, options?: RouteQueryOptions) => {
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

    return read.definition.url
            .replace('{notification}', parsedArgs.notification.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\Account\NotificationController::read
* @see app/Http/Controllers/Account/NotificationController.php:39
* @route '/account/notifications/{notification}/read'
*/
read.post = (args: { notification: string | number } | [notification: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: read.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\Account\NotificationController::read
* @see app/Http/Controllers/Account/NotificationController.php:39
* @route '/account/notifications/{notification}/read'
*/
const readForm = (args: { notification: string | number } | [notification: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: read.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\Account\NotificationController::read
* @see app/Http/Controllers/Account/NotificationController.php:39
* @route '/account/notifications/{notification}/read'
*/
readForm.post = (args: { notification: string | number } | [notification: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: read.url(args, options),
    method: 'post',
})

read.form = readForm

/**
* @see \App\Http\Controllers\Account\NotificationController::readAll
* @see app/Http/Controllers/Account/NotificationController.php:51
* @route '/account/notifications/read-all'
*/
export const readAll = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: readAll.url(options),
    method: 'post',
})

readAll.definition = {
    methods: ["post"],
    url: '/account/notifications/read-all',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\Account\NotificationController::readAll
* @see app/Http/Controllers/Account/NotificationController.php:51
* @route '/account/notifications/read-all'
*/
readAll.url = (options?: RouteQueryOptions) => {
    return readAll.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\Account\NotificationController::readAll
* @see app/Http/Controllers/Account/NotificationController.php:51
* @route '/account/notifications/read-all'
*/
readAll.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: readAll.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\Account\NotificationController::readAll
* @see app/Http/Controllers/Account/NotificationController.php:51
* @route '/account/notifications/read-all'
*/
const readAllForm = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: readAll.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\Account\NotificationController::readAll
* @see app/Http/Controllers/Account/NotificationController.php:51
* @route '/account/notifications/read-all'
*/
readAllForm.post = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: readAll.url(options),
    method: 'post',
})

readAll.form = readAllForm

const notifications = {
    read: Object.assign(read, read),
    readAll: Object.assign(readAll, readAll),
}

export default notifications