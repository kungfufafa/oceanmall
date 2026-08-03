import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition, applyUrlDefaults } from './../../../wayfinder'
/**
* @see \Shopper\Livewire\Pages\Collection\Index::__invoke
* @see vendor/shopper/framework/src/Livewire/Pages/Collection/Index.php:7
* @route '/cpanel/collections'
*/
export const index = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
})

index.definition = {
    methods: ["get","head"],
    url: '/cpanel/collections',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \Shopper\Livewire\Pages\Collection\Index::__invoke
* @see vendor/shopper/framework/src/Livewire/Pages/Collection/Index.php:7
* @route '/cpanel/collections'
*/
index.url = (options?: RouteQueryOptions) => {
    return index.definition.url + queryParams(options)
}

/**
* @see \Shopper\Livewire\Pages\Collection\Index::__invoke
* @see vendor/shopper/framework/src/Livewire/Pages/Collection/Index.php:7
* @route '/cpanel/collections'
*/
index.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
})

/**
* @see \Shopper\Livewire\Pages\Collection\Index::__invoke
* @see vendor/shopper/framework/src/Livewire/Pages/Collection/Index.php:7
* @route '/cpanel/collections'
*/
index.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: index.url(options),
    method: 'head',
})

/**
* @see \Shopper\Livewire\Pages\Collection\Index::__invoke
* @see vendor/shopper/framework/src/Livewire/Pages/Collection/Index.php:7
* @route '/cpanel/collections'
*/
const indexForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: index.url(options),
    method: 'get',
})

/**
* @see \Shopper\Livewire\Pages\Collection\Index::__invoke
* @see vendor/shopper/framework/src/Livewire/Pages/Collection/Index.php:7
* @route '/cpanel/collections'
*/
indexForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: index.url(options),
    method: 'get',
})

/**
* @see \Shopper\Livewire\Pages\Collection\Index::__invoke
* @see vendor/shopper/framework/src/Livewire/Pages/Collection/Index.php:7
* @route '/cpanel/collections'
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
* @see \Shopper\Livewire\Pages\Collection\Edit::__invoke
* @see vendor/shopper/framework/src/Livewire/Pages/Collection/Edit.php:7
* @route '/cpanel/collections/{collection}/edit'
*/
export const edit = (args: { collection: string | number } | [collection: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: edit.url(args, options),
    method: 'get',
})

edit.definition = {
    methods: ["get","head"],
    url: '/cpanel/collections/{collection}/edit',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \Shopper\Livewire\Pages\Collection\Edit::__invoke
* @see vendor/shopper/framework/src/Livewire/Pages/Collection/Edit.php:7
* @route '/cpanel/collections/{collection}/edit'
*/
edit.url = (args: { collection: string | number } | [collection: string | number ] | string | number, options?: RouteQueryOptions) => {
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

    return edit.definition.url
            .replace('{collection}', parsedArgs.collection.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \Shopper\Livewire\Pages\Collection\Edit::__invoke
* @see vendor/shopper/framework/src/Livewire/Pages/Collection/Edit.php:7
* @route '/cpanel/collections/{collection}/edit'
*/
edit.get = (args: { collection: string | number } | [collection: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: edit.url(args, options),
    method: 'get',
})

/**
* @see \Shopper\Livewire\Pages\Collection\Edit::__invoke
* @see vendor/shopper/framework/src/Livewire/Pages/Collection/Edit.php:7
* @route '/cpanel/collections/{collection}/edit'
*/
edit.head = (args: { collection: string | number } | [collection: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: edit.url(args, options),
    method: 'head',
})

/**
* @see \Shopper\Livewire\Pages\Collection\Edit::__invoke
* @see vendor/shopper/framework/src/Livewire/Pages/Collection/Edit.php:7
* @route '/cpanel/collections/{collection}/edit'
*/
const editForm = (args: { collection: string | number } | [collection: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: edit.url(args, options),
    method: 'get',
})

/**
* @see \Shopper\Livewire\Pages\Collection\Edit::__invoke
* @see vendor/shopper/framework/src/Livewire/Pages/Collection/Edit.php:7
* @route '/cpanel/collections/{collection}/edit'
*/
editForm.get = (args: { collection: string | number } | [collection: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: edit.url(args, options),
    method: 'get',
})

/**
* @see \Shopper\Livewire\Pages\Collection\Edit::__invoke
* @see vendor/shopper/framework/src/Livewire/Pages/Collection/Edit.php:7
* @route '/cpanel/collections/{collection}/edit'
*/
editForm.head = (args: { collection: string | number } | [collection: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: edit.url(args, {
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

edit.form = editForm

const collections = {
    index: Object.assign(index, index),
    edit: Object.assign(edit, edit),
}

export default collections