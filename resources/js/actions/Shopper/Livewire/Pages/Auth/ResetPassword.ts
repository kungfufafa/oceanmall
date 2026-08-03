import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition, applyUrlDefaults } from './../../../../../wayfinder'
/**
* @see \Shopper\Livewire\Pages\Auth\ResetPassword::__invoke
* @see vendor/shopper/framework/src/Livewire/Pages/Auth/ResetPassword.php:7
* @route '/cpanel/password/reset/{token}'
*/
const ResetPassword = (args: { token: string | number } | [token: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: ResetPassword.url(args, options),
    method: 'get',
})

ResetPassword.definition = {
    methods: ["get","head"],
    url: '/cpanel/password/reset/{token}',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \Shopper\Livewire\Pages\Auth\ResetPassword::__invoke
* @see vendor/shopper/framework/src/Livewire/Pages/Auth/ResetPassword.php:7
* @route '/cpanel/password/reset/{token}'
*/
ResetPassword.url = (args: { token: string | number } | [token: string | number ] | string | number, options?: RouteQueryOptions) => {
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

    return ResetPassword.definition.url
            .replace('{token}', parsedArgs.token.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \Shopper\Livewire\Pages\Auth\ResetPassword::__invoke
* @see vendor/shopper/framework/src/Livewire/Pages/Auth/ResetPassword.php:7
* @route '/cpanel/password/reset/{token}'
*/
ResetPassword.get = (args: { token: string | number } | [token: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: ResetPassword.url(args, options),
    method: 'get',
})

/**
* @see \Shopper\Livewire\Pages\Auth\ResetPassword::__invoke
* @see vendor/shopper/framework/src/Livewire/Pages/Auth/ResetPassword.php:7
* @route '/cpanel/password/reset/{token}'
*/
ResetPassword.head = (args: { token: string | number } | [token: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: ResetPassword.url(args, options),
    method: 'head',
})

/**
* @see \Shopper\Livewire\Pages\Auth\ResetPassword::__invoke
* @see vendor/shopper/framework/src/Livewire/Pages/Auth/ResetPassword.php:7
* @route '/cpanel/password/reset/{token}'
*/
const ResetPasswordForm = (args: { token: string | number } | [token: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: ResetPassword.url(args, options),
    method: 'get',
})

/**
* @see \Shopper\Livewire\Pages\Auth\ResetPassword::__invoke
* @see vendor/shopper/framework/src/Livewire/Pages/Auth/ResetPassword.php:7
* @route '/cpanel/password/reset/{token}'
*/
ResetPasswordForm.get = (args: { token: string | number } | [token: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: ResetPassword.url(args, options),
    method: 'get',
})

/**
* @see \Shopper\Livewire\Pages\Auth\ResetPassword::__invoke
* @see vendor/shopper/framework/src/Livewire/Pages/Auth/ResetPassword.php:7
* @route '/cpanel/password/reset/{token}'
*/
ResetPasswordForm.head = (args: { token: string | number } | [token: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: ResetPassword.url(args, {
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

ResetPassword.form = ResetPasswordForm

export default ResetPassword