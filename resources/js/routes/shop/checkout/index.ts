import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition, applyUrlDefaults } from './../../../wayfinder'
/**
* @see \App\Http\Controllers\Shop\CheckoutController::index
* @see app/Http/Controllers/Shop/CheckoutController.php:41
* @route '/checkout'
*/
export const index = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
})

index.definition = {
    methods: ["get","head"],
    url: '/checkout',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\Shop\CheckoutController::index
* @see app/Http/Controllers/Shop/CheckoutController.php:41
* @route '/checkout'
*/
index.url = (options?: RouteQueryOptions) => {
    return index.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\Shop\CheckoutController::index
* @see app/Http/Controllers/Shop/CheckoutController.php:41
* @route '/checkout'
*/
index.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Shop\CheckoutController::index
* @see app/Http/Controllers/Shop/CheckoutController.php:41
* @route '/checkout'
*/
index.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: index.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\Shop\CheckoutController::index
* @see app/Http/Controllers/Shop/CheckoutController.php:41
* @route '/checkout'
*/
const indexForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: index.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Shop\CheckoutController::index
* @see app/Http/Controllers/Shop/CheckoutController.php:41
* @route '/checkout'
*/
indexForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: index.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Shop\CheckoutController::index
* @see app/Http/Controllers/Shop/CheckoutController.php:41
* @route '/checkout'
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
* @see \App\Http\Controllers\Shop\DestinationSearchController::__invoke
* @see app/Http/Controllers/Shop/DestinationSearchController.php:15
* @route '/checkout/destinations'
*/
export const destinations = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: destinations.url(options),
    method: 'get',
})

destinations.definition = {
    methods: ["get","head"],
    url: '/checkout/destinations',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\Shop\DestinationSearchController::__invoke
* @see app/Http/Controllers/Shop/DestinationSearchController.php:15
* @route '/checkout/destinations'
*/
destinations.url = (options?: RouteQueryOptions) => {
    return destinations.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\Shop\DestinationSearchController::__invoke
* @see app/Http/Controllers/Shop/DestinationSearchController.php:15
* @route '/checkout/destinations'
*/
destinations.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: destinations.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Shop\DestinationSearchController::__invoke
* @see app/Http/Controllers/Shop/DestinationSearchController.php:15
* @route '/checkout/destinations'
*/
destinations.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: destinations.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\Shop\DestinationSearchController::__invoke
* @see app/Http/Controllers/Shop/DestinationSearchController.php:15
* @route '/checkout/destinations'
*/
const destinationsForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: destinations.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Shop\DestinationSearchController::__invoke
* @see app/Http/Controllers/Shop/DestinationSearchController.php:15
* @route '/checkout/destinations'
*/
destinationsForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: destinations.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Shop\DestinationSearchController::__invoke
* @see app/Http/Controllers/Shop/DestinationSearchController.php:15
* @route '/checkout/destinations'
*/
destinationsForm.head = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: destinations.url({
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

destinations.form = destinationsForm

/**
* @see \App\Http\Controllers\Shop\CheckoutController::shippingAddress
* @see app/Http/Controllers/Shop/CheckoutController.php:131
* @route '/checkout/shipping-address'
*/
export const shippingAddress = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: shippingAddress.url(options),
    method: 'post',
})

shippingAddress.definition = {
    methods: ["post"],
    url: '/checkout/shipping-address',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\Shop\CheckoutController::shippingAddress
* @see app/Http/Controllers/Shop/CheckoutController.php:131
* @route '/checkout/shipping-address'
*/
shippingAddress.url = (options?: RouteQueryOptions) => {
    return shippingAddress.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\Shop\CheckoutController::shippingAddress
* @see app/Http/Controllers/Shop/CheckoutController.php:131
* @route '/checkout/shipping-address'
*/
shippingAddress.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: shippingAddress.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\Shop\CheckoutController::shippingAddress
* @see app/Http/Controllers/Shop/CheckoutController.php:131
* @route '/checkout/shipping-address'
*/
const shippingAddressForm = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: shippingAddress.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\Shop\CheckoutController::shippingAddress
* @see app/Http/Controllers/Shop/CheckoutController.php:131
* @route '/checkout/shipping-address'
*/
shippingAddressForm.post = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: shippingAddress.url(options),
    method: 'post',
})

shippingAddress.form = shippingAddressForm

/**
* @see \App\Http\Controllers\Shop\CheckoutController::shippingOption
* @see app/Http/Controllers/Shop/CheckoutController.php:177
* @route '/checkout/shipping-option'
*/
export const shippingOption = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: shippingOption.url(options),
    method: 'post',
})

shippingOption.definition = {
    methods: ["post"],
    url: '/checkout/shipping-option',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\Shop\CheckoutController::shippingOption
* @see app/Http/Controllers/Shop/CheckoutController.php:177
* @route '/checkout/shipping-option'
*/
shippingOption.url = (options?: RouteQueryOptions) => {
    return shippingOption.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\Shop\CheckoutController::shippingOption
* @see app/Http/Controllers/Shop/CheckoutController.php:177
* @route '/checkout/shipping-option'
*/
shippingOption.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: shippingOption.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\Shop\CheckoutController::shippingOption
* @see app/Http/Controllers/Shop/CheckoutController.php:177
* @route '/checkout/shipping-option'
*/
const shippingOptionForm = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: shippingOption.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\Shop\CheckoutController::shippingOption
* @see app/Http/Controllers/Shop/CheckoutController.php:177
* @route '/checkout/shipping-option'
*/
shippingOptionForm.post = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: shippingOption.url(options),
    method: 'post',
})

shippingOption.form = shippingOptionForm

/**
* @see \App\Http\Controllers\Shop\CheckoutController::preparePayment
* @see app/Http/Controllers/Shop/CheckoutController.php:313
* @route '/checkout/prepare-payment'
*/
export const preparePayment = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: preparePayment.url(options),
    method: 'post',
})

preparePayment.definition = {
    methods: ["post"],
    url: '/checkout/prepare-payment',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\Shop\CheckoutController::preparePayment
* @see app/Http/Controllers/Shop/CheckoutController.php:313
* @route '/checkout/prepare-payment'
*/
preparePayment.url = (options?: RouteQueryOptions) => {
    return preparePayment.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\Shop\CheckoutController::preparePayment
* @see app/Http/Controllers/Shop/CheckoutController.php:313
* @route '/checkout/prepare-payment'
*/
preparePayment.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: preparePayment.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\Shop\CheckoutController::preparePayment
* @see app/Http/Controllers/Shop/CheckoutController.php:313
* @route '/checkout/prepare-payment'
*/
const preparePaymentForm = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: preparePayment.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\Shop\CheckoutController::preparePayment
* @see app/Http/Controllers/Shop/CheckoutController.php:313
* @route '/checkout/prepare-payment'
*/
preparePaymentForm.post = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: preparePayment.url(options),
    method: 'post',
})

preparePayment.form = preparePaymentForm

/**
* @see \App\Http\Controllers\Shop\CheckoutController::placeOrder
* @see app/Http/Controllers/Shop/CheckoutController.php:383
* @route '/checkout/place-order'
*/
export const placeOrder = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: placeOrder.url(options),
    method: 'post',
})

placeOrder.definition = {
    methods: ["post"],
    url: '/checkout/place-order',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\Shop\CheckoutController::placeOrder
* @see app/Http/Controllers/Shop/CheckoutController.php:383
* @route '/checkout/place-order'
*/
placeOrder.url = (options?: RouteQueryOptions) => {
    return placeOrder.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\Shop\CheckoutController::placeOrder
* @see app/Http/Controllers/Shop/CheckoutController.php:383
* @route '/checkout/place-order'
*/
placeOrder.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: placeOrder.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\Shop\CheckoutController::placeOrder
* @see app/Http/Controllers/Shop/CheckoutController.php:383
* @route '/checkout/place-order'
*/
const placeOrderForm = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: placeOrder.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\Shop\CheckoutController::placeOrder
* @see app/Http/Controllers/Shop/CheckoutController.php:383
* @route '/checkout/place-order'
*/
placeOrderForm.post = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: placeOrder.url(options),
    method: 'post',
})

placeOrder.form = placeOrderForm

/**
* @see \App\Http\Controllers\Shop\CheckoutController::stripeReturn
* @see app/Http/Controllers/Shop/CheckoutController.php:468
* @route '/checkout/stripe-return'
*/
export const stripeReturn = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: stripeReturn.url(options),
    method: 'get',
})

stripeReturn.definition = {
    methods: ["get","head"],
    url: '/checkout/stripe-return',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\Shop\CheckoutController::stripeReturn
* @see app/Http/Controllers/Shop/CheckoutController.php:468
* @route '/checkout/stripe-return'
*/
stripeReturn.url = (options?: RouteQueryOptions) => {
    return stripeReturn.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\Shop\CheckoutController::stripeReturn
* @see app/Http/Controllers/Shop/CheckoutController.php:468
* @route '/checkout/stripe-return'
*/
stripeReturn.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: stripeReturn.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Shop\CheckoutController::stripeReturn
* @see app/Http/Controllers/Shop/CheckoutController.php:468
* @route '/checkout/stripe-return'
*/
stripeReturn.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: stripeReturn.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\Shop\CheckoutController::stripeReturn
* @see app/Http/Controllers/Shop/CheckoutController.php:468
* @route '/checkout/stripe-return'
*/
const stripeReturnForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: stripeReturn.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Shop\CheckoutController::stripeReturn
* @see app/Http/Controllers/Shop/CheckoutController.php:468
* @route '/checkout/stripe-return'
*/
stripeReturnForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: stripeReturn.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Shop\CheckoutController::stripeReturn
* @see app/Http/Controllers/Shop/CheckoutController.php:468
* @route '/checkout/stripe-return'
*/
stripeReturnForm.head = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: stripeReturn.url({
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

stripeReturn.form = stripeReturnForm

/**
* @see \App\Http\Controllers\Shop\StripePaymentController::__invoke
* @see app/Http/Controllers/Shop/StripePaymentController.php:15
* @route '/checkout/payment/{number}'
*/
export const stripe = (args: { number: string | number } | [number: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: stripe.url(args, options),
    method: 'get',
})

stripe.definition = {
    methods: ["get","head"],
    url: '/checkout/payment/{number}',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\Shop\StripePaymentController::__invoke
* @see app/Http/Controllers/Shop/StripePaymentController.php:15
* @route '/checkout/payment/{number}'
*/
stripe.url = (args: { number: string | number } | [number: string | number ] | string | number, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { number: args }
    }

    if (Array.isArray(args)) {
        args = {
            number: args[0],
        }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
        number: args.number,
    }

    return stripe.definition.url
            .replace('{number}', parsedArgs.number.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\Shop\StripePaymentController::__invoke
* @see app/Http/Controllers/Shop/StripePaymentController.php:15
* @route '/checkout/payment/{number}'
*/
stripe.get = (args: { number: string | number } | [number: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: stripe.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Shop\StripePaymentController::__invoke
* @see app/Http/Controllers/Shop/StripePaymentController.php:15
* @route '/checkout/payment/{number}'
*/
stripe.head = (args: { number: string | number } | [number: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: stripe.url(args, options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\Shop\StripePaymentController::__invoke
* @see app/Http/Controllers/Shop/StripePaymentController.php:15
* @route '/checkout/payment/{number}'
*/
const stripeForm = (args: { number: string | number } | [number: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: stripe.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Shop\StripePaymentController::__invoke
* @see app/Http/Controllers/Shop/StripePaymentController.php:15
* @route '/checkout/payment/{number}'
*/
stripeForm.get = (args: { number: string | number } | [number: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: stripe.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Shop\StripePaymentController::__invoke
* @see app/Http/Controllers/Shop/StripePaymentController.php:15
* @route '/checkout/payment/{number}'
*/
stripeForm.head = (args: { number: string | number } | [number: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: stripe.url(args, {
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

stripe.form = stripeForm

/**
* @see \App\Http\Controllers\Shop\CheckoutSuccessController::__invoke
* @see app/Http/Controllers/Shop/CheckoutSuccessController.php:14
* @route '/checkout/success/{order}'
*/
export const success = (args: { order: number | { id: number } } | [order: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: success.url(args, options),
    method: 'get',
})

success.definition = {
    methods: ["get","head"],
    url: '/checkout/success/{order}',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\Shop\CheckoutSuccessController::__invoke
* @see app/Http/Controllers/Shop/CheckoutSuccessController.php:14
* @route '/checkout/success/{order}'
*/
success.url = (args: { order: number | { id: number } } | [order: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { order: args }
    }

    if (typeof args === 'object' && !Array.isArray(args) && 'id' in args) {
        args = { order: args.id }
    }

    if (Array.isArray(args)) {
        args = {
            order: args[0],
        }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
        order: typeof args.order === 'object'
        ? args.order.id
        : args.order,
    }

    return success.definition.url
            .replace('{order}', parsedArgs.order.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\Shop\CheckoutSuccessController::__invoke
* @see app/Http/Controllers/Shop/CheckoutSuccessController.php:14
* @route '/checkout/success/{order}'
*/
success.get = (args: { order: number | { id: number } } | [order: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: success.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Shop\CheckoutSuccessController::__invoke
* @see app/Http/Controllers/Shop/CheckoutSuccessController.php:14
* @route '/checkout/success/{order}'
*/
success.head = (args: { order: number | { id: number } } | [order: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: success.url(args, options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\Shop\CheckoutSuccessController::__invoke
* @see app/Http/Controllers/Shop/CheckoutSuccessController.php:14
* @route '/checkout/success/{order}'
*/
const successForm = (args: { order: number | { id: number } } | [order: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: success.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Shop\CheckoutSuccessController::__invoke
* @see app/Http/Controllers/Shop/CheckoutSuccessController.php:14
* @route '/checkout/success/{order}'
*/
successForm.get = (args: { order: number | { id: number } } | [order: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: success.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Shop\CheckoutSuccessController::__invoke
* @see app/Http/Controllers/Shop/CheckoutSuccessController.php:14
* @route '/checkout/success/{order}'
*/
successForm.head = (args: { order: number | { id: number } } | [order: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: success.url(args, {
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

success.form = successForm

const checkout = {
    index: Object.assign(index, index),
    destinations: Object.assign(destinations, destinations),
    shippingAddress: Object.assign(shippingAddress, shippingAddress),
    shippingOption: Object.assign(shippingOption, shippingOption),
    preparePayment: Object.assign(preparePayment, preparePayment),
    placeOrder: Object.assign(placeOrder, placeOrder),
    stripeReturn: Object.assign(stripeReturn, stripeReturn),
    stripe: Object.assign(stripe, stripe),
    success: Object.assign(success, success),
}

export default checkout