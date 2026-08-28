import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition, applyUrlDefaults } from './../../../../../../wayfinder'
/**
* @see \App\Http\Controllers\Api\V1\AddressController::index
* @see app/Http/Controllers/Api/V1/AddressController.php:20
* @route '/api/v1/addresses'
*/
export const index = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
})

index.definition = {
    methods: ["get","head"],
    url: '/api/v1/addresses',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\Api\V1\AddressController::index
* @see app/Http/Controllers/Api/V1/AddressController.php:20
* @route '/api/v1/addresses'
*/
index.url = (options?: RouteQueryOptions) => {
    return index.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\Api\V1\AddressController::index
* @see app/Http/Controllers/Api/V1/AddressController.php:20
* @route '/api/v1/addresses'
*/
index.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Api\V1\AddressController::index
* @see app/Http/Controllers/Api/V1/AddressController.php:20
* @route '/api/v1/addresses'
*/
index.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: index.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\Api\V1\AddressController::index
* @see app/Http/Controllers/Api/V1/AddressController.php:20
* @route '/api/v1/addresses'
*/
const indexForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: index.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Api\V1\AddressController::index
* @see app/Http/Controllers/Api/V1/AddressController.php:20
* @route '/api/v1/addresses'
*/
indexForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: index.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Api\V1\AddressController::index
* @see app/Http/Controllers/Api/V1/AddressController.php:20
* @route '/api/v1/addresses'
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
* @see \App\Http\Controllers\Api\V1\AddressController::store
* @see app/Http/Controllers/Api/V1/AddressController.php:33
* @route '/api/v1/addresses'
*/
export const store = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: store.url(options),
    method: 'post',
})

store.definition = {
    methods: ["post"],
    url: '/api/v1/addresses',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\Api\V1\AddressController::store
* @see app/Http/Controllers/Api/V1/AddressController.php:33
* @route '/api/v1/addresses'
*/
store.url = (options?: RouteQueryOptions) => {
    return store.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\Api\V1\AddressController::store
* @see app/Http/Controllers/Api/V1/AddressController.php:33
* @route '/api/v1/addresses'
*/
store.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: store.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\Api\V1\AddressController::store
* @see app/Http/Controllers/Api/V1/AddressController.php:33
* @route '/api/v1/addresses'
*/
const storeForm = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: store.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\Api\V1\AddressController::store
* @see app/Http/Controllers/Api/V1/AddressController.php:33
* @route '/api/v1/addresses'
*/
storeForm.post = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: store.url(options),
    method: 'post',
})

store.form = storeForm

/**
* @see \App\Http\Controllers\Api\V1\AddressController::update
* @see app/Http/Controllers/Api/V1/AddressController.php:46
* @route '/api/v1/addresses/{address}'
*/
export const update = (args: { address: string | number } | [address: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'patch'> => ({
    url: update.url(args, options),
    method: 'patch',
})

update.definition = {
    methods: ["patch"],
    url: '/api/v1/addresses/{address}',
} satisfies RouteDefinition<["patch"]>

/**
* @see \App\Http\Controllers\Api\V1\AddressController::update
* @see app/Http/Controllers/Api/V1/AddressController.php:46
* @route '/api/v1/addresses/{address}'
*/
update.url = (args: { address: string | number } | [address: string | number ] | string | number, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { address: args }
    }

    if (Array.isArray(args)) {
        args = {
            address: args[0],
        }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
        address: args.address,
    }

    return update.definition.url
            .replace('{address}', parsedArgs.address.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\Api\V1\AddressController::update
* @see app/Http/Controllers/Api/V1/AddressController.php:46
* @route '/api/v1/addresses/{address}'
*/
update.patch = (args: { address: string | number } | [address: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'patch'> => ({
    url: update.url(args, options),
    method: 'patch',
})

/**
* @see \App\Http\Controllers\Api\V1\AddressController::update
* @see app/Http/Controllers/Api/V1/AddressController.php:46
* @route '/api/v1/addresses/{address}'
*/
const updateForm = (args: { address: string | number } | [address: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: update.url(args, {
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'PATCH',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'post',
})

/**
* @see \App\Http\Controllers\Api\V1\AddressController::update
* @see app/Http/Controllers/Api/V1/AddressController.php:46
* @route '/api/v1/addresses/{address}'
*/
updateForm.patch = (args: { address: string | number } | [address: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
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
* @see \App\Http\Controllers\Api\V1\AddressController::destroy
* @see app/Http/Controllers/Api/V1/AddressController.php:55
* @route '/api/v1/addresses/{address}'
*/
export const destroy = (args: { address: string | number } | [address: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'delete'> => ({
    url: destroy.url(args, options),
    method: 'delete',
})

destroy.definition = {
    methods: ["delete"],
    url: '/api/v1/addresses/{address}',
} satisfies RouteDefinition<["delete"]>

/**
* @see \App\Http\Controllers\Api\V1\AddressController::destroy
* @see app/Http/Controllers/Api/V1/AddressController.php:55
* @route '/api/v1/addresses/{address}'
*/
destroy.url = (args: { address: string | number } | [address: string | number ] | string | number, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { address: args }
    }

    if (Array.isArray(args)) {
        args = {
            address: args[0],
        }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
        address: args.address,
    }

    return destroy.definition.url
            .replace('{address}', parsedArgs.address.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\Api\V1\AddressController::destroy
* @see app/Http/Controllers/Api/V1/AddressController.php:55
* @route '/api/v1/addresses/{address}'
*/
destroy.delete = (args: { address: string | number } | [address: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'delete'> => ({
    url: destroy.url(args, options),
    method: 'delete',
})

/**
* @see \App\Http\Controllers\Api\V1\AddressController::destroy
* @see app/Http/Controllers/Api/V1/AddressController.php:55
* @route '/api/v1/addresses/{address}'
*/
const destroyForm = (args: { address: string | number } | [address: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: destroy.url(args, {
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'DELETE',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'post',
})

/**
* @see \App\Http\Controllers\Api\V1\AddressController::destroy
* @see app/Http/Controllers/Api/V1/AddressController.php:55
* @route '/api/v1/addresses/{address}'
*/
destroyForm.delete = (args: { address: string | number } | [address: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
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
* @see \App\Http\Controllers\Api\V1\AddressController::setDefaultShipping
* @see app/Http/Controllers/Api/V1/AddressController.php:62
* @route '/api/v1/addresses/{address}/default-shipping'
*/
export const setDefaultShipping = (args: { address: string | number } | [address: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'patch'> => ({
    url: setDefaultShipping.url(args, options),
    method: 'patch',
})

setDefaultShipping.definition = {
    methods: ["patch"],
    url: '/api/v1/addresses/{address}/default-shipping',
} satisfies RouteDefinition<["patch"]>

/**
* @see \App\Http\Controllers\Api\V1\AddressController::setDefaultShipping
* @see app/Http/Controllers/Api/V1/AddressController.php:62
* @route '/api/v1/addresses/{address}/default-shipping'
*/
setDefaultShipping.url = (args: { address: string | number } | [address: string | number ] | string | number, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { address: args }
    }

    if (Array.isArray(args)) {
        args = {
            address: args[0],
        }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
        address: args.address,
    }

    return setDefaultShipping.definition.url
            .replace('{address}', parsedArgs.address.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\Api\V1\AddressController::setDefaultShipping
* @see app/Http/Controllers/Api/V1/AddressController.php:62
* @route '/api/v1/addresses/{address}/default-shipping'
*/
setDefaultShipping.patch = (args: { address: string | number } | [address: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'patch'> => ({
    url: setDefaultShipping.url(args, options),
    method: 'patch',
})

/**
* @see \App\Http\Controllers\Api\V1\AddressController::setDefaultShipping
* @see app/Http/Controllers/Api/V1/AddressController.php:62
* @route '/api/v1/addresses/{address}/default-shipping'
*/
const setDefaultShippingForm = (args: { address: string | number } | [address: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: setDefaultShipping.url(args, {
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'PATCH',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'post',
})

/**
* @see \App\Http\Controllers\Api\V1\AddressController::setDefaultShipping
* @see app/Http/Controllers/Api/V1/AddressController.php:62
* @route '/api/v1/addresses/{address}/default-shipping'
*/
setDefaultShippingForm.patch = (args: { address: string | number } | [address: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: setDefaultShipping.url(args, {
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'PATCH',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'post',
})

setDefaultShipping.form = setDefaultShippingForm

/**
* @see \App\Http\Controllers\Api\V1\AddressController::setDefaultBilling
* @see app/Http/Controllers/Api/V1/AddressController.php:71
* @route '/api/v1/addresses/{address}/default-billing'
*/
export const setDefaultBilling = (args: { address: string | number } | [address: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'patch'> => ({
    url: setDefaultBilling.url(args, options),
    method: 'patch',
})

setDefaultBilling.definition = {
    methods: ["patch"],
    url: '/api/v1/addresses/{address}/default-billing',
} satisfies RouteDefinition<["patch"]>

/**
* @see \App\Http\Controllers\Api\V1\AddressController::setDefaultBilling
* @see app/Http/Controllers/Api/V1/AddressController.php:71
* @route '/api/v1/addresses/{address}/default-billing'
*/
setDefaultBilling.url = (args: { address: string | number } | [address: string | number ] | string | number, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { address: args }
    }

    if (Array.isArray(args)) {
        args = {
            address: args[0],
        }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
        address: args.address,
    }

    return setDefaultBilling.definition.url
            .replace('{address}', parsedArgs.address.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\Api\V1\AddressController::setDefaultBilling
* @see app/Http/Controllers/Api/V1/AddressController.php:71
* @route '/api/v1/addresses/{address}/default-billing'
*/
setDefaultBilling.patch = (args: { address: string | number } | [address: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'patch'> => ({
    url: setDefaultBilling.url(args, options),
    method: 'patch',
})

/**
* @see \App\Http\Controllers\Api\V1\AddressController::setDefaultBilling
* @see app/Http/Controllers/Api/V1/AddressController.php:71
* @route '/api/v1/addresses/{address}/default-billing'
*/
const setDefaultBillingForm = (args: { address: string | number } | [address: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: setDefaultBilling.url(args, {
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'PATCH',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'post',
})

/**
* @see \App\Http\Controllers\Api\V1\AddressController::setDefaultBilling
* @see app/Http/Controllers/Api/V1/AddressController.php:71
* @route '/api/v1/addresses/{address}/default-billing'
*/
setDefaultBillingForm.patch = (args: { address: string | number } | [address: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: setDefaultBilling.url(args, {
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'PATCH',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'post',
})

setDefaultBilling.form = setDefaultBillingForm

const AddressController = { index, store, update, destroy, setDefaultShipping, setDefaultBilling }

export default AddressController