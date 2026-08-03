import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition, applyUrlDefaults } from './../../wayfinder'
import password from './password'
import settings from './settings'
import customers from './customers'
import orders from './orders'
import products from './products'
import attributes from './attributes'
import tags from './tags'
import brands from './brands'
import categories from './categories'
import collections from './collections'
import discounts from './discounts'
import reviews from './reviews'
/**
* @see \Shopper\Livewire\Pages\Auth\Login::__invoke
* @see vendor/shopper/framework/src/Livewire/Pages/Auth/Login.php:7
* @route '/cpanel/login'
*/
export const login = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: login.url(options),
    method: 'get',
})

login.definition = {
    methods: ["get","head"],
    url: '/cpanel/login',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \Shopper\Livewire\Pages\Auth\Login::__invoke
* @see vendor/shopper/framework/src/Livewire/Pages/Auth/Login.php:7
* @route '/cpanel/login'
*/
login.url = (options?: RouteQueryOptions) => {
    return login.definition.url + queryParams(options)
}

/**
* @see \Shopper\Livewire\Pages\Auth\Login::__invoke
* @see vendor/shopper/framework/src/Livewire/Pages/Auth/Login.php:7
* @route '/cpanel/login'
*/
login.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: login.url(options),
    method: 'get',
})

/**
* @see \Shopper\Livewire\Pages\Auth\Login::__invoke
* @see vendor/shopper/framework/src/Livewire/Pages/Auth/Login.php:7
* @route '/cpanel/login'
*/
login.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: login.url(options),
    method: 'head',
})

/**
* @see \Shopper\Livewire\Pages\Auth\Login::__invoke
* @see vendor/shopper/framework/src/Livewire/Pages/Auth/Login.php:7
* @route '/cpanel/login'
*/
const loginForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: login.url(options),
    method: 'get',
})

/**
* @see \Shopper\Livewire\Pages\Auth\Login::__invoke
* @see vendor/shopper/framework/src/Livewire/Pages/Auth/Login.php:7
* @route '/cpanel/login'
*/
loginForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: login.url(options),
    method: 'get',
})

/**
* @see \Shopper\Livewire\Pages\Auth\Login::__invoke
* @see vendor/shopper/framework/src/Livewire/Pages/Auth/Login.php:7
* @route '/cpanel/login'
*/
loginForm.head = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: login.url({
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

login.form = loginForm

/**
* @see \Shopper\Http\Controllers\AssetController::__invoke
* @see vendor/shopper/framework/src/Http/Controllers/AssetController.php:11
* @route '/cpanel/assets/{file}'
*/
export const asset = (args: { file: string | number } | [file: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: asset.url(args, options),
    method: 'get',
})

asset.definition = {
    methods: ["get","head"],
    url: '/cpanel/assets/{file}',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \Shopper\Http\Controllers\AssetController::__invoke
* @see vendor/shopper/framework/src/Http/Controllers/AssetController.php:11
* @route '/cpanel/assets/{file}'
*/
asset.url = (args: { file: string | number } | [file: string | number ] | string | number, options?: RouteQueryOptions) => {
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

    return asset.definition.url
            .replace('{file}', parsedArgs.file.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \Shopper\Http\Controllers\AssetController::__invoke
* @see vendor/shopper/framework/src/Http/Controllers/AssetController.php:11
* @route '/cpanel/assets/{file}'
*/
asset.get = (args: { file: string | number } | [file: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: asset.url(args, options),
    method: 'get',
})

/**
* @see \Shopper\Http\Controllers\AssetController::__invoke
* @see vendor/shopper/framework/src/Http/Controllers/AssetController.php:11
* @route '/cpanel/assets/{file}'
*/
asset.head = (args: { file: string | number } | [file: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: asset.url(args, options),
    method: 'head',
})

/**
* @see \Shopper\Http\Controllers\AssetController::__invoke
* @see vendor/shopper/framework/src/Http/Controllers/AssetController.php:11
* @route '/cpanel/assets/{file}'
*/
const assetForm = (args: { file: string | number } | [file: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: asset.url(args, options),
    method: 'get',
})

/**
* @see \Shopper\Http\Controllers\AssetController::__invoke
* @see vendor/shopper/framework/src/Http/Controllers/AssetController.php:11
* @route '/cpanel/assets/{file}'
*/
assetForm.get = (args: { file: string | number } | [file: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: asset.url(args, options),
    method: 'get',
})

/**
* @see \Shopper\Http\Controllers\AssetController::__invoke
* @see vendor/shopper/framework/src/Http/Controllers/AssetController.php:11
* @route '/cpanel/assets/{file}'
*/
assetForm.head = (args: { file: string | number } | [file: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: asset.url(args, {
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

asset.form = assetForm

/**
* @see vendor/shopper/framework/routes/web.php:49
* @route '/cpanel/logout'
*/
export const logout = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: logout.url(options),
    method: 'post',
})

logout.definition = {
    methods: ["post"],
    url: '/cpanel/logout',
} satisfies RouteDefinition<["post"]>

/**
* @see vendor/shopper/framework/routes/web.php:49
* @route '/cpanel/logout'
*/
logout.url = (options?: RouteQueryOptions) => {
    return logout.definition.url + queryParams(options)
}

/**
* @see vendor/shopper/framework/routes/web.php:49
* @route '/cpanel/logout'
*/
logout.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: logout.url(options),
    method: 'post',
})

/**
* @see vendor/shopper/framework/routes/web.php:49
* @route '/cpanel/logout'
*/
const logoutForm = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: logout.url(options),
    method: 'post',
})

/**
* @see vendor/shopper/framework/routes/web.php:49
* @route '/cpanel/logout'
*/
logoutForm.post = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: logout.url(options),
    method: 'post',
})

logout.form = logoutForm

/**
* @see \Shopper\Livewire\Pages\Initialization::__invoke
* @see vendor/shopper/framework/src/Livewire/Pages/Initialization.php:7
* @route '/cpanel/initialize'
*/
export const initialize = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: initialize.url(options),
    method: 'get',
})

initialize.definition = {
    methods: ["get","head"],
    url: '/cpanel/initialize',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \Shopper\Livewire\Pages\Initialization::__invoke
* @see vendor/shopper/framework/src/Livewire/Pages/Initialization.php:7
* @route '/cpanel/initialize'
*/
initialize.url = (options?: RouteQueryOptions) => {
    return initialize.definition.url + queryParams(options)
}

/**
* @see \Shopper\Livewire\Pages\Initialization::__invoke
* @see vendor/shopper/framework/src/Livewire/Pages/Initialization.php:7
* @route '/cpanel/initialize'
*/
initialize.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: initialize.url(options),
    method: 'get',
})

/**
* @see \Shopper\Livewire\Pages\Initialization::__invoke
* @see vendor/shopper/framework/src/Livewire/Pages/Initialization.php:7
* @route '/cpanel/initialize'
*/
initialize.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: initialize.url(options),
    method: 'head',
})

/**
* @see \Shopper\Livewire\Pages\Initialization::__invoke
* @see vendor/shopper/framework/src/Livewire/Pages/Initialization.php:7
* @route '/cpanel/initialize'
*/
const initializeForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: initialize.url(options),
    method: 'get',
})

/**
* @see \Shopper\Livewire\Pages\Initialization::__invoke
* @see vendor/shopper/framework/src/Livewire/Pages/Initialization.php:7
* @route '/cpanel/initialize'
*/
initializeForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: initialize.url(options),
    method: 'get',
})

/**
* @see \Shopper\Livewire\Pages\Initialization::__invoke
* @see vendor/shopper/framework/src/Livewire/Pages/Initialization.php:7
* @route '/cpanel/initialize'
*/
initializeForm.head = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: initialize.url({
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

initialize.form = initializeForm

/**
* @see \Shopper\Livewire\Pages\Forbidden::__invoke
* @see vendor/shopper/framework/src/Livewire/Pages/Forbidden.php:7
* @route '/cpanel/forbidden'
*/
export const forbidden = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: forbidden.url(options),
    method: 'get',
})

forbidden.definition = {
    methods: ["get","head"],
    url: '/cpanel/forbidden',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \Shopper\Livewire\Pages\Forbidden::__invoke
* @see vendor/shopper/framework/src/Livewire/Pages/Forbidden.php:7
* @route '/cpanel/forbidden'
*/
forbidden.url = (options?: RouteQueryOptions) => {
    return forbidden.definition.url + queryParams(options)
}

/**
* @see \Shopper\Livewire\Pages\Forbidden::__invoke
* @see vendor/shopper/framework/src/Livewire/Pages/Forbidden.php:7
* @route '/cpanel/forbidden'
*/
forbidden.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: forbidden.url(options),
    method: 'get',
})

/**
* @see \Shopper\Livewire\Pages\Forbidden::__invoke
* @see vendor/shopper/framework/src/Livewire/Pages/Forbidden.php:7
* @route '/cpanel/forbidden'
*/
forbidden.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: forbidden.url(options),
    method: 'head',
})

/**
* @see \Shopper\Livewire\Pages\Forbidden::__invoke
* @see vendor/shopper/framework/src/Livewire/Pages/Forbidden.php:7
* @route '/cpanel/forbidden'
*/
const forbiddenForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: forbidden.url(options),
    method: 'get',
})

/**
* @see \Shopper\Livewire\Pages\Forbidden::__invoke
* @see vendor/shopper/framework/src/Livewire/Pages/Forbidden.php:7
* @route '/cpanel/forbidden'
*/
forbiddenForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: forbidden.url(options),
    method: 'get',
})

/**
* @see \Shopper\Livewire\Pages\Forbidden::__invoke
* @see vendor/shopper/framework/src/Livewire/Pages/Forbidden.php:7
* @route '/cpanel/forbidden'
*/
forbiddenForm.head = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: forbidden.url({
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

forbidden.form = forbiddenForm

/**
* @see \Shopper\Livewire\Pages\Dashboard::__invoke
* @see vendor/shopper/framework/src/Livewire/Pages/Dashboard.php:7
* @route '/cpanel/dashboard'
*/
export const dashboard = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: dashboard.url(options),
    method: 'get',
})

dashboard.definition = {
    methods: ["get","head"],
    url: '/cpanel/dashboard',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \Shopper\Livewire\Pages\Dashboard::__invoke
* @see vendor/shopper/framework/src/Livewire/Pages/Dashboard.php:7
* @route '/cpanel/dashboard'
*/
dashboard.url = (options?: RouteQueryOptions) => {
    return dashboard.definition.url + queryParams(options)
}

/**
* @see \Shopper\Livewire\Pages\Dashboard::__invoke
* @see vendor/shopper/framework/src/Livewire/Pages/Dashboard.php:7
* @route '/cpanel/dashboard'
*/
dashboard.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: dashboard.url(options),
    method: 'get',
})

/**
* @see \Shopper\Livewire\Pages\Dashboard::__invoke
* @see vendor/shopper/framework/src/Livewire/Pages/Dashboard.php:7
* @route '/cpanel/dashboard'
*/
dashboard.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: dashboard.url(options),
    method: 'head',
})

/**
* @see \Shopper\Livewire\Pages\Dashboard::__invoke
* @see vendor/shopper/framework/src/Livewire/Pages/Dashboard.php:7
* @route '/cpanel/dashboard'
*/
const dashboardForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: dashboard.url(options),
    method: 'get',
})

/**
* @see \Shopper\Livewire\Pages\Dashboard::__invoke
* @see vendor/shopper/framework/src/Livewire/Pages/Dashboard.php:7
* @route '/cpanel/dashboard'
*/
dashboardForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: dashboard.url(options),
    method: 'get',
})

/**
* @see \Shopper\Livewire\Pages\Dashboard::__invoke
* @see vendor/shopper/framework/src/Livewire/Pages/Dashboard.php:7
* @route '/cpanel/dashboard'
*/
dashboardForm.head = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: dashboard.url({
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

dashboard.form = dashboardForm

/**
* @see \Shopper\Livewire\Pages\Account::__invoke
* @see vendor/shopper/framework/src/Livewire/Pages/Account.php:7
* @route '/cpanel/profile'
*/
export const profile = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: profile.url(options),
    method: 'get',
})

profile.definition = {
    methods: ["get","head"],
    url: '/cpanel/profile',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \Shopper\Livewire\Pages\Account::__invoke
* @see vendor/shopper/framework/src/Livewire/Pages/Account.php:7
* @route '/cpanel/profile'
*/
profile.url = (options?: RouteQueryOptions) => {
    return profile.definition.url + queryParams(options)
}

/**
* @see \Shopper\Livewire\Pages\Account::__invoke
* @see vendor/shopper/framework/src/Livewire/Pages/Account.php:7
* @route '/cpanel/profile'
*/
profile.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: profile.url(options),
    method: 'get',
})

/**
* @see \Shopper\Livewire\Pages\Account::__invoke
* @see vendor/shopper/framework/src/Livewire/Pages/Account.php:7
* @route '/cpanel/profile'
*/
profile.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: profile.url(options),
    method: 'head',
})

/**
* @see \Shopper\Livewire\Pages\Account::__invoke
* @see vendor/shopper/framework/src/Livewire/Pages/Account.php:7
* @route '/cpanel/profile'
*/
const profileForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: profile.url(options),
    method: 'get',
})

/**
* @see \Shopper\Livewire\Pages\Account::__invoke
* @see vendor/shopper/framework/src/Livewire/Pages/Account.php:7
* @route '/cpanel/profile'
*/
profileForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: profile.url(options),
    method: 'get',
})

/**
* @see \Shopper\Livewire\Pages\Account::__invoke
* @see vendor/shopper/framework/src/Livewire/Pages/Account.php:7
* @route '/cpanel/profile'
*/
profileForm.head = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: profile.url({
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

profile.form = profileForm

const shopper = {
    login: Object.assign(login, login),
    password: Object.assign(password, password),
    asset: Object.assign(asset, asset),
    logout: Object.assign(logout, logout),
    initialize: Object.assign(initialize, initialize),
    forbidden: Object.assign(forbidden, forbidden),
    dashboard: Object.assign(dashboard, dashboard),
    profile: Object.assign(profile, profile),
    settings: Object.assign(settings, settings),
    customers: Object.assign(customers, customers),
    orders: Object.assign(orders, orders),
    products: Object.assign(products, products),
    attributes: Object.assign(attributes, attributes),
    tags: Object.assign(tags, tags),
    brands: Object.assign(brands, brands),
    categories: Object.assign(categories, categories),
    collections: Object.assign(collections, collections),
    discounts: Object.assign(discounts, discounts),
    reviews: Object.assign(reviews, reviews),
}

export default shopper