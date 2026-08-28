import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition, applyUrlDefaults } from './../../../../../../wayfinder'
/**
* @see \App\Http\Controllers\Api\V1\CartController::show
* @see app/Http/Controllers/Api/V1/CartController.php:31
* @route '/api/v1/cart'
*/
export const show = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: show.url(options),
    method: 'get',
})

show.definition = {
    methods: ["get","head"],
    url: '/api/v1/cart',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\Api\V1\CartController::show
* @see app/Http/Controllers/Api/V1/CartController.php:31
* @route '/api/v1/cart'
*/
show.url = (options?: RouteQueryOptions) => {
    return show.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\Api\V1\CartController::show
* @see app/Http/Controllers/Api/V1/CartController.php:31
* @route '/api/v1/cart'
*/
show.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: show.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Api\V1\CartController::show
* @see app/Http/Controllers/Api/V1/CartController.php:31
* @route '/api/v1/cart'
*/
show.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: show.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\Api\V1\CartController::show
* @see app/Http/Controllers/Api/V1/CartController.php:31
* @route '/api/v1/cart'
*/
const showForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: show.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Api\V1\CartController::show
* @see app/Http/Controllers/Api/V1/CartController.php:31
* @route '/api/v1/cart'
*/
showForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: show.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Api\V1\CartController::show
* @see app/Http/Controllers/Api/V1/CartController.php:31
* @route '/api/v1/cart'
*/
showForm.head = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: show.url({
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

show.form = showForm

/**
* @see \App\Http\Controllers\Api\V1\CartController::add
* @see app/Http/Controllers/Api/V1/CartController.php:36
* @route '/api/v1/cart/items'
*/
export const add = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: add.url(options),
    method: 'post',
})

add.definition = {
    methods: ["post"],
    url: '/api/v1/cart/items',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\Api\V1\CartController::add
* @see app/Http/Controllers/Api/V1/CartController.php:36
* @route '/api/v1/cart/items'
*/
add.url = (options?: RouteQueryOptions) => {
    return add.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\Api\V1\CartController::add
* @see app/Http/Controllers/Api/V1/CartController.php:36
* @route '/api/v1/cart/items'
*/
add.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: add.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\Api\V1\CartController::add
* @see app/Http/Controllers/Api/V1/CartController.php:36
* @route '/api/v1/cart/items'
*/
const addForm = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: add.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\Api\V1\CartController::add
* @see app/Http/Controllers/Api/V1/CartController.php:36
* @route '/api/v1/cart/items'
*/
addForm.post = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: add.url(options),
    method: 'post',
})

add.form = addForm

/**
* @see \App\Http\Controllers\Api\V1\CartController::update
* @see app/Http/Controllers/Api/V1/CartController.php:65
* @route '/api/v1/cart/items/{line}'
*/
export const update = (args: { line: string | number } | [line: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'patch'> => ({
    url: update.url(args, options),
    method: 'patch',
})

update.definition = {
    methods: ["patch"],
    url: '/api/v1/cart/items/{line}',
} satisfies RouteDefinition<["patch"]>

/**
* @see \App\Http\Controllers\Api\V1\CartController::update
* @see app/Http/Controllers/Api/V1/CartController.php:65
* @route '/api/v1/cart/items/{line}'
*/
update.url = (args: { line: string | number } | [line: string | number ] | string | number, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { line: args }
    }

    if (Array.isArray(args)) {
        args = {
            line: args[0],
        }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
        line: args.line,
    }

    return update.definition.url
            .replace('{line}', parsedArgs.line.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\Api\V1\CartController::update
* @see app/Http/Controllers/Api/V1/CartController.php:65
* @route '/api/v1/cart/items/{line}'
*/
update.patch = (args: { line: string | number } | [line: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'patch'> => ({
    url: update.url(args, options),
    method: 'patch',
})

/**
* @see \App\Http\Controllers\Api\V1\CartController::update
* @see app/Http/Controllers/Api/V1/CartController.php:65
* @route '/api/v1/cart/items/{line}'
*/
const updateForm = (args: { line: string | number } | [line: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: update.url(args, {
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'PATCH',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'post',
})

/**
* @see \App\Http\Controllers\Api\V1\CartController::update
* @see app/Http/Controllers/Api/V1/CartController.php:65
* @route '/api/v1/cart/items/{line}'
*/
updateForm.patch = (args: { line: string | number } | [line: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: update.url(args, {
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'PATCH',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'post',
})

update.form = updateForm

/**
* @see \App\Http\Controllers\Api\V1\CartController::destroy
* @see app/Http/Controllers/Api/V1/CartController.php:85
* @route '/api/v1/cart/items/{line}'
*/
export const destroy = (args: { line: string | number } | [line: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'delete'> => ({
    url: destroy.url(args, options),
    method: 'delete',
})

destroy.definition = {
    methods: ["delete"],
    url: '/api/v1/cart/items/{line}',
} satisfies RouteDefinition<["delete"]>

/**
* @see \App\Http\Controllers\Api\V1\CartController::destroy
* @see app/Http/Controllers/Api/V1/CartController.php:85
* @route '/api/v1/cart/items/{line}'
*/
destroy.url = (args: { line: string | number } | [line: string | number ] | string | number, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { line: args }
    }

    if (Array.isArray(args)) {
        args = {
            line: args[0],
        }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
        line: args.line,
    }

    return destroy.definition.url
            .replace('{line}', parsedArgs.line.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\Api\V1\CartController::destroy
* @see app/Http/Controllers/Api/V1/CartController.php:85
* @route '/api/v1/cart/items/{line}'
*/
destroy.delete = (args: { line: string | number } | [line: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'delete'> => ({
    url: destroy.url(args, options),
    method: 'delete',
})

/**
* @see \App\Http\Controllers\Api\V1\CartController::destroy
* @see app/Http/Controllers/Api/V1/CartController.php:85
* @route '/api/v1/cart/items/{line}'
*/
const destroyForm = (args: { line: string | number } | [line: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: destroy.url(args, {
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'DELETE',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'post',
})

/**
* @see \App\Http\Controllers\Api\V1\CartController::destroy
* @see app/Http/Controllers/Api/V1/CartController.php:85
* @route '/api/v1/cart/items/{line}'
*/
destroyForm.delete = (args: { line: string | number } | [line: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: destroy.url(args, {
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'DELETE',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'post',
})

destroy.form = destroyForm

/**
* @see \App\Http\Controllers\Api\V1\CartController::clear
* @see app/Http/Controllers/Api/V1/CartController.php:95
* @route '/api/v1/cart'
*/
export const clear = (options?: RouteQueryOptions): RouteDefinition<'delete'> => ({
    url: clear.url(options),
    method: 'delete',
})

clear.definition = {
    methods: ["delete"],
    url: '/api/v1/cart',
} satisfies RouteDefinition<["delete"]>

/**
* @see \App\Http\Controllers\Api\V1\CartController::clear
* @see app/Http/Controllers/Api/V1/CartController.php:95
* @route '/api/v1/cart'
*/
clear.url = (options?: RouteQueryOptions) => {
    return clear.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\Api\V1\CartController::clear
* @see app/Http/Controllers/Api/V1/CartController.php:95
* @route '/api/v1/cart'
*/
clear.delete = (options?: RouteQueryOptions): RouteDefinition<'delete'> => ({
    url: clear.url(options),
    method: 'delete',
})

/**
* @see \App\Http\Controllers\Api\V1\CartController::clear
* @see app/Http/Controllers/Api/V1/CartController.php:95
* @route '/api/v1/cart'
*/
const clearForm = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: clear.url({
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'DELETE',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'post',
})

/**
* @see \App\Http\Controllers\Api\V1\CartController::clear
* @see app/Http/Controllers/Api/V1/CartController.php:95
* @route '/api/v1/cart'
*/
clearForm.delete = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: clear.url({
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'DELETE',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'post',
})

clear.form = clearForm

/**
* @see \App\Http\Controllers\Api\V1\CartController::applyCoupon
* @see app/Http/Controllers/Api/V1/CartController.php:105
* @route '/api/v1/cart/coupon'
*/
export const applyCoupon = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: applyCoupon.url(options),
    method: 'post',
})

applyCoupon.definition = {
    methods: ["post"],
    url: '/api/v1/cart/coupon',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\Api\V1\CartController::applyCoupon
* @see app/Http/Controllers/Api/V1/CartController.php:105
* @route '/api/v1/cart/coupon'
*/
applyCoupon.url = (options?: RouteQueryOptions) => {
    return applyCoupon.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\Api\V1\CartController::applyCoupon
* @see app/Http/Controllers/Api/V1/CartController.php:105
* @route '/api/v1/cart/coupon'
*/
applyCoupon.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: applyCoupon.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\Api\V1\CartController::applyCoupon
* @see app/Http/Controllers/Api/V1/CartController.php:105
* @route '/api/v1/cart/coupon'
*/
const applyCouponForm = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: applyCoupon.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\Api\V1\CartController::applyCoupon
* @see app/Http/Controllers/Api/V1/CartController.php:105
* @route '/api/v1/cart/coupon'
*/
applyCouponForm.post = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: applyCoupon.url(options),
    method: 'post',
})

applyCoupon.form = applyCouponForm

/**
* @see \App\Http\Controllers\Api\V1\CartController::removeCoupon
* @see app/Http/Controllers/Api/V1/CartController.php:136
* @route '/api/v1/cart/coupon'
*/
export const removeCoupon = (options?: RouteQueryOptions): RouteDefinition<'delete'> => ({
    url: removeCoupon.url(options),
    method: 'delete',
})

removeCoupon.definition = {
    methods: ["delete"],
    url: '/api/v1/cart/coupon',
} satisfies RouteDefinition<["delete"]>

/**
* @see \App\Http\Controllers\Api\V1\CartController::removeCoupon
* @see app/Http/Controllers/Api/V1/CartController.php:136
* @route '/api/v1/cart/coupon'
*/
removeCoupon.url = (options?: RouteQueryOptions) => {
    return removeCoupon.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\Api\V1\CartController::removeCoupon
* @see app/Http/Controllers/Api/V1/CartController.php:136
* @route '/api/v1/cart/coupon'
*/
removeCoupon.delete = (options?: RouteQueryOptions): RouteDefinition<'delete'> => ({
    url: removeCoupon.url(options),
    method: 'delete',
})

/**
* @see \App\Http\Controllers\Api\V1\CartController::removeCoupon
* @see app/Http/Controllers/Api/V1/CartController.php:136
* @route '/api/v1/cart/coupon'
*/
const removeCouponForm = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: removeCoupon.url({
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'DELETE',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'post',
})

/**
* @see \App\Http\Controllers\Api\V1\CartController::removeCoupon
* @see app/Http/Controllers/Api/V1/CartController.php:136
* @route '/api/v1/cart/coupon'
*/
removeCouponForm.delete = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: removeCoupon.url({
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'DELETE',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'post',
})

removeCoupon.form = removeCouponForm

const CartController = { show, add, update, destroy, clear, applyCoupon, removeCoupon }

export default CartController