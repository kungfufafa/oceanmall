import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition, applyUrlDefaults } from './../../../wayfinder'
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
* @see \App\Http\Controllers\Account\AddressController::defaultShipping
* @see app/Http/Controllers/Account/AddressController.php:71
* @route '/account/addresses/{address}/default-shipping'
*/
export const defaultShipping = (args: { address: number | { id: number } } | [address: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'patch'> => ({
    url: defaultShipping.url(args, options),
    method: 'patch',
})

defaultShipping.definition = {
    methods: ["patch"],
    url: '/account/addresses/{address}/default-shipping',
} satisfies RouteDefinition<["patch"]>

/**
* @see \App\Http\Controllers\Account\AddressController::defaultShipping
* @see app/Http/Controllers/Account/AddressController.php:71
* @route '/account/addresses/{address}/default-shipping'
*/
defaultShipping.url = (args: { address: number | { id: number } } | [address: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions) => {
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

    return defaultShipping.definition.url
            .replace('{address}', parsedArgs.address.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\Account\AddressController::defaultShipping
* @see app/Http/Controllers/Account/AddressController.php:71
* @route '/account/addresses/{address}/default-shipping'
*/
defaultShipping.patch = (args: { address: number | { id: number } } | [address: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'patch'> => ({
    url: defaultShipping.url(args, options),
    method: 'patch',
})

/**
* @see \App\Http\Controllers\Account\AddressController::defaultShipping
* @see app/Http/Controllers/Account/AddressController.php:71
* @route '/account/addresses/{address}/default-shipping'
*/
const defaultShippingForm = (args: { address: number | { id: number } } | [address: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: defaultShipping.url(args, {
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'PATCH',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'post',
})

/**
* @see \App\Http\Controllers\Account\AddressController::defaultShipping
* @see app/Http/Controllers/Account/AddressController.php:71
* @route '/account/addresses/{address}/default-shipping'
*/
defaultShippingForm.patch = (args: { address: number | { id: number } } | [address: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: defaultShipping.url(args, {
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'PATCH',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'post',
})

defaultShipping.form = defaultShippingForm

/**
* @see \App\Http\Controllers\Account\AddressController::defaultBilling
* @see app/Http/Controllers/Account/AddressController.php:80
* @route '/account/addresses/{address}/default-billing'
*/
export const defaultBilling = (args: { address: number | { id: number } } | [address: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'patch'> => ({
    url: defaultBilling.url(args, options),
    method: 'patch',
})

defaultBilling.definition = {
    methods: ["patch"],
    url: '/account/addresses/{address}/default-billing',
} satisfies RouteDefinition<["patch"]>

/**
* @see \App\Http\Controllers\Account\AddressController::defaultBilling
* @see app/Http/Controllers/Account/AddressController.php:80
* @route '/account/addresses/{address}/default-billing'
*/
defaultBilling.url = (args: { address: number | { id: number } } | [address: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions) => {
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

    return defaultBilling.definition.url
            .replace('{address}', parsedArgs.address.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\Account\AddressController::defaultBilling
* @see app/Http/Controllers/Account/AddressController.php:80
* @route '/account/addresses/{address}/default-billing'
*/
defaultBilling.patch = (args: { address: number | { id: number } } | [address: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'patch'> => ({
    url: defaultBilling.url(args, options),
    method: 'patch',
})

/**
* @see \App\Http\Controllers\Account\AddressController::defaultBilling
* @see app/Http/Controllers/Account/AddressController.php:80
* @route '/account/addresses/{address}/default-billing'
*/
const defaultBillingForm = (args: { address: number | { id: number } } | [address: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: defaultBilling.url(args, {
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'PATCH',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'post',
})

/**
* @see \App\Http\Controllers\Account\AddressController::defaultBilling
* @see app/Http/Controllers/Account/AddressController.php:80
* @route '/account/addresses/{address}/default-billing'
*/
defaultBillingForm.patch = (args: { address: number | { id: number } } | [address: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: defaultBilling.url(args, {
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'PATCH',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'post',
})

defaultBilling.form = defaultBillingForm

const addresses = {
    store: Object.assign(store, store),
    update: Object.assign(update, update),
    destroy: Object.assign(destroy, destroy),
    defaultShipping: Object.assign(defaultShipping, defaultShipping),
    defaultBilling: Object.assign(defaultBilling, defaultBilling),
}

export default addresses