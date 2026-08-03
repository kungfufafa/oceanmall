import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition } from './../../../../../wayfinder'
/**
* @see \Shopper\Livewire\Pages\Customers\Create::__invoke
* @see vendor/shopper/framework/src/Livewire/Pages/Customers/Create.php:7
* @route '/cpanel/customers/create'
*/
const Create = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: Create.url(options),
    method: 'get',
})

Create.definition = {
    methods: ["get","head"],
    url: '/cpanel/customers/create',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \Shopper\Livewire\Pages\Customers\Create::__invoke
* @see vendor/shopper/framework/src/Livewire/Pages/Customers/Create.php:7
* @route '/cpanel/customers/create'
*/
Create.url = (options?: RouteQueryOptions) => {
    return Create.definition.url + queryParams(options)
}

/**
* @see \Shopper\Livewire\Pages\Customers\Create::__invoke
* @see vendor/shopper/framework/src/Livewire/Pages/Customers/Create.php:7
* @route '/cpanel/customers/create'
*/
Create.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: Create.url(options),
    method: 'get',
})

/**
* @see \Shopper\Livewire\Pages\Customers\Create::__invoke
* @see vendor/shopper/framework/src/Livewire/Pages/Customers/Create.php:7
* @route '/cpanel/customers/create'
*/
Create.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: Create.url(options),
    method: 'head',
})

/**
* @see \Shopper\Livewire\Pages\Customers\Create::__invoke
* @see vendor/shopper/framework/src/Livewire/Pages/Customers/Create.php:7
* @route '/cpanel/customers/create'
*/
const CreateForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: Create.url(options),
    method: 'get',
})

/**
* @see \Shopper\Livewire\Pages\Customers\Create::__invoke
* @see vendor/shopper/framework/src/Livewire/Pages/Customers/Create.php:7
* @route '/cpanel/customers/create'
*/
CreateForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: Create.url(options),
    method: 'get',
})

/**
* @see \Shopper\Livewire\Pages\Customers\Create::__invoke
* @see vendor/shopper/framework/src/Livewire/Pages/Customers/Create.php:7
* @route '/cpanel/customers/create'
*/
CreateForm.head = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: Create.url({
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

Create.form = CreateForm

export default Create