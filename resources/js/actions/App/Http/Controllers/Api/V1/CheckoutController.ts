import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition } from './../../../../../../wayfinder'
/**
* @see \App\Http\Controllers\Api\V1\CheckoutController::show
* @see app/Http/Controllers/Api/V1/CheckoutController.php:39
* @route '/api/v1/checkout'
*/
export const show = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: show.url(options),
    method: 'get',
})

show.definition = {
    methods: ["get","head"],
    url: '/api/v1/checkout',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\Api\V1\CheckoutController::show
* @see app/Http/Controllers/Api/V1/CheckoutController.php:39
* @route '/api/v1/checkout'
*/
show.url = (options?: RouteQueryOptions) => {
    return show.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\Api\V1\CheckoutController::show
* @see app/Http/Controllers/Api/V1/CheckoutController.php:39
* @route '/api/v1/checkout'
*/
show.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: show.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Api\V1\CheckoutController::show
* @see app/Http/Controllers/Api/V1/CheckoutController.php:39
* @route '/api/v1/checkout'
*/
show.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: show.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\Api\V1\CheckoutController::show
* @see app/Http/Controllers/Api/V1/CheckoutController.php:39
* @route '/api/v1/checkout'
*/
const showForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: show.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Api\V1\CheckoutController::show
* @see app/Http/Controllers/Api/V1/CheckoutController.php:39
* @route '/api/v1/checkout'
*/
showForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: show.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Api\V1\CheckoutController::show
* @see app/Http/Controllers/Api/V1/CheckoutController.php:39
* @route '/api/v1/checkout'
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
* @see \App\Http\Controllers\Api\V1\CheckoutController::saveShippingAddress
* @see app/Http/Controllers/Api/V1/CheckoutController.php:93
* @route '/api/v1/checkout/shipping-address'
*/
export const saveShippingAddress = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: saveShippingAddress.url(options),
    method: 'post',
})

saveShippingAddress.definition = {
    methods: ["post"],
    url: '/api/v1/checkout/shipping-address',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\Api\V1\CheckoutController::saveShippingAddress
* @see app/Http/Controllers/Api/V1/CheckoutController.php:93
* @route '/api/v1/checkout/shipping-address'
*/
saveShippingAddress.url = (options?: RouteQueryOptions) => {
    return saveShippingAddress.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\Api\V1\CheckoutController::saveShippingAddress
* @see app/Http/Controllers/Api/V1/CheckoutController.php:93
* @route '/api/v1/checkout/shipping-address'
*/
saveShippingAddress.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: saveShippingAddress.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\Api\V1\CheckoutController::saveShippingAddress
* @see app/Http/Controllers/Api/V1/CheckoutController.php:93
* @route '/api/v1/checkout/shipping-address'
*/
const saveShippingAddressForm = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: saveShippingAddress.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\Api\V1\CheckoutController::saveShippingAddress
* @see app/Http/Controllers/Api/V1/CheckoutController.php:93
* @route '/api/v1/checkout/shipping-address'
*/
saveShippingAddressForm.post = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: saveShippingAddress.url(options),
    method: 'post',
})

saveShippingAddress.form = saveShippingAddressForm

/**
* @see \App\Http\Controllers\Api\V1\CheckoutController::applySavedAddress
* @see app/Http/Controllers/Api/V1/CheckoutController.php:73
* @route '/api/v1/checkout/shipping-address/saved'
*/
export const applySavedAddress = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: applySavedAddress.url(options),
    method: 'post',
})

applySavedAddress.definition = {
    methods: ["post"],
    url: '/api/v1/checkout/shipping-address/saved',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\Api\V1\CheckoutController::applySavedAddress
* @see app/Http/Controllers/Api/V1/CheckoutController.php:73
* @route '/api/v1/checkout/shipping-address/saved'
*/
applySavedAddress.url = (options?: RouteQueryOptions) => {
    return applySavedAddress.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\Api\V1\CheckoutController::applySavedAddress
* @see app/Http/Controllers/Api/V1/CheckoutController.php:73
* @route '/api/v1/checkout/shipping-address/saved'
*/
applySavedAddress.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: applySavedAddress.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\Api\V1\CheckoutController::applySavedAddress
* @see app/Http/Controllers/Api/V1/CheckoutController.php:73
* @route '/api/v1/checkout/shipping-address/saved'
*/
const applySavedAddressForm = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: applySavedAddress.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\Api\V1\CheckoutController::applySavedAddress
* @see app/Http/Controllers/Api/V1/CheckoutController.php:73
* @route '/api/v1/checkout/shipping-address/saved'
*/
applySavedAddressForm.post = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: applySavedAddress.url(options),
    method: 'post',
})

applySavedAddress.form = applySavedAddressForm

/**
* @see \App\Http\Controllers\Api\V1\CheckoutController::saveShippingOption
* @see app/Http/Controllers/Api/V1/CheckoutController.php:151
* @route '/api/v1/checkout/shipping-option'
*/
export const saveShippingOption = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: saveShippingOption.url(options),
    method: 'post',
})

saveShippingOption.definition = {
    methods: ["post"],
    url: '/api/v1/checkout/shipping-option',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\Api\V1\CheckoutController::saveShippingOption
* @see app/Http/Controllers/Api/V1/CheckoutController.php:151
* @route '/api/v1/checkout/shipping-option'
*/
saveShippingOption.url = (options?: RouteQueryOptions) => {
    return saveShippingOption.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\Api\V1\CheckoutController::saveShippingOption
* @see app/Http/Controllers/Api/V1/CheckoutController.php:151
* @route '/api/v1/checkout/shipping-option'
*/
saveShippingOption.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: saveShippingOption.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\Api\V1\CheckoutController::saveShippingOption
* @see app/Http/Controllers/Api/V1/CheckoutController.php:151
* @route '/api/v1/checkout/shipping-option'
*/
const saveShippingOptionForm = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: saveShippingOption.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\Api\V1\CheckoutController::saveShippingOption
* @see app/Http/Controllers/Api/V1/CheckoutController.php:151
* @route '/api/v1/checkout/shipping-option'
*/
saveShippingOptionForm.post = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: saveShippingOption.url(options),
    method: 'post',
})

saveShippingOption.form = saveShippingOptionForm

/**
* @see \App\Http\Controllers\Api\V1\CheckoutController::placeOrder
* @see app/Http/Controllers/Api/V1/CheckoutController.php:201
* @route '/api/v1/checkout/place-order'
*/
export const placeOrder = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: placeOrder.url(options),
    method: 'post',
})

placeOrder.definition = {
    methods: ["post"],
    url: '/api/v1/checkout/place-order',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\Api\V1\CheckoutController::placeOrder
* @see app/Http/Controllers/Api/V1/CheckoutController.php:201
* @route '/api/v1/checkout/place-order'
*/
placeOrder.url = (options?: RouteQueryOptions) => {
    return placeOrder.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\Api\V1\CheckoutController::placeOrder
* @see app/Http/Controllers/Api/V1/CheckoutController.php:201
* @route '/api/v1/checkout/place-order'
*/
placeOrder.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: placeOrder.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\Api\V1\CheckoutController::placeOrder
* @see app/Http/Controllers/Api/V1/CheckoutController.php:201
* @route '/api/v1/checkout/place-order'
*/
const placeOrderForm = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: placeOrder.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\Api\V1\CheckoutController::placeOrder
* @see app/Http/Controllers/Api/V1/CheckoutController.php:201
* @route '/api/v1/checkout/place-order'
*/
placeOrderForm.post = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: placeOrder.url(options),
    method: 'post',
})

placeOrder.form = placeOrderForm

const CheckoutController = { show, saveShippingAddress, applySavedAddress, saveShippingOption, placeOrder }

export default CheckoutController