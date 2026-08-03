import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition } from './../../wayfinder'
import ordersB47e5f from './orders'
import addresses2498c9 from './addresses'
/**
* @see \App\Http\Controllers\Account\OrderController::orders
* @see app/Http/Controllers/Account/OrderController.php:17
* @route '/account/orders'
*/
export const orders = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: orders.url(options),
    method: 'get',
})

orders.definition = {
    methods: ["get","head"],
    url: '/account/orders',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\Account\OrderController::orders
* @see app/Http/Controllers/Account/OrderController.php:17
* @route '/account/orders'
*/
orders.url = (options?: RouteQueryOptions) => {
    return orders.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\Account\OrderController::orders
* @see app/Http/Controllers/Account/OrderController.php:17
* @route '/account/orders'
*/
orders.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: orders.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Account\OrderController::orders
* @see app/Http/Controllers/Account/OrderController.php:17
* @route '/account/orders'
*/
orders.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: orders.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\Account\OrderController::orders
* @see app/Http/Controllers/Account/OrderController.php:17
* @route '/account/orders'
*/
const ordersForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: orders.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Account\OrderController::orders
* @see app/Http/Controllers/Account/OrderController.php:17
* @route '/account/orders'
*/
ordersForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: orders.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Account\OrderController::orders
* @see app/Http/Controllers/Account/OrderController.php:17
* @route '/account/orders'
*/
ordersForm.head = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: orders.url({
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

orders.form = ordersForm

/**
* @see \App\Http\Controllers\Account\AddressController::addresses
* @see app/Http/Controllers/Account/AddressController.php:21
* @route '/account/addresses'
*/
export const addresses = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: addresses.url(options),
    method: 'get',
})

addresses.definition = {
    methods: ["get","head"],
    url: '/account/addresses',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\Account\AddressController::addresses
* @see app/Http/Controllers/Account/AddressController.php:21
* @route '/account/addresses'
*/
addresses.url = (options?: RouteQueryOptions) => {
    return addresses.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\Account\AddressController::addresses
* @see app/Http/Controllers/Account/AddressController.php:21
* @route '/account/addresses'
*/
addresses.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: addresses.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Account\AddressController::addresses
* @see app/Http/Controllers/Account/AddressController.php:21
* @route '/account/addresses'
*/
addresses.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: addresses.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\Account\AddressController::addresses
* @see app/Http/Controllers/Account/AddressController.php:21
* @route '/account/addresses'
*/
const addressesForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: addresses.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Account\AddressController::addresses
* @see app/Http/Controllers/Account/AddressController.php:21
* @route '/account/addresses'
*/
addressesForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: addresses.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Account\AddressController::addresses
* @see app/Http/Controllers/Account/AddressController.php:21
* @route '/account/addresses'
*/
addressesForm.head = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: addresses.url({
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

addresses.form = addressesForm

const account = {
    orders: Object.assign(orders, ordersB47e5f),
    addresses: Object.assign(addresses, addresses2498c9),
}

export default account