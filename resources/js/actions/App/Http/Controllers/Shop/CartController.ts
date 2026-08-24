import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition, applyUrlDefaults } from './../../../../../wayfinder'
/**
* @see \App\Http\Controllers\Shop\CartController::index
* @see app/Http/Controllers/Shop/CartController.php:22
* @route '/cart'
*/
export const index = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
})

index.definition = {
    methods: ["get","head"],
    url: '/cart',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\Shop\CartController::index
* @see app/Http/Controllers/Shop/CartController.php:22
* @route '/cart'
*/
index.url = (options?: RouteQueryOptions) => {
    return index.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\Shop\CartController::index
* @see app/Http/Controllers/Shop/CartController.php:22
* @route '/cart'
*/
index.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Shop\CartController::index
* @see app/Http/Controllers/Shop/CartController.php:22
* @route '/cart'
*/
index.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: index.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\Shop\CartController::index
* @see app/Http/Controllers/Shop/CartController.php:22
* @route '/cart'
*/
const indexForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: index.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Shop\CartController::index
* @see app/Http/Controllers/Shop/CartController.php:22
* @route '/cart'
*/
indexForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: index.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Shop\CartController::index
* @see app/Http/Controllers/Shop/CartController.php:22
* @route '/cart'
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
* @see \App\Http\Controllers\Shop\CartController::add
* @see app/Http/Controllers/Shop/CartController.php:39
* @route '/cart'
*/
export const add = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: add.url(options),
    method: 'post',
})

add.definition = {
    methods: ["post"],
    url: '/cart',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\Shop\CartController::add
* @see app/Http/Controllers/Shop/CartController.php:39
* @route '/cart'
*/
add.url = (options?: RouteQueryOptions) => {
    return add.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\Shop\CartController::add
* @see app/Http/Controllers/Shop/CartController.php:39
* @route '/cart'
*/
add.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: add.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\Shop\CartController::add
* @see app/Http/Controllers/Shop/CartController.php:39
* @route '/cart'
*/
const addForm = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: add.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\Shop\CartController::add
* @see app/Http/Controllers/Shop/CartController.php:39
* @route '/cart'
*/
addForm.post = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: add.url(options),
    method: 'post',
})

add.form = addForm

/**
* @see \App\Http\Controllers\Shop\CartController::update
* @see app/Http/Controllers/Shop/CartController.php:84
* @route '/cart/{line}'
*/
export const update = (args: { line: string | number } | [line: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'patch'> => ({
    url: update.url(args, options),
    method: 'patch',
})

update.definition = {
    methods: ["patch"],
    url: '/cart/{line}',
} satisfies RouteDefinition<["patch"]>

/**
* @see \App\Http\Controllers\Shop\CartController::update
* @see app/Http/Controllers/Shop/CartController.php:84
* @route '/cart/{line}'
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
* @see \App\Http\Controllers\Shop\CartController::update
* @see app/Http/Controllers/Shop/CartController.php:84
* @route '/cart/{line}'
*/
update.patch = (args: { line: string | number } | [line: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'patch'> => ({
    url: update.url(args, options),
    method: 'patch',
})

/**
* @see \App\Http\Controllers\Shop\CartController::update
* @see app/Http/Controllers/Shop/CartController.php:84
* @route '/cart/{line}'
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
* @see \App\Http\Controllers\Shop\CartController::update
* @see app/Http/Controllers/Shop/CartController.php:84
* @route '/cart/{line}'
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
* @see \App\Http\Controllers\Shop\CartController::destroy
* @see app/Http/Controllers/Shop/CartController.php:119
* @route '/cart/{line}'
*/
export const destroy = (args: { line: string | number } | [line: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'delete'> => ({
    url: destroy.url(args, options),
    method: 'delete',
})

destroy.definition = {
    methods: ["delete"],
    url: '/cart/{line}',
} satisfies RouteDefinition<["delete"]>

/**
* @see \App\Http\Controllers\Shop\CartController::destroy
* @see app/Http/Controllers/Shop/CartController.php:119
* @route '/cart/{line}'
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
* @see \App\Http\Controllers\Shop\CartController::destroy
* @see app/Http/Controllers/Shop/CartController.php:119
* @route '/cart/{line}'
*/
destroy.delete = (args: { line: string | number } | [line: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'delete'> => ({
    url: destroy.url(args, options),
    method: 'delete',
})

/**
* @see \App\Http\Controllers\Shop\CartController::destroy
* @see app/Http/Controllers/Shop/CartController.php:119
* @route '/cart/{line}'
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
* @see \App\Http\Controllers\Shop\CartController::destroy
* @see app/Http/Controllers/Shop/CartController.php:119
* @route '/cart/{line}'
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
* @see \App\Http\Controllers\Shop\CartController::clear
* @see app/Http/Controllers/Shop/CartController.php:139
* @route '/cart'
*/
export const clear = (options?: RouteQueryOptions): RouteDefinition<'delete'> => ({
    url: clear.url(options),
    method: 'delete',
})

clear.definition = {
    methods: ["delete"],
    url: '/cart',
} satisfies RouteDefinition<["delete"]>

/**
* @see \App\Http\Controllers\Shop\CartController::clear
* @see app/Http/Controllers/Shop/CartController.php:139
* @route '/cart'
*/
clear.url = (options?: RouteQueryOptions) => {
    return clear.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\Shop\CartController::clear
* @see app/Http/Controllers/Shop/CartController.php:139
* @route '/cart'
*/
clear.delete = (options?: RouteQueryOptions): RouteDefinition<'delete'> => ({
    url: clear.url(options),
    method: 'delete',
})

/**
* @see \App\Http\Controllers\Shop\CartController::clear
* @see app/Http/Controllers/Shop/CartController.php:139
* @route '/cart'
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
* @see \App\Http\Controllers\Shop\CartController::clear
* @see app/Http/Controllers/Shop/CartController.php:139
* @route '/cart'
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

const CartController = { index, add, update, destroy, clear }

export default CartController