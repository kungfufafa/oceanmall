import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition, applyUrlDefaults } from './../../../wayfinder'
/**
* @see \Shopper\Livewire\Pages\Product\Index::__invoke
* @see vendor/shopper/framework/src/Livewire/Pages/Product/Index.php:7
* @route '/cpanel/products'
*/
export const index = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
})

index.definition = {
    methods: ["get","head"],
    url: '/cpanel/products',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \Shopper\Livewire\Pages\Product\Index::__invoke
* @see vendor/shopper/framework/src/Livewire/Pages/Product/Index.php:7
* @route '/cpanel/products'
*/
index.url = (options?: RouteQueryOptions) => {
    return index.definition.url + queryParams(options)
}

/**
* @see \Shopper\Livewire\Pages\Product\Index::__invoke
* @see vendor/shopper/framework/src/Livewire/Pages/Product/Index.php:7
* @route '/cpanel/products'
*/
index.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
})

/**
* @see \Shopper\Livewire\Pages\Product\Index::__invoke
* @see vendor/shopper/framework/src/Livewire/Pages/Product/Index.php:7
* @route '/cpanel/products'
*/
index.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: index.url(options),
    method: 'head',
})

/**
* @see \Shopper\Livewire\Pages\Product\Index::__invoke
* @see vendor/shopper/framework/src/Livewire/Pages/Product/Index.php:7
* @route '/cpanel/products'
*/
const indexForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: index.url(options),
    method: 'get',
})

/**
* @see \Shopper\Livewire\Pages\Product\Index::__invoke
* @see vendor/shopper/framework/src/Livewire/Pages/Product/Index.php:7
* @route '/cpanel/products'
*/
indexForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: index.url(options),
    method: 'get',
})

/**
* @see \Shopper\Livewire\Pages\Product\Index::__invoke
* @see vendor/shopper/framework/src/Livewire/Pages/Product/Index.php:7
* @route '/cpanel/products'
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
* @see \Shopper\Livewire\Pages\Product\Edit::__invoke
* @see vendor/shopper/framework/src/Livewire/Pages/Product/Edit.php:7
* @route '/cpanel/products/{product}/edit'
*/
export const edit = (args: { product: string | number } | [product: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: edit.url(args, options),
    method: 'get',
})

edit.definition = {
    methods: ["get","head"],
    url: '/cpanel/products/{product}/edit',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \Shopper\Livewire\Pages\Product\Edit::__invoke
* @see vendor/shopper/framework/src/Livewire/Pages/Product/Edit.php:7
* @route '/cpanel/products/{product}/edit'
*/
edit.url = (args: { product: string | number } | [product: string | number ] | string | number, options?: RouteQueryOptions) => {
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

    return edit.definition.url
            .replace('{product}', parsedArgs.product.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \Shopper\Livewire\Pages\Product\Edit::__invoke
* @see vendor/shopper/framework/src/Livewire/Pages/Product/Edit.php:7
* @route '/cpanel/products/{product}/edit'
*/
edit.get = (args: { product: string | number } | [product: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: edit.url(args, options),
    method: 'get',
})

/**
* @see \Shopper\Livewire\Pages\Product\Edit::__invoke
* @see vendor/shopper/framework/src/Livewire/Pages/Product/Edit.php:7
* @route '/cpanel/products/{product}/edit'
*/
edit.head = (args: { product: string | number } | [product: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: edit.url(args, options),
    method: 'head',
})

/**
* @see \Shopper\Livewire\Pages\Product\Edit::__invoke
* @see vendor/shopper/framework/src/Livewire/Pages/Product/Edit.php:7
* @route '/cpanel/products/{product}/edit'
*/
const editForm = (args: { product: string | number } | [product: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: edit.url(args, options),
    method: 'get',
})

/**
* @see \Shopper\Livewire\Pages\Product\Edit::__invoke
* @see vendor/shopper/framework/src/Livewire/Pages/Product/Edit.php:7
* @route '/cpanel/products/{product}/edit'
*/
editForm.get = (args: { product: string | number } | [product: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: edit.url(args, options),
    method: 'get',
})

/**
* @see \Shopper\Livewire\Pages\Product\Edit::__invoke
* @see vendor/shopper/framework/src/Livewire/Pages/Product/Edit.php:7
* @route '/cpanel/products/{product}/edit'
*/
editForm.head = (args: { product: string | number } | [product: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: edit.url(args, {
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

edit.form = editForm

/**
* @see \Shopper\Livewire\Pages\Product\Variant::__invoke
* @see vendor/shopper/framework/src/Livewire/Pages/Product/Variant.php:7
* @route '/cpanel/products/{product}/variants/{variant}'
*/
export const variant = (args: { product: string | number, variant: string | number } | [product: string | number, variant: string | number ], options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: variant.url(args, options),
    method: 'get',
})

variant.definition = {
    methods: ["get","head"],
    url: '/cpanel/products/{product}/variants/{variant}',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \Shopper\Livewire\Pages\Product\Variant::__invoke
* @see vendor/shopper/framework/src/Livewire/Pages/Product/Variant.php:7
* @route '/cpanel/products/{product}/variants/{variant}'
*/
variant.url = (args: { product: string | number, variant: string | number } | [product: string | number, variant: string | number ], options?: RouteQueryOptions) => {
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

    return variant.definition.url
            .replace('{product}', parsedArgs.product.toString())
            .replace('{variant}', parsedArgs.variant.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \Shopper\Livewire\Pages\Product\Variant::__invoke
* @see vendor/shopper/framework/src/Livewire/Pages/Product/Variant.php:7
* @route '/cpanel/products/{product}/variants/{variant}'
*/
variant.get = (args: { product: string | number, variant: string | number } | [product: string | number, variant: string | number ], options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: variant.url(args, options),
    method: 'get',
})

/**
* @see \Shopper\Livewire\Pages\Product\Variant::__invoke
* @see vendor/shopper/framework/src/Livewire/Pages/Product/Variant.php:7
* @route '/cpanel/products/{product}/variants/{variant}'
*/
variant.head = (args: { product: string | number, variant: string | number } | [product: string | number, variant: string | number ], options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: variant.url(args, options),
    method: 'head',
})

/**
* @see \Shopper\Livewire\Pages\Product\Variant::__invoke
* @see vendor/shopper/framework/src/Livewire/Pages/Product/Variant.php:7
* @route '/cpanel/products/{product}/variants/{variant}'
*/
const variantForm = (args: { product: string | number, variant: string | number } | [product: string | number, variant: string | number ], options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: variant.url(args, options),
    method: 'get',
})

/**
* @see \Shopper\Livewire\Pages\Product\Variant::__invoke
* @see vendor/shopper/framework/src/Livewire/Pages/Product/Variant.php:7
* @route '/cpanel/products/{product}/variants/{variant}'
*/
variantForm.get = (args: { product: string | number, variant: string | number } | [product: string | number, variant: string | number ], options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: variant.url(args, options),
    method: 'get',
})

/**
* @see \Shopper\Livewire\Pages\Product\Variant::__invoke
* @see vendor/shopper/framework/src/Livewire/Pages/Product/Variant.php:7
* @route '/cpanel/products/{product}/variants/{variant}'
*/
variantForm.head = (args: { product: string | number, variant: string | number } | [product: string | number, variant: string | number ], options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: variant.url(args, {
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

variant.form = variantForm

const products = {
    index: Object.assign(index, index),
    edit: Object.assign(edit, edit),
    variant: Object.assign(variant, variant),
}

export default products