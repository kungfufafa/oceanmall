import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition, applyUrlDefaults } from './../../../../../wayfinder'
/**
* @see \App\Http\Controllers\Account\AddressController::index
* @see app/Http/Controllers/Account/AddressController.php:21
* @route '/account/addresses'
*/
export const index = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
})

index.definition = {
    methods: ["get","head"],
    url: '/account/addresses',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\Account\AddressController::index
* @see app/Http/Controllers/Account/AddressController.php:21
* @route '/account/addresses'
*/
index.url = (options?: RouteQueryOptions) => {
    return index.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\Account\AddressController::index
* @see app/Http/Controllers/Account/AddressController.php:21
* @route '/account/addresses'
*/
index.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Account\AddressController::index
* @see app/Http/Controllers/Account/AddressController.php:21
* @route '/account/addresses'
*/
index.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: index.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\Account\AddressController::index
* @see app/Http/Controllers/Account/AddressController.php:21
* @route '/account/addresses'
*/
const indexForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: index.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Account\AddressController::index
* @see app/Http/Controllers/Account/AddressController.php:21
* @route '/account/addresses'
*/
indexForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: index.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Account\AddressController::index
* @see app/Http/Controllers/Account/AddressController.php:21
* @route '/account/addresses'
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
* @see \App\Http\Controllers\Account\AddressController::store
* @see app/Http/Controllers/Account/AddressController.php:42
* @route '/account/addresses'
*/
export const store = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: store.url(options),
    method: 'post',
})

store.definition = {
    methods: ["post"],
    url: '/account/addresses',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\Account\AddressController::store
* @see app/Http/Controllers/Account/AddressController.php:42
* @route '/account/addresses'
*/
store.url = (options?: RouteQueryOptions) => {
    return store.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\Account\AddressController::store
* @see app/Http/Controllers/Account/AddressController.php:42
* @route '/account/addresses'
*/
store.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: store.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\Account\AddressController::store
* @see app/Http/Controllers/Account/AddressController.php:42
* @route '/account/addresses'
*/
const storeForm = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: store.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\Account\AddressController::store
* @see app/Http/Controllers/Account/AddressController.php:42
* @route '/account/addresses'
*/
storeForm.post = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: store.url(options),
    method: 'post',
})

store.form = storeForm

/**
* @see \App\Http\Controllers\Account\AddressController::update
* @see app/Http/Controllers/Account/AddressController.php:51
* @route '/account/addresses/{address}'
*/
export const update = (args: { address: number | { id: number } } | [address: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'patch'> => ({
    url: update.url(args, options),
    method: 'patch',
})

update.definition = {
    methods: ["patch"],
    url: '/account/addresses/{address}',
} satisfies RouteDefinition<["patch"]>

/**
* @see \App\Http\Controllers\Account\AddressController::update
* @see app/Http/Controllers/Account/AddressController.php:51
* @route '/account/addresses/{address}'
*/
update.url = (args: { address: number | { id: number } } | [address: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { address: args }
    }

    if (typeof args === 'object' && !Array.isArray(args) && 'id' in args) {
        args = { address: args.id }
    }

    if (Array.isArray(args)) {
        args = {
            address: args[0],
        }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
        address: typeof args.address === 'object'
        ? args.address.id
        : args.address,
    }

    return update.definition.url
            .replace('{address}', parsedArgs.address.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\Account\AddressController::update
* @see app/Http/Controllers/Account/AddressController.php:51
* @route '/account/addresses/{address}'
*/
update.patch = (args: { address: number | { id: number } } | [address: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'patch'> => ({
    url: update.url(args, options),
    method: 'patch',
})

/**
* @see \App\Http\Controllers\Account\AddressController::update
* @see app/Http/Controllers/Account/AddressController.php:51
* @route '/account/addresses/{address}'
*/
const updateForm = (args: { address: number | { id: number } } | [address: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: update.url(args, {
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'PATCH',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'post',
})

/**
* @see \App\Http\Controllers\Account\AddressController::update
* @see app/Http/Controllers/Account/AddressController.php:51
* @route '/account/addresses/{address}'
*/
updateForm.patch = (args: { address: number | { id: number } } | [address: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
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
* @see \App\Http\Controllers\Account\AddressController::destroy
* @see app/Http/Controllers/Account/AddressController.php:62
* @route '/account/addresses/{address}'
*/
export const destroy = (args: { address: number | { id: number } } | [address: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'delete'> => ({
    url: destroy.url(args, options),
    method: 'delete',
})

destroy.definition = {
    methods: ["delete"],
    url: '/account/addresses/{address}',
} satisfies RouteDefinition<["delete"]>

/**
* @see \App\Http\Controllers\Account\AddressController::destroy
* @see app/Http/Controllers/Account/AddressController.php:62
* @route '/account/addresses/{address}'
*/
destroy.url = (args: { address: number | { id: number } } | [address: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { address: args }
    }

    if (typeof args === 'object' && !Array.isArray(args) && 'id' in args) {
        args = { address: args.id }
    }

    if (Array.isArray(args)) {
        args = {
            address: args[0],
        }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
        address: typeof args.address === 'object'
        ? args.address.id
        : args.address,
    }

    return destroy.definition.url
            .replace('{address}', parsedArgs.address.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\Account\AddressController::destroy
* @see app/Http/Controllers/Account/AddressController.php:62
* @route '/account/addresses/{address}'
*/
destroy.delete = (args: { address: number | { id: number } } | [address: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'delete'> => ({
    url: destroy.url(args, options),
    method: 'delete',
})

/**
* @see \App\Http\Controllers\Account\AddressController::destroy
* @see app/Http/Controllers/Account/AddressController.php:62
* @route '/account/addresses/{address}'
*/
const destroyForm = (args: { address: number | { id: number } } | [address: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: destroy.url(args, {
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'DELETE',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'post',
})

/**
* @see \App\Http\Controllers\Account\AddressController::destroy
* @see app/Http/Controllers/Account/AddressController.php:62
* @route '/account/addresses/{address}'
*/
destroyForm.delete = (args: { address: number | { id: number } } | [address: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
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
* @see \App\Http\Controllers\Account\AddressController::setDefaultShipping
* @see app/Http/Controllers/Account/AddressController.php:71
* @route '/account/addresses/{address}/default-shipping'
*/
export const setDefaultShipping = (args: { address: number | { id: number } } | [address: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'patch'> => ({
    url: setDefaultShipping.url(args, options),
    method: 'patch',
})

setDefaultShipping.definition = {
    methods: ["patch"],
    url: '/account/addresses/{address}/default-shipping',
} satisfies RouteDefinition<["patch"]>

/**
* @see \App\Http\Controllers\Account\AddressController::setDefaultShipping
* @see app/Http/Controllers/Account/AddressController.php:71
* @route '/account/addresses/{address}/default-shipping'
*/
setDefaultShipping.url = (args: { address: number | { id: number } } | [address: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { address: args }
    }

    if (typeof args === 'object' && !Array.isArray(args) && 'id' in args) {
        args = { address: args.id }
    }

    if (Array.isArray(args)) {
        args = {
            address: args[0],
        }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
        address: typeof args.address === 'object'
        ? args.address.id
        : args.address,
    }

    return setDefaultShipping.definition.url
            .replace('{address}', parsedArgs.address.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\Account\AddressController::setDefaultShipping
* @see app/Http/Controllers/Account/AddressController.php:71
* @route '/account/addresses/{address}/default-shipping'
*/
setDefaultShipping.patch = (args: { address: number | { id: number } } | [address: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'patch'> => ({
    url: setDefaultShipping.url(args, options),
    method: 'patch',
})

/**
* @see \App\Http\Controllers\Account\AddressController::setDefaultShipping
* @see app/Http/Controllers/Account/AddressController.php:71
* @route '/account/addresses/{address}/default-shipping'
*/
const setDefaultShippingForm = (args: { address: number | { id: number } } | [address: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: setDefaultShipping.url(args, {
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'PATCH',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'post',
})

/**
* @see \App\Http\Controllers\Account\AddressController::setDefaultShipping
* @see app/Http/Controllers/Account/AddressController.php:71
* @route '/account/addresses/{address}/default-shipping'
*/
setDefaultShippingForm.patch = (args: { address: number | { id: number } } | [address: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
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
* @see \App\Http\Controllers\Account\AddressController::setDefaultBilling
* @see app/Http/Controllers/Account/AddressController.php:80
* @route '/account/addresses/{address}/default-billing'
*/
export const setDefaultBilling = (args: { address: number | { id: number } } | [address: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'patch'> => ({
    url: setDefaultBilling.url(args, options),
    method: 'patch',
})

setDefaultBilling.definition = {
    methods: ["patch"],
    url: '/account/addresses/{address}/default-billing',
} satisfies RouteDefinition<["patch"]>

/**
* @see \App\Http\Controllers\Account\AddressController::setDefaultBilling
* @see app/Http/Controllers/Account/AddressController.php:80
* @route '/account/addresses/{address}/default-billing'
*/
setDefaultBilling.url = (args: { address: number | { id: number } } | [address: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { address: args }
    }

    if (typeof args === 'object' && !Array.isArray(args) && 'id' in args) {
        args = { address: args.id }
    }

    if (Array.isArray(args)) {
        args = {
            address: args[0],
        }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
        address: typeof args.address === 'object'
        ? args.address.id
        : args.address,
    }

    return setDefaultBilling.definition.url
            .replace('{address}', parsedArgs.address.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\Account\AddressController::setDefaultBilling
* @see app/Http/Controllers/Account/AddressController.php:80
* @route '/account/addresses/{address}/default-billing'
*/
setDefaultBilling.patch = (args: { address: number | { id: number } } | [address: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'patch'> => ({
    url: setDefaultBilling.url(args, options),
    method: 'patch',
})

/**
* @see \App\Http\Controllers\Account\AddressController::setDefaultBilling
* @see app/Http/Controllers/Account/AddressController.php:80
* @route '/account/addresses/{address}/default-billing'
*/
const setDefaultBillingForm = (args: { address: number | { id: number } } | [address: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: setDefaultBilling.url(args, {
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'PATCH',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'post',
})

/**
* @see \App\Http\Controllers\Account\AddressController::setDefaultBilling
* @see app/Http/Controllers/Account/AddressController.php:80
* @route '/account/addresses/{address}/default-billing'
*/
setDefaultBillingForm.patch = (args: { address: number | { id: number } } | [address: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
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