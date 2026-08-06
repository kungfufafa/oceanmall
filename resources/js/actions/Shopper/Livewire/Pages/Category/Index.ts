import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition } from './../../../../../wayfinder'
/**
* @see \Shopper\Livewire\Pages\Category\Index::__invoke
* @see vendor/shopper/framework/src/Livewire/Pages/Category/Index.php:7
* @route '/cpanel/categories'
*/
const Index = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: Index.url(options),
    method: 'get',
})

Index.definition = {
    methods: ["get","head"],
    url: '/cpanel/categories',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \Shopper\Livewire\Pages\Category\Index::__invoke
* @see vendor/shopper/framework/src/Livewire/Pages/Category/Index.php:7
* @route '/cpanel/categories'
*/
Index.url = (options?: RouteQueryOptions) => {
    return Index.definition.url + queryParams(options)
}

/**
* @see \Shopper\Livewire\Pages\Category\Index::__invoke
* @see vendor/shopper/framework/src/Livewire/Pages/Category/Index.php:7
* @route '/cpanel/categories'
*/
Index.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: Index.url(options),
    method: 'get',
})

/**
* @see \Shopper\Livewire\Pages\Category\Index::__invoke
* @see vendor/shopper/framework/src/Livewire/Pages/Category/Index.php:7
* @route '/cpanel/categories'
*/
Index.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: Index.url(options),
    method: 'head',
})

/**
* @see \Shopper\Livewire\Pages\Category\Index::__invoke
* @see vendor/shopper/framework/src/Livewire/Pages/Category/Index.php:7
* @route '/cpanel/categories'
*/
const IndexForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: Index.url(options),
    method: 'get',
})

/**
* @see \Shopper\Livewire\Pages\Category\Index::__invoke
* @see vendor/shopper/framework/src/Livewire/Pages/Category/Index.php:7
* @route '/cpanel/categories'
*/
IndexForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: Index.url(options),
    method: 'get',
})

/**
* @see \Shopper\Livewire\Pages\Category\Index::__invoke
* @see vendor/shopper/framework/src/Livewire/Pages/Category/Index.php:7
* @route '/cpanel/categories'
*/
IndexForm.head = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: Index.url({
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

Index.form = IndexForm

export default Index