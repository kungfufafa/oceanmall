import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition } from './../../../wayfinder'
/**
* @see \App\Http\Controllers\Webhooks\KomercePaymentWebhookController::__invoke
* @see app/Http/Controllers/Webhooks/KomercePaymentWebhookController.php:16
* @route '/webhooks/komerce/payment'
*/
export const payment = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: payment.url(options),
    method: 'post',
})

payment.definition = {
    methods: ["post"],
    url: '/webhooks/komerce/payment',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\Webhooks\KomercePaymentWebhookController::__invoke
* @see app/Http/Controllers/Webhooks/KomercePaymentWebhookController.php:16
* @route '/webhooks/komerce/payment'
*/
payment.url = (options?: RouteQueryOptions) => {
    return payment.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\Webhooks\KomercePaymentWebhookController::__invoke
* @see app/Http/Controllers/Webhooks/KomercePaymentWebhookController.php:16
* @route '/webhooks/komerce/payment'
*/
payment.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: payment.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\Webhooks\KomercePaymentWebhookController::__invoke
* @see app/Http/Controllers/Webhooks/KomercePaymentWebhookController.php:16
* @route '/webhooks/komerce/payment'
*/
const paymentForm = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: payment.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\Webhooks\KomercePaymentWebhookController::__invoke
* @see app/Http/Controllers/Webhooks/KomercePaymentWebhookController.php:16
* @route '/webhooks/komerce/payment'
*/
paymentForm.post = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: payment.url(options),
    method: 'post',
})

payment.form = paymentForm

/**
* @see \App\Http\Controllers\Webhooks\KomerceDeliveryWebhookController::__invoke
* @see app/Http/Controllers/Webhooks/KomerceDeliveryWebhookController.php:25
* @route '/webhooks/komerce/delivery'
*/
export const delivery = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: delivery.url(options),
    method: 'post',
})

delivery.definition = {
    methods: ["post","put"],
    url: '/webhooks/komerce/delivery',
} satisfies RouteDefinition<["post","put"]>

/**
* @see \App\Http\Controllers\Webhooks\KomerceDeliveryWebhookController::__invoke
* @see app/Http/Controllers/Webhooks/KomerceDeliveryWebhookController.php:25
* @route '/webhooks/komerce/delivery'
*/
delivery.url = (options?: RouteQueryOptions) => {
    return delivery.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\Webhooks\KomerceDeliveryWebhookController::__invoke
* @see app/Http/Controllers/Webhooks/KomerceDeliveryWebhookController.php:25
* @route '/webhooks/komerce/delivery'
*/
delivery.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: delivery.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\Webhooks\KomerceDeliveryWebhookController::__invoke
* @see app/Http/Controllers/Webhooks/KomerceDeliveryWebhookController.php:25
* @route '/webhooks/komerce/delivery'
*/
delivery.put = (options?: RouteQueryOptions): RouteDefinition<'put'> => ({
    url: delivery.url(options),
    method: 'put',
})

/**
* @see \App\Http\Controllers\Webhooks\KomerceDeliveryWebhookController::__invoke
* @see app/Http/Controllers/Webhooks/KomerceDeliveryWebhookController.php:25
* @route '/webhooks/komerce/delivery'
*/
const deliveryForm = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: delivery.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\Webhooks\KomerceDeliveryWebhookController::__invoke
* @see app/Http/Controllers/Webhooks/KomerceDeliveryWebhookController.php:25
* @route '/webhooks/komerce/delivery'
*/
deliveryForm.post = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: delivery.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\Webhooks\KomerceDeliveryWebhookController::__invoke
* @see app/Http/Controllers/Webhooks/KomerceDeliveryWebhookController.php:25
* @route '/webhooks/komerce/delivery'
*/
deliveryForm.put = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: delivery.url({
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'PUT',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'post',
})

delivery.form = deliveryForm

/**
* @see \App\Http\Controllers\Webhooks\KomerceQrislyWebhookController::__invoke
* @see app/Http/Controllers/Webhooks/KomerceQrislyWebhookController.php:24
* @route '/webhooks/komerce/qrisly'
*/
export const qrisly = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: qrisly.url(options),
    method: 'post',
})

qrisly.definition = {
    methods: ["post"],
    url: '/webhooks/komerce/qrisly',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\Webhooks\KomerceQrislyWebhookController::__invoke
* @see app/Http/Controllers/Webhooks/KomerceQrislyWebhookController.php:24
* @route '/webhooks/komerce/qrisly'
*/
qrisly.url = (options?: RouteQueryOptions) => {
    return qrisly.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\Webhooks\KomerceQrislyWebhookController::__invoke
* @see app/Http/Controllers/Webhooks/KomerceQrislyWebhookController.php:24
* @route '/webhooks/komerce/qrisly'
*/
qrisly.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: qrisly.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\Webhooks\KomerceQrislyWebhookController::__invoke
* @see app/Http/Controllers/Webhooks/KomerceQrislyWebhookController.php:24
* @route '/webhooks/komerce/qrisly'
*/
const qrislyForm = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: qrisly.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\Webhooks\KomerceQrislyWebhookController::__invoke
* @see app/Http/Controllers/Webhooks/KomerceQrislyWebhookController.php:24
* @route '/webhooks/komerce/qrisly'
*/
qrislyForm.post = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: qrisly.url(options),
    method: 'post',
})

qrisly.form = qrislyForm

const komerce = {
    payment: Object.assign(payment, payment),
    delivery: Object.assign(delivery, delivery),
    qrisly: Object.assign(qrisly, qrisly),
}

export default komerce