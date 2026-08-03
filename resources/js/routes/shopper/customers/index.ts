import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition, applyUrlDefaults } from './../../../wayfinder'
/**
* @see \Shopper\Livewire\Pages\Customers\Index::__invoke
* @see vendor/shopper/framework/src/Livewire/Pages/Customers/Index.php:7
* @route '/cpanel/customers'
*/
export const index = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
})

index.definition = {
    methods: ["get","head"],
    url: '/cpanel/customers',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \Shopper\Livewire\Pages\Customers\Index::__invoke
* @see vendor/shopper/framework/src/Livewire/Pages/Customers/Index.php:7
* @route '/cpanel/customers'
*/
index.url = (options?: RouteQueryOptions) => {
    return index.definition.url + queryParams(options)
}

/**
* @see \Shopper\Livewire\Pages\Customers\Index::__invoke
* @see vendor/shopper/framework/src/Livewire/Pages/Customers/Index.php:7
* @route '/cpanel/customers'
*/
index.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
})

/**
* @see \Shopper\Livewire\Pages\Customers\Index::__invoke
* @see vendor/shopper/framework/src/Livewire/Pages/Customers/Index.php:7
* @route '/cpanel/customers'
*/
index.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: index.url(options),
    method: 'head',
})

/**
* @see \Shopper\Livewire\Pages\Customers\Index::__invoke
* @see vendor/shopper/framework/src/Livewire/Pages/Customers/Index.php:7
* @route '/cpanel/customers'
*/
const indexForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: index.url(options),
    method: 'get',
})

/**
* @see \Shopper\Livewire\Pages\Customers\Index::__invoke
* @see vendor/shopper/framework/src/Livewire/Pages/Customers/Index.php:7
* @route '/cpanel/customers'
*/
indexForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: index.url(options),
    method: 'get',
})

/**
* @see \Shopper\Livewire\Pages\Customers\Index::__invoke
* @see vendor/shopper/framework/src/Livewire/Pages/Customers/Index.php:7
* @route '/cpanel/customers'
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
* @see \Shopper\Livewire\Pages\Customers\Create::__invoke
* @see vendor/shopper/framework/src/Livewire/Pages/Customers/Create.php:7
* @route '/cpanel/customers/create'
*/
export const create = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: create.url(options),
    method: 'get',
})

create.definition = {
    methods: ["get","head"],
    url: '/cpanel/customers/create',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \Shopper\Livewire\Pages\Customers\Create::__invoke
* @see vendor/shopper/framework/src/Livewire/Pages/Customers/Create.php:7
* @route '/cpanel/customers/create'
*/
create.url = (options?: RouteQueryOptions) => {
    return create.definition.url + queryParams(options)
}

/**
* @see \Shopper\Livewire\Pages\Customers\Create::__invoke
* @see vendor/shopper/framework/src/Livewire/Pages/Customers/Create.php:7
* @route '/cpanel/customers/create'
*/
create.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: create.url(options),
    method: 'get',
})

/**
* @see \Shopper\Livewire\Pages\Customers\Create::__invoke
* @see vendor/shopper/framework/src/Livewire/Pages/Customers/Create.php:7
* @route '/cpanel/customers/create'
*/
create.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: create.url(options),
    method: 'head',
})

/**
* @see \Shopper\Livewire\Pages\Customers\Create::__invoke
* @see vendor/shopper/framework/src/Livewire/Pages/Customers/Create.php:7
* @route '/cpanel/customers/create'
*/
const createForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: create.url(options),
    method: 'get',
})

/**
* @see \Shopper\Livewire\Pages\Customers\Create::__invoke
* @see vendor/shopper/framework/src/Livewire/Pages/Customers/Create.php:7
* @route '/cpanel/customers/create'
*/
createForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: create.url(options),
    method: 'get',
})

/**
* @see \Shopper\Livewire\Pages\Customers\Create::__invoke
* @see vendor/shopper/framework/src/Livewire/Pages/Customers/Create.php:7
* @route '/cpanel/customers/create'
*/
createForm.head = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: create.url({
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

create.form = createForm

/**
* @see \Shopper\Livewire\Pages\Customers\Show::__invoke
* @see vendor/shopper/framework/src/Livewire/Pages/Customers/Show.php:7
* @route '/cpanel/customers/{user}/show'
*/
export const show = (args: { user: string | number } | [user: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: show.url(args, options),
    method: 'get',
})

show.definition = {
    methods: ["get","head"],
    url: '/cpanel/customers/{user}/show',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \Shopper\Livewire\Pages\Customers\Show::__invoke
* @see vendor/shopper/framework/src/Livewire/Pages/Customers/Show.php:7
* @route '/cpanel/customers/{user}/show'
*/
show.url = (args: { user: string | number } | [user: string | number ] | string | number, options?: RouteQueryOptions) => {
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

    return show.definition.url
            .replace('{user}', parsedArgs.user.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \Shopper\Livewire\Pages\Customers\Show::__invoke
* @see vendor/shopper/framework/src/Livewire/Pages/Customers/Show.php:7
* @route '/cpanel/customers/{user}/show'
*/
show.get = (args: { user: string | number } | [user: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: show.url(args, options),
    method: 'get',
})

/**
* @see \Shopper\Livewire\Pages\Customers\Show::__invoke
* @see vendor/shopper/framework/src/Livewire/Pages/Customers/Show.php:7
* @route '/cpanel/customers/{user}/show'
*/
show.head = (args: { user: string | number } | [user: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: show.url(args, options),
    method: 'head',
})

/**
* @see \Shopper\Livewire\Pages\Customers\Show::__invoke
* @see vendor/shopper/framework/src/Livewire/Pages/Customers/Show.php:7
* @route '/cpanel/customers/{user}/show'
*/
const showForm = (args: { user: string | number } | [user: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: show.url(args, options),
    method: 'get',
})

/**
* @see \Shopper\Livewire\Pages\Customers\Show::__invoke
* @see vendor/shopper/framework/src/Livewire/Pages/Customers/Show.php:7
* @route '/cpanel/customers/{user}/show'
*/
showForm.get = (args: { user: string | number } | [user: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: show.url(args, options),
    method: 'get',
})

/**
* @see \Shopper\Livewire\Pages\Customers\Show::__invoke
* @see vendor/shopper/framework/src/Livewire/Pages/Customers/Show.php:7
* @route '/cpanel/customers/{user}/show'
*/
showForm.head = (args: { user: string | number } | [user: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: show.url(args, {
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

show.form = showForm

const customers = {
    index: Object.assign(index, index),
    create: Object.assign(create, create),
    show: Object.assign(show, show),
}

export default customers