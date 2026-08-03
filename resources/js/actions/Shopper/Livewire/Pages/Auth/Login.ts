import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition } from './../../../../../wayfinder'
/**
* @see \Shopper\Livewire\Pages\Auth\Login::__invoke
* @see vendor/shopper/framework/src/Livewire/Pages/Auth/Login.php:7
* @route '/cpanel/login'
*/
const Login = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: Login.url(options),
    method: 'get',
})

Login.definition = {
    methods: ["get","head"],
    url: '/cpanel/login',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \Shopper\Livewire\Pages\Auth\Login::__invoke
* @see vendor/shopper/framework/src/Livewire/Pages/Auth/Login.php:7
* @route '/cpanel/login'
*/
Login.url = (options?: RouteQueryOptions) => {
    return Login.definition.url + queryParams(options)
}

/**
* @see \Shopper\Livewire\Pages\Auth\Login::__invoke
* @see vendor/shopper/framework/src/Livewire/Pages/Auth/Login.php:7
* @route '/cpanel/login'
*/
Login.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: Login.url(options),
    method: 'get',
})

/**
* @see \Shopper\Livewire\Pages\Auth\Login::__invoke
* @see vendor/shopper/framework/src/Livewire/Pages/Auth/Login.php:7
* @route '/cpanel/login'
*/
Login.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: Login.url(options),
    method: 'head',
})

/**
* @see \Shopper\Livewire\Pages\Auth\Login::__invoke
* @see vendor/shopper/framework/src/Livewire/Pages/Auth/Login.php:7
* @route '/cpanel/login'
*/
const LoginForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: Login.url(options),
    method: 'get',
})

/**
* @see \Shopper\Livewire\Pages\Auth\Login::__invoke
* @see vendor/shopper/framework/src/Livewire/Pages/Auth/Login.php:7
* @route '/cpanel/login'
*/
LoginForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: Login.url(options),
    method: 'get',
})

/**
* @see \Shopper\Livewire\Pages\Auth\Login::__invoke
* @see vendor/shopper/framework/src/Livewire/Pages/Auth/Login.php:7
* @route '/cpanel/login'
*/
LoginForm.head = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: Login.url({
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

Login.form = LoginForm

export default Login