import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition, applyUrlDefaults } from './../../../../../../wayfinder'
/**
* @see \Shopper\Livewire\Pages\Settings\Locations\Edit::__invoke
* @see vendor/shopper/framework/src/Livewire/Pages/Settings/Locations/Edit.php:7
* @route '/cpanel/setting/locations/{inventory}/edit'
*/
const Edit = (args: { inventory: string | number } | [inventory: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: Edit.url(args, options),
    method: 'get',
})

Edit.definition = {
    methods: ["get","head"],
    url: '/cpanel/setting/locations/{inventory}/edit',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \Shopper\Livewire\Pages\Settings\Locations\Edit::__invoke
* @see vendor/shopper/framework/src/Livewire/Pages/Settings/Locations/Edit.php:7
* @route '/cpanel/setting/locations/{inventory}/edit'
*/
Edit.url = (args: { inventory: string | number } | [inventory: string | number ] | string | number, options?: RouteQueryOptions) => {
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

    return Edit.definition.url
            .replace('{inventory}', parsedArgs.inventory.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \Shopper\Livewire\Pages\Settings\Locations\Edit::__invoke
* @see vendor/shopper/framework/src/Livewire/Pages/Settings/Locations/Edit.php:7
* @route '/cpanel/setting/locations/{inventory}/edit'
*/
Edit.get = (args: { inventory: string | number } | [inventory: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: Edit.url(args, options),
    method: 'get',
})

/**
* @see \Shopper\Livewire\Pages\Settings\Locations\Edit::__invoke
* @see vendor/shopper/framework/src/Livewire/Pages/Settings/Locations/Edit.php:7
* @route '/cpanel/setting/locations/{inventory}/edit'
*/
Edit.head = (args: { inventory: string | number } | [inventory: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: Edit.url(args, options),
    method: 'head',
})

/**
* @see \Shopper\Livewire\Pages\Settings\Locations\Edit::__invoke
* @see vendor/shopper/framework/src/Livewire/Pages/Settings/Locations/Edit.php:7
* @route '/cpanel/setting/locations/{inventory}/edit'
*/
const EditForm = (args: { inventory: string | number } | [inventory: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: Edit.url(args, options),
    method: 'get',
})

/**
* @see \Shopper\Livewire\Pages\Settings\Locations\Edit::__invoke
* @see vendor/shopper/framework/src/Livewire/Pages/Settings/Locations/Edit.php:7
* @route '/cpanel/setting/locations/{inventory}/edit'
*/
EditForm.get = (args: { inventory: string | number } | [inventory: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: Edit.url(args, options),
    method: 'get',
})

/**
* @see \Shopper\Livewire\Pages\Settings\Locations\Edit::__invoke
* @see vendor/shopper/framework/src/Livewire/Pages/Settings/Locations/Edit.php:7
* @route '/cpanel/setting/locations/{inventory}/edit'
*/
EditForm.head = (args: { inventory: string | number } | [inventory: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: Edit.url(args, {
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

Edit.form = EditForm

export default Edit