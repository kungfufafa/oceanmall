import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition, applyUrlDefaults } from './../../../../../../wayfinder'
/**
* @see \App\Http\Controllers\Api\V1\CatalogController::home
* @see app/Http/Controllers/Api/V1/CatalogController.php:27
* @route '/api/v1/catalog/home'
*/
export const home = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: home.url(options),
    method: 'get',
})

home.definition = {
    methods: ["get","head"],
    url: '/api/v1/catalog/home',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\Api\V1\CatalogController::home
* @see app/Http/Controllers/Api/V1/CatalogController.php:27
* @route '/api/v1/catalog/home'
*/
home.url = (options?: RouteQueryOptions) => {
    return home.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\Api\V1\CatalogController::home
* @see app/Http/Controllers/Api/V1/CatalogController.php:27
* @route '/api/v1/catalog/home'
*/
home.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: home.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Api\V1\CatalogController::home
* @see app/Http/Controllers/Api/V1/CatalogController.php:27
* @route '/api/v1/catalog/home'
*/
home.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: home.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\Api\V1\CatalogController::home
* @see app/Http/Controllers/Api/V1/CatalogController.php:27
* @route '/api/v1/catalog/home'
*/
const homeForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: home.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Api\V1\CatalogController::home
* @see app/Http/Controllers/Api/V1/CatalogController.php:27
* @route '/api/v1/catalog/home'
*/
homeForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: home.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Api\V1\CatalogController::home
* @see app/Http/Controllers/Api/V1/CatalogController.php:27
* @route '/api/v1/catalog/home'
*/
homeForm.head = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: home.url({
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

home.form = homeForm

/**
* @see \App\Http\Controllers\Api\V1\CatalogController::products
* @see app/Http/Controllers/Api/V1/CatalogController.php:65
* @route '/api/v1/catalog/products'
*/
export const products = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: products.url(options),
    method: 'get',
})

products.definition = {
    methods: ["get","head"],
    url: '/api/v1/catalog/products',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\Api\V1\CatalogController::products
* @see app/Http/Controllers/Api/V1/CatalogController.php:65
* @route '/api/v1/catalog/products'
*/
products.url = (options?: RouteQueryOptions) => {
    return products.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\Api\V1\CatalogController::products
* @see app/Http/Controllers/Api/V1/CatalogController.php:65
* @route '/api/v1/catalog/products'
*/
products.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: products.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Api\V1\CatalogController::products
* @see app/Http/Controllers/Api/V1/CatalogController.php:65
* @route '/api/v1/catalog/products'
*/
products.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: products.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\Api\V1\CatalogController::products
* @see app/Http/Controllers/Api/V1/CatalogController.php:65
* @route '/api/v1/catalog/products'
*/
const productsForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: products.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Api\V1\CatalogController::products
* @see app/Http/Controllers/Api/V1/CatalogController.php:65
* @route '/api/v1/catalog/products'
*/
productsForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: products.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Api\V1\CatalogController::products
* @see app/Http/Controllers/Api/V1/CatalogController.php:65
* @route '/api/v1/catalog/products'
*/
productsForm.head = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: products.url({
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

products.form = productsForm

/**
* @see \App\Http\Controllers\Api\V1\CatalogController::product
* @see app/Http/Controllers/Api/V1/CatalogController.php:116
* @route '/api/v1/catalog/products/{slug}'
*/
export const product = (args: { slug: string | number } | [slug: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: product.url(args, options),
    method: 'get',
})

product.definition = {
    methods: ["get","head"],
    url: '/api/v1/catalog/products/{slug}',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\Api\V1\CatalogController::product
* @see app/Http/Controllers/Api/V1/CatalogController.php:116
* @route '/api/v1/catalog/products/{slug}'
*/
product.url = (args: { slug: string | number } | [slug: string | number ] | string | number, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { slug: args }
    }

    if (Array.isArray(args)) {
        args = {
            slug: args[0],
        }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
        slug: args.slug,
    }

    return product.definition.url
            .replace('{slug}', parsedArgs.slug.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\Api\V1\CatalogController::product
* @see app/Http/Controllers/Api/V1/CatalogController.php:116
* @route '/api/v1/catalog/products/{slug}'
*/
product.get = (args: { slug: string | number } | [slug: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: product.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Api\V1\CatalogController::product
* @see app/Http/Controllers/Api/V1/CatalogController.php:116
* @route '/api/v1/catalog/products/{slug}'
*/
product.head = (args: { slug: string | number } | [slug: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: product.url(args, options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\Api\V1\CatalogController::product
* @see app/Http/Controllers/Api/V1/CatalogController.php:116
* @route '/api/v1/catalog/products/{slug}'
*/
const productForm = (args: { slug: string | number } | [slug: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: product.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Api\V1\CatalogController::product
* @see app/Http/Controllers/Api/V1/CatalogController.php:116
* @route '/api/v1/catalog/products/{slug}'
*/
productForm.get = (args: { slug: string | number } | [slug: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: product.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Api\V1\CatalogController::product
* @see app/Http/Controllers/Api/V1/CatalogController.php:116
* @route '/api/v1/catalog/products/{slug}'
*/
productForm.head = (args: { slug: string | number } | [slug: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
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
* @see \App\Http\Controllers\Api\V1\CatalogController::search
* @see app/Http/Controllers/Api/V1/CatalogController.php:147
* @route '/api/v1/catalog/search'
*/
export const search = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: search.url(options),
    method: 'get',
})

search.definition = {
    methods: ["get","head"],
    url: '/api/v1/catalog/search',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\Api\V1\CatalogController::search
* @see app/Http/Controllers/Api/V1/CatalogController.php:147
* @route '/api/v1/catalog/search'
*/
search.url = (options?: RouteQueryOptions) => {
    return search.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\Api\V1\CatalogController::search
* @see app/Http/Controllers/Api/V1/CatalogController.php:147
* @route '/api/v1/catalog/search'
*/
search.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: search.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Api\V1\CatalogController::search
* @see app/Http/Controllers/Api/V1/CatalogController.php:147
* @route '/api/v1/catalog/search'
*/
search.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: search.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\Api\V1\CatalogController::search
* @see app/Http/Controllers/Api/V1/CatalogController.php:147
* @route '/api/v1/catalog/search'
*/
const searchForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: search.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Api\V1\CatalogController::search
* @see app/Http/Controllers/Api/V1/CatalogController.php:147
* @route '/api/v1/catalog/search'
*/
searchForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: search.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Api\V1\CatalogController::search
* @see app/Http/Controllers/Api/V1/CatalogController.php:147
* @route '/api/v1/catalog/search'
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
* @see \App\Http\Controllers\Api\V1\CatalogController::categories
* @see app/Http/Controllers/Api/V1/CatalogController.php:283
* @route '/api/v1/catalog/categories'
*/
export const categories = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: categories.url(options),
    method: 'get',
})

categories.definition = {
    methods: ["get","head"],
    url: '/api/v1/catalog/categories',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\Api\V1\CatalogController::categories
* @see app/Http/Controllers/Api/V1/CatalogController.php:283
* @route '/api/v1/catalog/categories'
*/
categories.url = (options?: RouteQueryOptions) => {
    return categories.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\Api\V1\CatalogController::categories
* @see app/Http/Controllers/Api/V1/CatalogController.php:283
* @route '/api/v1/catalog/categories'
*/
categories.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: categories.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Api\V1\CatalogController::categories
* @see app/Http/Controllers/Api/V1/CatalogController.php:283
* @route '/api/v1/catalog/categories'
*/
categories.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: categories.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\Api\V1\CatalogController::categories
* @see app/Http/Controllers/Api/V1/CatalogController.php:283
* @route '/api/v1/catalog/categories'
*/
const categoriesForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: categories.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Api\V1\CatalogController::categories
* @see app/Http/Controllers/Api/V1/CatalogController.php:283
* @route '/api/v1/catalog/categories'
*/
categoriesForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: categories.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Api\V1\CatalogController::categories
* @see app/Http/Controllers/Api/V1/CatalogController.php:283
* @route '/api/v1/catalog/categories'
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
* @see \App\Http\Controllers\Api\V1\CatalogController::category
* @see app/Http/Controllers/Api/V1/CatalogController.php:166
* @route '/api/v1/catalog/categories/{slug}'
*/
export const category = (args: { slug: string | number } | [slug: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: category.url(args, options),
    method: 'get',
})

category.definition = {
    methods: ["get","head"],
    url: '/api/v1/catalog/categories/{slug}',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\Api\V1\CatalogController::category
* @see app/Http/Controllers/Api/V1/CatalogController.php:166
* @route '/api/v1/catalog/categories/{slug}'
*/
category.url = (args: { slug: string | number } | [slug: string | number ] | string | number, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { slug: args }
    }

    if (Array.isArray(args)) {
        args = {
            slug: args[0],
        }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
        slug: args.slug,
    }

    return category.definition.url
            .replace('{slug}', parsedArgs.slug.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\Api\V1\CatalogController::category
* @see app/Http/Controllers/Api/V1/CatalogController.php:166
* @route '/api/v1/catalog/categories/{slug}'
*/
category.get = (args: { slug: string | number } | [slug: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: category.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Api\V1\CatalogController::category
* @see app/Http/Controllers/Api/V1/CatalogController.php:166
* @route '/api/v1/catalog/categories/{slug}'
*/
category.head = (args: { slug: string | number } | [slug: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: category.url(args, options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\Api\V1\CatalogController::category
* @see app/Http/Controllers/Api/V1/CatalogController.php:166
* @route '/api/v1/catalog/categories/{slug}'
*/
const categoryForm = (args: { slug: string | number } | [slug: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: category.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Api\V1\CatalogController::category
* @see app/Http/Controllers/Api/V1/CatalogController.php:166
* @route '/api/v1/catalog/categories/{slug}'
*/
categoryForm.get = (args: { slug: string | number } | [slug: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: category.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Api\V1\CatalogController::category
* @see app/Http/Controllers/Api/V1/CatalogController.php:166
* @route '/api/v1/catalog/categories/{slug}'
*/
categoryForm.head = (args: { slug: string | number } | [slug: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
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
* @see \App\Http\Controllers\Api\V1\CatalogController::collection
* @see app/Http/Controllers/Api/V1/CatalogController.php:182
* @route '/api/v1/catalog/collections/{slug}'
*/
export const collection = (args: { slug: string | number } | [slug: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: collection.url(args, options),
    method: 'get',
})

collection.definition = {
    methods: ["get","head"],
    url: '/api/v1/catalog/collections/{slug}',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\Api\V1\CatalogController::collection
* @see app/Http/Controllers/Api/V1/CatalogController.php:182
* @route '/api/v1/catalog/collections/{slug}'
*/
collection.url = (args: { slug: string | number } | [slug: string | number ] | string | number, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { slug: args }
    }

    if (Array.isArray(args)) {
        args = {
            slug: args[0],
        }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
        slug: args.slug,
    }

    return collection.definition.url
            .replace('{slug}', parsedArgs.slug.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\Api\V1\CatalogController::collection
* @see app/Http/Controllers/Api/V1/CatalogController.php:182
* @route '/api/v1/catalog/collections/{slug}'
*/
collection.get = (args: { slug: string | number } | [slug: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: collection.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Api\V1\CatalogController::collection
* @see app/Http/Controllers/Api/V1/CatalogController.php:182
* @route '/api/v1/catalog/collections/{slug}'
*/
collection.head = (args: { slug: string | number } | [slug: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: collection.url(args, options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\Api\V1\CatalogController::collection
* @see app/Http/Controllers/Api/V1/CatalogController.php:182
* @route '/api/v1/catalog/collections/{slug}'
*/
const collectionForm = (args: { slug: string | number } | [slug: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: collection.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Api\V1\CatalogController::collection
* @see app/Http/Controllers/Api/V1/CatalogController.php:182
* @route '/api/v1/catalog/collections/{slug}'
*/
collectionForm.get = (args: { slug: string | number } | [slug: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: collection.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Api\V1\CatalogController::collection
* @see app/Http/Controllers/Api/V1/CatalogController.php:182
* @route '/api/v1/catalog/collections/{slug}'
*/
collectionForm.head = (args: { slug: string | number } | [slug: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
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
* @see \App\Http\Controllers\Api\V1\CatalogController::storeReview
* @see app/Http/Controllers/Api/V1/CatalogController.php:216
* @route '/api/v1/catalog/products/{slug}/reviews'
*/
export const storeReview = (args: { slug: string | number } | [slug: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: storeReview.url(args, options),
    method: 'post',
})

storeReview.definition = {
    methods: ["post"],
    url: '/api/v1/catalog/products/{slug}/reviews',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\Api\V1\CatalogController::storeReview
* @see app/Http/Controllers/Api/V1/CatalogController.php:216
* @route '/api/v1/catalog/products/{slug}/reviews'
*/
storeReview.url = (args: { slug: string | number } | [slug: string | number ] | string | number, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { slug: args }
    }

    if (Array.isArray(args)) {
        args = {
            slug: args[0],
        }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
        slug: args.slug,
    }

    return storeReview.definition.url
            .replace('{slug}', parsedArgs.slug.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\Api\V1\CatalogController::storeReview
* @see app/Http/Controllers/Api/V1/CatalogController.php:216
* @route '/api/v1/catalog/products/{slug}/reviews'
*/
storeReview.post = (args: { slug: string | number } | [slug: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: storeReview.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\Api\V1\CatalogController::storeReview
* @see app/Http/Controllers/Api/V1/CatalogController.php:216
* @route '/api/v1/catalog/products/{slug}/reviews'
*/
const storeReviewForm = (args: { slug: string | number } | [slug: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: storeReview.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\Api\V1\CatalogController::storeReview
* @see app/Http/Controllers/Api/V1/CatalogController.php:216
* @route '/api/v1/catalog/products/{slug}/reviews'
*/
storeReviewForm.post = (args: { slug: string | number } | [slug: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: storeReview.url(args, options),
    method: 'post',
})

storeReview.form = storeReviewForm

const CatalogController = { home, products, product, search, categories, category, collection, storeReview }

export default CatalogController