import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition, applyUrlDefaults } from './../../../../../wayfinder'
/**
* @see \Shopper\Livewire\Pages\Product\Variant::__invoke
* @see vendor/shopper/framework/src/Livewire/Pages/Product/Variant.php:7
* @route '/cpanel/products/{product}/variants/{variant}'
*/
const Variant = (args: { product: string | number, variant: string | number } | [product: string | number, variant: string | number ], options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: Variant.url(args, options),
    method: 'get',
})

Variant.definition = {
    methods: ["get","head"],
    url: '/cpanel/products/{product}/variants/{variant}',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \Shopper\Livewire\Pages\Product\Variant::__invoke
* @see vendor/shopper/framework/src/Livewire/Pages/Product/Variant.php:7
* @route '/cpanel/products/{product}/variants/{variant}'
*/
Variant.url = (args: { product: string | number, variant: string | number } | [product: string | number, variant: string | number ], options?: RouteQueryOptions) => {
    if (Array.isArray(args)) {
        args = {
            product: args[0],
            variant: args[1],
        }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
        product: args.product,
        variant: args.variant,
    }

    return Variant.definition.url
            .replace('{product}', parsedArgs.product.toString())
            .replace('{variant}', parsedArgs.variant.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \Shopper\Livewire\Pages\Product\Variant::__invoke
* @see vendor/shopper/framework/src/Livewire/Pages/Product/Variant.php:7
* @route '/cpanel/products/{product}/variants/{variant}'
*/
Variant.get = (args: { product: string | number, variant: string | number } | [product: string | number, variant: string | number ], options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: Variant.url(args, options),
    method: 'get',
})

/**
* @see \Shopper\Livewire\Pages\Product\Variant::__invoke
* @see vendor/shopper/framework/src/Livewire/Pages/Product/Variant.php:7
* @route '/cpanel/products/{product}/variants/{variant}'
*/
Variant.head = (args: { product: string | number, variant: string | number } | [product: string | number, variant: string | number ], options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: Variant.url(args, options),
    method: 'head',
})

/**
* @see \Shopper\Livewire\Pages\Product\Variant::__invoke
* @see vendor/shopper/framework/src/Livewire/Pages/Product/Variant.php:7
* @route '/cpanel/products/{product}/variants/{variant}'
*/
const VariantForm = (args: { product: string | number, variant: string | number } | [product: string | number, variant: string | number ], options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: Variant.url(args, options),
    method: 'get',
})

/**
* @see \Shopper\Livewire\Pages\Product\Variant::__invoke
* @see vendor/shopper/framework/src/Livewire/Pages/Product/Variant.php:7
* @route '/cpanel/products/{product}/variants/{variant}'
*/
VariantForm.get = (args: { product: string | number, variant: string | number } | [product: string | number, variant: string | number ], options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: Variant.url(args, options),
    method: 'get',
})

/**
* @see \Shopper\Livewire\Pages\Product\Variant::__invoke
* @see vendor/shopper/framework/src/Livewire/Pages/Product/Variant.php:7
* @route '/cpanel/products/{product}/variants/{variant}'
*/
VariantForm.head = (args: { product: string | number, variant: string | number } | [product: string | number, variant: string | number ], options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: Variant.url(args, {
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

Variant.form = VariantForm

export default Variant