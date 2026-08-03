import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition, applyUrlDefaults } from './../../../../../wayfinder'
/**
* @see \Shopper\Livewire\Pages\Product\Edit::__invoke
* @see vendor/shopper/framework/src/Livewire/Pages/Product/Edit.php:7
* @route '/cpanel/products/{product}/edit'
*/
const Edit = (args: { product: string | number } | [product: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: Edit.url(args, options),
    method: 'get',
})

Edit.definition = {
    methods: ["get","head"],
    url: '/cpanel/products/{product}/edit',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \Shopper\Livewire\Pages\Product\Edit::__invoke
* @see vendor/shopper/framework/src/Livewire/Pages/Product/Edit.php:7
* @route '/cpanel/products/{product}/edit'
*/
Edit.url = (args: { product: string | number } | [product: string | number ] | string | number, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { product: args }
    }

    if (Array.isArray(args)) {
        args = {
            product: args[0],
        }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
        product: args.product,
    }

    return Edit.definition.url
            .replace('{product}', parsedArgs.product.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \Shopper\Livewire\Pages\Product\Edit::__invoke
* @see vendor/shopper/framework/src/Livewire/Pages/Product/Edit.php:7
* @route '/cpanel/products/{product}/edit'
*/
Edit.get = (args: { product: string | number } | [product: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: Edit.url(args, options),
    method: 'get',
})

/**
* @see \Shopper\Livewire\Pages\Product\Edit::__invoke
* @see vendor/shopper/framework/src/Livewire/Pages/Product/Edit.php:7
* @route '/cpanel/products/{product}/edit'
*/
Edit.head = (args: { product: string | number } | [product: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: Edit.url(args, options),
    method: 'head',
})

/**
* @see \Shopper\Livewire\Pages\Product\Edit::__invoke
* @see vendor/shopper/framework/src/Livewire/Pages/Product/Edit.php:7
* @route '/cpanel/products/{product}/edit'
*/
const EditForm = (args: { product: string | number } | [product: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: Edit.url(args, options),
    method: 'get',
})

/**
* @see \Shopper\Livewire\Pages\Product\Edit::__invoke
* @see vendor/shopper/framework/src/Livewire/Pages/Product/Edit.php:7
* @route '/cpanel/products/{product}/edit'
*/
EditForm.get = (args: { product: string | number } | [product: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: Edit.url(args, options),
    method: 'get',
})

/**
* @see \Shopper\Livewire\Pages\Product\Edit::__invoke
* @see vendor/shopper/framework/src/Livewire/Pages/Product/Edit.php:7
* @route '/cpanel/products/{product}/edit'
*/
EditForm.head = (args: { product: string | number } | [product: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
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