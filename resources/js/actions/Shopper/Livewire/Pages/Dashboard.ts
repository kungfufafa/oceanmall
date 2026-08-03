import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition } from './../../../../wayfinder'
/**
* @see \Shopper\Livewire\Pages\Dashboard::__invoke
* @see vendor/shopper/framework/src/Livewire/Pages/Dashboard.php:7
* @route '/cpanel/dashboard'
*/
const Dashboard = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: Dashboard.url(options),
    method: 'get',
})

Dashboard.definition = {
    methods: ["get","head"],
    url: '/cpanel/dashboard',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \Shopper\Livewire\Pages\Dashboard::__invoke
* @see vendor/shopper/framework/src/Livewire/Pages/Dashboard.php:7
* @route '/cpanel/dashboard'
*/
Dashboard.url = (options?: RouteQueryOptions) => {
    return Dashboard.definition.url + queryParams(options)
}

/**
* @see \Shopper\Livewire\Pages\Dashboard::__invoke
* @see vendor/shopper/framework/src/Livewire/Pages/Dashboard.php:7
* @route '/cpanel/dashboard'
*/
Dashboard.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: Dashboard.url(options),
    method: 'get',
})

/**
* @see \Shopper\Livewire\Pages\Dashboard::__invoke
* @see vendor/shopper/framework/src/Livewire/Pages/Dashboard.php:7
* @route '/cpanel/dashboard'
*/
Dashboard.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: Dashboard.url(options),
    method: 'head',
})

/**
* @see \Shopper\Livewire\Pages\Dashboard::__invoke
* @see vendor/shopper/framework/src/Livewire/Pages/Dashboard.php:7
* @route '/cpanel/dashboard'
*/
const DashboardForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: Dashboard.url(options),
    method: 'get',
})

/**
* @see \Shopper\Livewire\Pages\Dashboard::__invoke
* @see vendor/shopper/framework/src/Livewire/Pages/Dashboard.php:7
* @route '/cpanel/dashboard'
*/
DashboardForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: Dashboard.url(options),
    method: 'get',
})

/**
* @see \Shopper\Livewire\Pages\Dashboard::__invoke
* @see vendor/shopper/framework/src/Livewire/Pages/Dashboard.php:7
* @route '/cpanel/dashboard'
*/
DashboardForm.head = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: Dashboard.url({
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

Dashboard.form = DashboardForm

export default Dashboard