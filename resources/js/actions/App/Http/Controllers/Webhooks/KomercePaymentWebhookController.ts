import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition } from './../../../../../wayfinder'
/**
* @see \App\Http\Controllers\Webhooks\KomercePaymentWebhookController::__invoke
* @see app/Http/Controllers/Webhooks/KomercePaymentWebhookController.php:17
* @route '/webhooks/komerce/payment'
*/
const KomercePaymentWebhookController = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: KomercePaymentWebhookController.url(options),
    method: 'post',
})

KomercePaymentWebhookController.definition = {
    methods: ["post"],
    url: '/webhooks/komerce/payment',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\Webhooks\KomercePaymentWebhookController::__invoke
* @see app/Http/Controllers/Webhooks/KomercePaymentWebhookController.php:17
* @route '/webhooks/komerce/payment'
*/
KomercePaymentWebhookController.url = (options?: RouteQueryOptions) => {
    return KomercePaymentWebhookController.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\Webhooks\KomercePaymentWebhookController::__invoke
* @see app/Http/Controllers/Webhooks/KomercePaymentWebhookController.php:17
* @route '/webhooks/komerce/payment'
*/
KomercePaymentWebhookController.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: KomercePaymentWebhookController.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\Webhooks\KomercePaymentWebhookController::__invoke
* @see app/Http/Controllers/Webhooks/KomercePaymentWebhookController.php:17
* @route '/webhooks/komerce/payment'
*/
const KomercePaymentWebhookControllerForm = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: KomercePaymentWebhookController.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\Webhooks\KomercePaymentWebhookController::__invoke
* @see app/Http/Controllers/Webhooks/KomercePaymentWebhookController.php:17
* @route '/webhooks/komerce/payment'
*/
KomercePaymentWebhookControllerForm.post = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: KomercePaymentWebhookController.url(options),
    method: 'post',
})

KomercePaymentWebhookController.form = KomercePaymentWebhookControllerForm

export default KomercePaymentWebhookController