import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition } from './../../../../../wayfinder'
/**
* @see \Shopper\Livewire\Pages\Brand\Index::__invoke
* @see vendor/shopper/framework/src/Livewire/Pages/Brand/Index.php:7
* @route '/cpanel/brands'
*/
const Index = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: Index.url(options),
    method: 'get',
})

Index.definition = {
    methods: ["get","head"],
    url: '/cpanel/brands',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \Shopper\Livewire\Pages\Brand\Index::__invoke
* @see vendor/shopper/framework/src/Livewire/Pages/Brand/Index.php:7
* @route '/cpanel/brands'
*/
Index.url = (options?: RouteQueryOptions) => {
    return Index.definition.url + queryParams(options)
}

/**
* @see \Shopper\Livewire\Pages\Brand\Index::__invoke
* @see vendor/shopper/framework/src/Livewire/Pages/Brand/Index.php:7
* @route '/cpanel/brands'
*/
Index.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: Index.url(options),
    method: 'get',
})

/**
* @see \Shopper\Livewire\Pages\Brand\Index::__invoke
* @see vendor/shopper/framework/src/Livewire/Pages/Brand/Index.php:7
* @route '/cpanel/brands'
*/
Index.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: Index.url(options),
    method: 'head',
})

/**
* @see \Shopper\Livewire\Pages\Brand\Index::__invoke
* @see vendor/shopper/framework/src/Livewire/Pages/Brand/Index.php:7
* @route '/cpanel/brands'
*/
const IndexForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: Index.url(options),
    method: 'get',
})

/**
* @see \Shopper\Livewire\Pages\Brand\Index::__invoke
* @see vendor/shopper/framework/src/Livewire/Pages/Brand/Index.php:7
* @route '/cpanel/brands'
*/
IndexForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: Index.url(options),
    method: 'get',
})

/**
* @see \Shopper\Livewire\Pages\Brand\Index::__invoke
* @see vendor/shopper/framework/src/Livewire/Pages/Brand/Index.php:7
* @route '/cpanel/brands'
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