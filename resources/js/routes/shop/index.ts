import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition, applyUrlDefaults } from './../../wayfinder'
import productD87513 from './product'
import cartB8cf73 from './cart'
import zone from './zone'
import checkout from './checkout'
/**
* @see \App\Http\Controllers\Shop\ProductController::index
* @see app/Http/Controllers/Shop/ProductController.php:22
* @route '/shop'
*/
export const index = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
})

index.definition = {
    methods: ["get","head"],
    url: '/shop',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\Shop\ProductController::index
* @see app/Http/Controllers/Shop/ProductController.php:22
* @route '/shop'
*/
index.url = (options?: RouteQueryOptions) => {
    return index.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\Shop\ProductController::index
* @see app/Http/Controllers/Shop/ProductController.php:22
* @route '/shop'
*/
index.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Shop\ProductController::index
* @see app/Http/Controllers/Shop/ProductController.php:22
* @route '/shop'
*/
index.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: index.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\Shop\ProductController::index
* @see app/Http/Controllers/Shop/ProductController.php:22
* @route '/shop'
*/
const indexForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: index.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Shop\ProductController::index
* @see app/Http/Controllers/Shop/ProductController.php:22
* @route '/shop'
*/
indexForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: index.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Shop\ProductController::index
* @see app/Http/Controllers/Shop/ProductController.php:22
* @route '/shop'
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
* @see \App\Http\Controllers\Shop\ProductController::product
* @see app/Http/Controllers/Shop/ProductController.php:93
* @route '/shop/{product}'
*/
export const product = (args: { product: string | { slug: string } } | [product: string | { slug: string } ] | string | { slug: string }, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: product.url(args, options),
    method: 'get',
})

product.definition = {
    methods: ["get","head"],
    url: '/shop/{product}',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\Shop\ProductController::product
* @see app/Http/Controllers/Shop/ProductController.php:93
* @route '/shop/{product}'
*/
product.url = (args: { product: string | { slug: string } } | [product: string | { slug: string } ] | string | { slug: string }, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { product: args }
    }

    if (typeof args === 'object' && !Array.isArray(args) && 'slug' in args) {
        args = { product: args.slug }
    }

    if (Array.isArray(args)) {
        args = {
            product: args[0],
        }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
        product: typeof args.product === 'object'
        ? args.product.slug
        : args.product,
    }

    return product.definition.url
            .replace('{product}', parsedArgs.product.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\Shop\ProductController::product
* @see app/Http/Controllers/Shop/ProductController.php:93
* @route '/shop/{product}'
*/
product.get = (args: { product: string | { slug: string } } | [product: string | { slug: string } ] | string | { slug: string }, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: product.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Shop\ProductController::product
* @see app/Http/Controllers/Shop/ProductController.php:93
* @route '/shop/{product}'
*/
product.head = (args: { product: string | { slug: string } } | [product: string | { slug: string } ] | string | { slug: string }, options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: product.url(args, options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\Shop\ProductController::product
* @see app/Http/Controllers/Shop/ProductController.php:93
* @route '/shop/{product}'
*/
const productForm = (args: { product: string | { slug: string } } | [product: string | { slug: string } ] | string | { slug: string }, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: product.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Shop\ProductController::product
* @see app/Http/Controllers/Shop/ProductController.php:93
* @route '/shop/{product}'
*/
productForm.get = (args: { product: string | { slug: string } } | [product: string | { slug: string } ] | string | { slug: string }, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: product.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Shop\ProductController::product
* @see app/Http/Controllers/Shop/ProductController.php:93
* @route '/shop/{product}'
*/
productForm.head = (args: { product: string | { slug: string } } | [product: string | { slug: string } ] | string | { slug: string }, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: product.url(args, {
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

product.form = productForm

/**
* @see \App\Http\Controllers\Shop\CategoryController::categories
* @see app/Http/Controllers/Shop/CategoryController.php:16
* @route '/categories'
*/
export const categories = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: categories.url(options),
    method: 'get',
})

categories.definition = {
    methods: ["get","head"],
    url: '/categories',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\Shop\CategoryController::categories
* @see app/Http/Controllers/Shop/CategoryController.php:16
* @route '/categories'
*/
categories.url = (options?: RouteQueryOptions) => {
    return categories.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\Shop\CategoryController::categories
* @see app/Http/Controllers/Shop/CategoryController.php:16
* @route '/categories'
*/
categories.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: categories.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Shop\CategoryController::categories
* @see app/Http/Controllers/Shop/CategoryController.php:16
* @route '/categories'
*/
categories.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: categories.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\Shop\CategoryController::categories
* @see app/Http/Controllers/Shop/CategoryController.php:16
* @route '/categories'
*/
const categoriesForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: categories.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Shop\CategoryController::categories
* @see app/Http/Controllers/Shop/CategoryController.php:16
* @route '/categories'
*/
categoriesForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: categories.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Shop\CategoryController::categories
* @see app/Http/Controllers/Shop/CategoryController.php:16
* @route '/categories'
*/
categoriesForm.head = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: categories.url({
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

categories.form = categoriesForm

/**
* @see \App\Http\Controllers\Shop\CategoryController::category
* @see app/Http/Controllers/Shop/CategoryController.php:29
* @route '/categories/{category}'
*/
export const category = (args: { category: string | { slug: string } } | [category: string | { slug: string } ] | string | { slug: string }, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: category.url(args, options),
    method: 'get',
})

category.definition = {
    methods: ["get","head"],
    url: '/categories/{category}',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\Shop\CategoryController::category
* @see app/Http/Controllers/Shop/CategoryController.php:29
* @route '/categories/{category}'
*/
category.url = (args: { category: string | { slug: string } } | [category: string | { slug: string } ] | string | { slug: string }, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { category: args }
    }

    if (typeof args === 'object' && !Array.isArray(args) && 'slug' in args) {
        args = { category: args.slug }
    }

    if (Array.isArray(args)) {
        args = {
            category: args[0],
        }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
        category: typeof args.category === 'object'
        ? args.category.slug
        : args.category,
    }

    return category.definition.url
            .replace('{category}', parsedArgs.category.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\Shop\CategoryController::category
* @see app/Http/Controllers/Shop/CategoryController.php:29
* @route '/categories/{category}'
*/
category.get = (args: { category: string | { slug: string } } | [category: string | { slug: string } ] | string | { slug: string }, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: category.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Shop\CategoryController::category
* @see app/Http/Controllers/Shop/CategoryController.php:29
* @route '/categories/{category}'
*/
category.head = (args: { category: string | { slug: string } } | [category: string | { slug: string } ] | string | { slug: string }, options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: category.url(args, options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\Shop\CategoryController::category
* @see app/Http/Controllers/Shop/CategoryController.php:29
* @route '/categories/{category}'
*/
const categoryForm = (args: { category: string | { slug: string } } | [category: string | { slug: string } ] | string | { slug: string }, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: category.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Shop\CategoryController::category
* @see app/Http/Controllers/Shop/CategoryController.php:29
* @route '/categories/{category}'
*/
categoryForm.get = (args: { category: string | { slug: string } } | [category: string | { slug: string } ] | string | { slug: string }, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: category.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Shop\CategoryController::category
* @see app/Http/Controllers/Shop/CategoryController.php:29
* @route '/categories/{category}'
*/
categoryForm.head = (args: { category: string | { slug: string } } | [category: string | { slug: string } ] | string | { slug: string }, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: category.url(args, {
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

category.form = categoryForm

/**
* @see \App\Http\Controllers\Shop\CollectionController::collection
* @see app/Http/Controllers/Shop/CollectionController.php:15
* @route '/collections/{collection}'
*/
export const collection = (args: { collection: string | { slug: string } } | [collection: string | { slug: string } ] | string | { slug: string }, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: collection.url(args, options),
    method: 'get',
})

collection.definition = {
    methods: ["get","head"],
    url: '/collections/{collection}',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\Shop\CollectionController::collection
* @see app/Http/Controllers/Shop/CollectionController.php:15
* @route '/collections/{collection}'
*/
collection.url = (args: { collection: string | { slug: string } } | [collection: string | { slug: string } ] | string | { slug: string }, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { collection: args }
    }

    if (typeof args === 'object' && !Array.isArray(args) && 'slug' in args) {
        args = { collection: args.slug }
    }

    if (Array.isArray(args)) {
        args = {
            collection: args[0],
        }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
        collection: typeof args.collection === 'object'
        ? args.collection.slug
        : args.collection,
    }

    return collection.definition.url
            .replace('{collection}', parsedArgs.collection.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\Shop\CollectionController::collection
* @see app/Http/Controllers/Shop/CollectionController.php:15
* @route '/collections/{collection}'
*/
collection.get = (args: { collection: string | { slug: string } } | [collection: string | { slug: string } ] | string | { slug: string }, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: collection.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Shop\CollectionController::collection
* @see app/Http/Controllers/Shop/CollectionController.php:15
* @route '/collections/{collection}'
*/
collection.head = (args: { collection: string | { slug: string } } | [collection: string | { slug: string } ] | string | { slug: string }, options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: collection.url(args, options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\Shop\CollectionController::collection
* @see app/Http/Controllers/Shop/CollectionController.php:15
* @route '/collections/{collection}'
*/
const collectionForm = (args: { collection: string | { slug: string } } | [collection: string | { slug: string } ] | string | { slug: string }, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: collection.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Shop\CollectionController::collection
* @see app/Http/Controllers/Shop/CollectionController.php:15
* @route '/collections/{collection}'
*/
collectionForm.get = (args: { collection: string | { slug: string } } | [collection: string | { slug: string } ] | string | { slug: string }, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: collection.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Shop\CollectionController::collection
* @see app/Http/Controllers/Shop/CollectionController.php:15
* @route '/collections/{collection}'
*/
collectionForm.head = (args: { collection: string | { slug: string } } | [collection: string | { slug: string } ] | string | { slug: string }, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: collection.url(args, {
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

collection.form = collectionForm

/**
* @see \App\Http\Controllers\Shop\SearchController::__invoke
* @see app/Http/Controllers/Shop/SearchController.php:15
* @route '/search'
*/
export const search = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: search.url(options),
    method: 'get',
})

search.definition = {
    methods: ["get","head"],
    url: '/search',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\Shop\SearchController::__invoke
* @see app/Http/Controllers/Shop/SearchController.php:15
* @route '/search'
*/
search.url = (options?: RouteQueryOptions) => {
    return search.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\Shop\SearchController::__invoke
* @see app/Http/Controllers/Shop/SearchController.php:15
* @route '/search'
*/
search.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: search.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Shop\SearchController::__invoke
* @see app/Http/Controllers/Shop/SearchController.php:15
* @route '/search'
*/
search.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: search.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\Shop\SearchController::__invoke
* @see app/Http/Controllers/Shop/SearchController.php:15
* @route '/search'
*/
const searchForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: search.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Shop\SearchController::__invoke
* @see app/Http/Controllers/Shop/SearchController.php:15
* @route '/search'
*/
searchForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: search.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Shop\SearchController::__invoke
* @see app/Http/Controllers/Shop/SearchController.php:15
* @route '/search'
*/
searchForm.head = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: search.url({
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

search.form = searchForm

/**
* @see \App\Http\Controllers\Shop\CartController::cart
* @see app/Http/Controllers/Shop/CartController.php:22
* @route '/cart'
*/
export const cart = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: cart.url(options),
    method: 'get',
})

cart.definition = {
    methods: ["get","head"],
    url: '/cart',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\Shop\CartController::cart
* @see app/Http/Controllers/Shop/CartController.php:22
* @route '/cart'
*/
cart.url = (options?: RouteQueryOptions) => {
    return cart.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\Shop\CartController::cart
* @see app/Http/Controllers/Shop/CartController.php:22
* @route '/cart'
*/
cart.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: cart.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Shop\CartController::cart
* @see app/Http/Controllers/Shop/CartController.php:22
* @route '/cart'
*/
cart.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: cart.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\Shop\CartController::cart
* @see app/Http/Controllers/Shop/CartController.php:22
* @route '/cart'
*/
const cartForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: cart.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Shop\CartController::cart
* @see app/Http/Controllers/Shop/CartController.php:22
* @route '/cart'
*/
cartForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: cart.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Shop\CartController::cart
* @see app/Http/Controllers/Shop/CartController.php:22
* @route '/cart'
*/
cartForm.head = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: cart.url({
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

cart.form = cartForm

const shop = {
    index: Object.assign(index, index),
    product: Object.assign(product, productD87513),
    categories: Object.assign(categories, categories),
    category: Object.assign(category, category),
    collection: Object.assign(collection, collection),
    search: Object.assign(search, search),
    cart: Object.assign(cart, cartB8cf73),
    zone: Object.assign(zone, zone),
    checkout: Object.assign(checkout, checkout),
}

export default shop