import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition, applyUrlDefaults } from './../../../../wayfinder'
/**
* @see \Shopper\Livewire\Pages\Settings\Locations\Create::__invoke
* @see vendor/shopper/framework/src/Livewire/Pages/Settings/Locations/Create.php:7
* @route '/cpanel/setting/locations/create'
*/
export const create = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: create.url(options),
    method: 'get',
})

create.definition = {
    methods: ["get","head"],
    url: '/cpanel/setting/locations/create',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \Shopper\Livewire\Pages\Settings\Locations\Create::__invoke
* @see vendor/shopper/framework/src/Livewire/Pages/Settings/Locations/Create.php:7
* @route '/cpanel/setting/locations/create'
*/
create.url = (options?: RouteQueryOptions) => {
    return create.definition.url + queryParams(options)
}

/**
* @see \Shopper\Livewire\Pages\Settings\Locations\Create::__invoke
* @see vendor/shopper/framework/src/Livewire/Pages/Settings/Locations/Create.php:7
* @route '/cpanel/setting/locations/create'
*/
create.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: create.url(options),
    method: 'get',
})

/**
* @see \Shopper\Livewire\Pages\Settings\Locations\Create::__invoke
* @see vendor/shopper/framework/src/Livewire/Pages/Settings/Locations/Create.php:7
* @route '/cpanel/setting/locations/create'
*/
create.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: create.url(options),
    method: 'head',
})

/**
* @see \Shopper\Livewire\Pages\Settings\Locations\Create::__invoke
* @see vendor/shopper/framework/src/Livewire/Pages/Settings/Locations/Create.php:7
* @route '/cpanel/setting/locations/create'
*/
const createForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: create.url(options),
    method: 'get',
})

/**
* @see \Shopper\Livewire\Pages\Settings\Locations\Create::__invoke
* @see vendor/shopper/framework/src/Livewire/Pages/Settings/Locations/Create.php:7
* @route '/cpanel/setting/locations/create'
*/
createForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: create.url(options),
    method: 'get',
})

/**
* @see \Shopper\Livewire\Pages\Settings\Locations\Create::__invoke
* @see vendor/shopper/framework/src/Livewire/Pages/Settings/Locations/Create.php:7
* @route '/cpanel/setting/locations/create'
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
* @see \Shopper\Livewire\Pages\Settings\Locations\Edit::__invoke
* @see vendor/shopper/framework/src/Livewire/Pages/Settings/Locations/Edit.php:7
* @route '/cpanel/setting/locations/{inventory}/edit'
*/
export const edit = (args: { inventory: string | number } | [inventory: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: edit.url(args, options),
    method: 'get',
})

edit.definition = {
    methods: ["get","head"],
    url: '/cpanel/setting/locations/{inventory}/edit',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \Shopper\Livewire\Pages\Settings\Locations\Edit::__invoke
* @see vendor/shopper/framework/src/Livewire/Pages/Settings/Locations/Edit.php:7
* @route '/cpanel/setting/locations/{inventory}/edit'
*/
edit.url = (args: { inventory: string | number } | [inventory: string | number ] | string | number, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { inventory: args }
    }

    if (Array.isArray(args)) {
        args = {
            inventory: args[0],
        }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
        inventory: args.inventory,
    }

    return edit.definition.url
            .replace('{inventory}', parsedArgs.inventory.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \Shopper\Livewire\Pages\Settings\Locations\Edit::__invoke
* @see vendor/shopper/framework/src/Livewire/Pages/Settings/Locations/Edit.php:7
* @route '/cpanel/setting/locations/{inventory}/edit'
*/
edit.get = (args: { inventory: string | number } | [inventory: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: edit.url(args, options),
    method: 'get',
})

/**
* @see \Shopper\Livewire\Pages\Settings\Locations\Edit::__invoke
* @see vendor/shopper/framework/src/Livewire/Pages/Settings/Locations/Edit.php:7
* @route '/cpanel/setting/locations/{inventory}/edit'
*/
edit.head = (args: { inventory: string | number } | [inventory: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: edit.url(args, options),
    method: 'head',
})

/**
* @see \Shopper\Livewire\Pages\Settings\Locations\Edit::__invoke
* @see vendor/shopper/framework/src/Livewire/Pages/Settings/Locations/Edit.php:7
* @route '/cpanel/setting/locations/{inventory}/edit'
*/
const editForm = (args: { inventory: string | number } | [inventory: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: edit.url(args, options),
    method: 'get',
})

/**
* @see \Shopper\Livewire\Pages\Settings\Locations\Edit::__invoke
* @see vendor/shopper/framework/src/Livewire/Pages/Settings/Locations/Edit.php:7
* @route '/cpanel/setting/locations/{inventory}/edit'
*/
editForm.get = (args: { inventory: string | number } | [inventory: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: edit.url(args, options),
    method: 'get',
})

/**
* @see \Shopper\Livewire\Pages\Settings\Locations\Edit::__invoke
* @see vendor/shopper/framework/src/Livewire/Pages/Settings/Locations/Edit.php:7
* @route '/cpanel/setting/locations/{inventory}/edit'
*/
editForm.head = (args: { inventory: string | number } | [inventory: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: edit.url(args, {
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

edit.form = editForm

const locations = {
    create: Object.assign(create, create),
    edit: Object.assign(edit, edit),
}

export default locations