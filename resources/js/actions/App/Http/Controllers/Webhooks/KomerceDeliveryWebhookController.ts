import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition } from './../../../../../wayfinder'
/**
* @see \App\Http\Controllers\Webhooks\KomerceDeliveryWebhookController::__invoke
* @see app/Http/Controllers/Webhooks/KomerceDeliveryWebhookController.php:24
* @route '/webhooks/komerce/delivery'
*/
const KomerceDeliveryWebhookController = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: KomerceDeliveryWebhookController.url(options),
    method: 'post',
})

KomerceDeliveryWebhookController.definition = {
    methods: ["post"],
    url: '/webhooks/komerce/delivery',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\Webhooks\KomerceDeliveryWebhookController::__invoke
* @see app/Http/Controllers/Webhooks/KomerceDeliveryWebhookController.php:24
* @route '/webhooks/komerce/delivery'
*/
KomerceDeliveryWebhookController.url = (options?: RouteQueryOptions) => {
    return KomerceDeliveryWebhookController.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\Webhooks\KomerceDeliveryWebhookController::__invoke
* @see app/Http/Controllers/Webhooks/KomerceDeliveryWebhookController.php:24
* @route '/webhooks/komerce/delivery'
*/
KomerceDeliveryWebhookController.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: KomerceDeliveryWebhookController.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\Webhooks\KomerceDeliveryWebhookController::__invoke
* @see app/Http/Controllers/Webhooks/KomerceDeliveryWebhookController.php:24
* @route '/webhooks/komerce/delivery'
*/
const KomerceDeliveryWebhookControllerForm = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: KomerceDeliveryWebhookController.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\Webhooks\KomerceDeliveryWebhookController::__invoke
* @see app/Http/Controllers/Webhooks/KomerceDeliveryWebhookController.php:24
* @route '/webhooks/komerce/delivery'
*/
KomerceDeliveryWebhookControllerForm.post = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: KomerceDeliveryWebhookController.url(options),
    method: 'post',
})

KomerceDeliveryWebhookController.form = KomerceDeliveryWebhookControllerForm

export default KomerceDeliveryWebhookController