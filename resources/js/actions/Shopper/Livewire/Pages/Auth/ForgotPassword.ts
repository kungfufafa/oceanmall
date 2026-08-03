import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition } from './../../../../../wayfinder'
/**
* @see \Shopper\Livewire\Pages\Auth\ForgotPassword::__invoke
* @see vendor/shopper/framework/src/Livewire/Pages/Auth/ForgotPassword.php:7
* @route '/cpanel/password/reset'
*/
const ForgotPassword = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: ForgotPassword.url(options),
    method: 'get',
})

ForgotPassword.definition = {
    methods: ["get","head"],
    url: '/cpanel/password/reset',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \Shopper\Livewire\Pages\Auth\ForgotPassword::__invoke
* @see vendor/shopper/framework/src/Livewire/Pages/Auth/ForgotPassword.php:7
* @route '/cpanel/password/reset'
*/
ForgotPassword.url = (options?: RouteQueryOptions) => {
    return ForgotPassword.definition.url + queryParams(options)
}

/**
* @see \Shopper\Livewire\Pages\Auth\ForgotPassword::__invoke
* @see vendor/shopper/framework/src/Livewire/Pages/Auth/ForgotPassword.php:7
* @route '/cpanel/password/reset'
*/
ForgotPassword.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: ForgotPassword.url(options),
    method: 'get',
})

/**
* @see \Shopper\Livewire\Pages\Auth\ForgotPassword::__invoke
* @see vendor/shopper/framework/src/Livewire/Pages/Auth/ForgotPassword.php:7
* @route '/cpanel/password/reset'
*/
ForgotPassword.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: ForgotPassword.url(options),
    method: 'head',
})

/**
* @see \Shopper\Livewire\Pages\Auth\ForgotPassword::__invoke
* @see vendor/shopper/framework/src/Livewire/Pages/Auth/ForgotPassword.php:7
* @route '/cpanel/password/reset'
*/
const ForgotPasswordForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: ForgotPassword.url(options),
    method: 'get',
})

/**
* @see \Shopper\Livewire\Pages\Auth\ForgotPassword::__invoke
* @see vendor/shopper/framework/src/Livewire/Pages/Auth/ForgotPassword.php:7
* @route '/cpanel/password/reset'
*/
ForgotPasswordForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: ForgotPassword.url(options),
    method: 'get',
})

/**
* @see \Shopper\Livewire\Pages\Auth\ForgotPassword::__invoke
* @see vendor/shopper/framework/src/Livewire/Pages/Auth/ForgotPassword.php:7
* @route '/cpanel/password/reset'
*/
ForgotPasswordForm.head = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: ForgotPassword.url({
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

ForgotPassword.form = ForgotPasswordForm

export default ForgotPassword