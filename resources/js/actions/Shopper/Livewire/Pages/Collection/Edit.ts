import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition, applyUrlDefaults } from './../../../../../wayfinder'
/**
* @see \Shopper\Livewire\Pages\Collection\Edit::__invoke
* @see vendor/shopper/framework/src/Livewire/Pages/Collection/Edit.php:7
* @route '/cpanel/collections/{collection}/edit'
*/
const Edit = (args: { collection: string | number } | [collection: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: Edit.url(args, options),
    method: 'get',
})

Edit.definition = {
    methods: ["get","head"],
    url: '/cpanel/collections/{collection}/edit',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \Shopper\Livewire\Pages\Collection\Edit::__invoke
* @see vendor/shopper/framework/src/Livewire/Pages/Collection/Edit.php:7
* @route '/cpanel/collections/{collection}/edit'
*/
Edit.url = (args: { collection: string | number } | [collection: string | number ] | string | number, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { collection: args }
    }

    if (Array.isArray(args)) {
        args = {
            collection: args[0],
        }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
        collection: args.collection,
    }

    return Edit.definition.url
            .replace('{collection}', parsedArgs.collection.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \Shopper\Livewire\Pages\Collection\Edit::__invoke
* @see vendor/shopper/framework/src/Livewire/Pages/Collection/Edit.php:7
* @route '/cpanel/collections/{collection}/edit'
*/
Edit.get = (args: { collection: string | number } | [collection: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: Edit.url(args, options),
    method: 'get',
})

/**
* @see \Shopper\Livewire\Pages\Collection\Edit::__invoke
* @see vendor/shopper/framework/src/Livewire/Pages/Collection/Edit.php:7
* @route '/cpanel/collections/{collection}/edit'
*/
Edit.head = (args: { collection: string | number } | [collection: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: Edit.url(args, options),
    method: 'head',
})

/**
* @see \Shopper\Livewire\Pages\Collection\Edit::__invoke
* @see vendor/shopper/framework/src/Livewire/Pages/Collection/Edit.php:7
* @route '/cpanel/collections/{collection}/edit'
*/
const EditForm = (args: { collection: string | number } | [collection: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: Edit.url(args, options),
    method: 'get',
})

/**
* @see \Shopper\Livewire\Pages\Collection\Edit::__invoke
* @see vendor/shopper/framework/src/Livewire/Pages/Collection/Edit.php:7
* @route '/cpanel/collections/{collection}/edit'
*/
EditForm.get = (args: { collection: string | number } | [collection: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: Edit.url(args, options),
    method: 'get',
})

/**
* @see \Shopper\Livewire\Pages\Collection\Edit::__invoke
* @see vendor/shopper/framework/src/Livewire/Pages/Collection/Edit.php:7
* @route '/cpanel/collections/{collection}/edit'
*/
EditForm.head = (args: { collection: string | number } | [collection: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
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