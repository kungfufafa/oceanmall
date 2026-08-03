import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition, applyUrlDefaults } from './../../../../../wayfinder'
/**
* @see \Shopper\Livewire\Pages\Customers\Show::__invoke
* @see vendor/shopper/framework/src/Livewire/Pages/Customers/Show.php:7
* @route '/cpanel/customers/{user}/show'
*/
const Show = (args: { user: string | number } | [user: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: Show.url(args, options),
    method: 'get',
})

Show.definition = {
    methods: ["get","head"],
    url: '/cpanel/customers/{user}/show',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \Shopper\Livewire\Pages\Customers\Show::__invoke
* @see vendor/shopper/framework/src/Livewire/Pages/Customers/Show.php:7
* @route '/cpanel/customers/{user}/show'
*/
Show.url = (args: { user: string | number } | [user: string | number ] | string | number, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { user: args }
    }

    if (Array.isArray(args)) {
        args = {
            user: args[0],
        }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
        user: args.user,
    }

    return Show.definition.url
            .replace('{user}', parsedArgs.user.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \Shopper\Livewire\Pages\Customers\Show::__invoke
* @see vendor/shopper/framework/src/Livewire/Pages/Customers/Show.php:7
* @route '/cpanel/customers/{user}/show'
*/
Show.get = (args: { user: string | number } | [user: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: Show.url(args, options),
    method: 'get',
})

/**
* @see \Shopper\Livewire\Pages\Customers\Show::__invoke
* @see vendor/shopper/framework/src/Livewire/Pages/Customers/Show.php:7
* @route '/cpanel/customers/{user}/show'
*/
Show.head = (args: { user: string | number } | [user: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: Show.url(args, options),
    method: 'head',
})

/**
* @see \Shopper\Livewire\Pages\Customers\Show::__invoke
* @see vendor/shopper/framework/src/Livewire/Pages/Customers/Show.php:7
* @route '/cpanel/customers/{user}/show'
*/
const ShowForm = (args: { user: string | number } | [user: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: Show.url(args, options),
    method: 'get',
})

/**
* @see \Shopper\Livewire\Pages\Customers\Show::__invoke
* @see vendor/shopper/framework/src/Livewire/Pages/Customers/Show.php:7
* @route '/cpanel/customers/{user}/show'
*/
ShowForm.get = (args: { user: string | number } | [user: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: Show.url(args, options),
    method: 'get',
})

/**
* @see \Shopper\Livewire\Pages\Customers\Show::__invoke
* @see vendor/shopper/framework/src/Livewire/Pages/Customers/Show.php:7
* @route '/cpanel/customers/{user}/show'
*/
ShowForm.head = (args: { user: string | number } | [user: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: Show.url(args, {
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

Show.form = ShowForm

export default Show