import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition, applyUrlDefaults } from './../../../wayfinder'
/**
* @see \Shopper\Livewire\Pages\Auth\ForgotPassword::__invoke
* @see vendor/shopper/framework/src/Livewire/Pages/Auth/ForgotPassword.php:7
* @route '/cpanel/password/reset'
*/
export const request = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: request.url(options),
    method: 'get',
})

request.definition = {
    methods: ["get","head"],
    url: '/cpanel/password/reset',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \Shopper\Livewire\Pages\Auth\ForgotPassword::__invoke
* @see vendor/shopper/framework/src/Livewire/Pages/Auth/ForgotPassword.php:7
* @route '/cpanel/password/reset'
*/
request.url = (options?: RouteQueryOptions) => {
    return request.definition.url + queryParams(options)
}

/**
* @see \Shopper\Livewire\Pages\Auth\ForgotPassword::__invoke
* @see vendor/shopper/framework/src/Livewire/Pages/Auth/ForgotPassword.php:7
* @route '/cpanel/password/reset'
*/
request.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: request.url(options),
    method: 'get',
})

/**
* @see \Shopper\Livewire\Pages\Auth\ForgotPassword::__invoke
* @see vendor/shopper/framework/src/Livewire/Pages/Auth/ForgotPassword.php:7
* @route '/cpanel/password/reset'
*/
request.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: request.url(options),
    method: 'head',
})

/**
* @see \Shopper\Livewire\Pages\Auth\ForgotPassword::__invoke
* @see vendor/shopper/framework/src/Livewire/Pages/Auth/ForgotPassword.php:7
* @route '/cpanel/password/reset'
*/
const requestForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: request.url(options),
    method: 'get',
})

/**
* @see \Shopper\Livewire\Pages\Auth\ForgotPassword::__invoke
* @see vendor/shopper/framework/src/Livewire/Pages/Auth/ForgotPassword.php:7
* @route '/cpanel/password/reset'
*/
requestForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: request.url(options),
    method: 'get',
})

/**
* @see \Shopper\Livewire\Pages\Auth\ForgotPassword::__invoke
* @see vendor/shopper/framework/src/Livewire/Pages/Auth/ForgotPassword.php:7
* @route '/cpanel/password/reset'
*/
requestForm.head = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: request.url({
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

request.form = requestForm

/**
* @see \Shopper\Livewire\Pages\Auth\ResetPassword::__invoke
* @see vendor/shopper/framework/src/Livewire/Pages/Auth/ResetPassword.php:7
* @route '/cpanel/password/reset/{token}'
*/
export const reset = (args: { token: string | number } | [token: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: reset.url(args, options),
    method: 'get',
})

reset.definition = {
    methods: ["get","head"],
    url: '/cpanel/password/reset/{token}',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \Shopper\Livewire\Pages\Auth\ResetPassword::__invoke
* @see vendor/shopper/framework/src/Livewire/Pages/Auth/ResetPassword.php:7
* @route '/cpanel/password/reset/{token}'
*/
reset.url = (args: { token: string | number } | [token: string | number ] | string | number, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { token: args }
    }

    if (Array.isArray(args)) {
        args = {
            token: args[0],
        }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
        token: args.token,
    }

    return reset.definition.url
            .replace('{token}', parsedArgs.token.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \Shopper\Livewire\Pages\Auth\ResetPassword::__invoke
* @see vendor/shopper/framework/src/Livewire/Pages/Auth/ResetPassword.php:7
* @route '/cpanel/password/reset/{token}'
*/
reset.get = (args: { token: string | number } | [token: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: reset.url(args, options),
    method: 'get',
})

/**
* @see \Shopper\Livewire\Pages\Auth\ResetPassword::__invoke
* @see vendor/shopper/framework/src/Livewire/Pages/Auth/ResetPassword.php:7
* @route '/cpanel/password/reset/{token}'
*/
reset.head = (args: { token: string | number } | [token: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: reset.url(args, options),
    method: 'head',
})

/**
* @see \Shopper\Livewire\Pages\Auth\ResetPassword::__invoke
* @see vendor/shopper/framework/src/Livewire/Pages/Auth/ResetPassword.php:7
* @route '/cpanel/password/reset/{token}'
*/
const resetForm = (args: { token: string | number } | [token: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: reset.url(args, options),
    method: 'get',
})

/**
* @see \Shopper\Livewire\Pages\Auth\ResetPassword::__invoke
* @see vendor/shopper/framework/src/Livewire/Pages/Auth/ResetPassword.php:7
* @route '/cpanel/password/reset/{token}'
*/
resetForm.get = (args: { token: string | number } | [token: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: reset.url(args, options),
    method: 'get',
})

/**
* @see \Shopper\Livewire\Pages\Auth\ResetPassword::__invoke
* @see vendor/shopper/framework/src/Livewire/Pages/Auth/ResetPassword.php:7
* @route '/cpanel/password/reset/{token}'
*/
resetForm.head = (args: { token: string | number } | [token: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: reset.url(args, {
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

reset.form = resetForm

const password = {
    request: Object.assign(request, request),
    reset: Object.assign(reset, reset),
}

export default password