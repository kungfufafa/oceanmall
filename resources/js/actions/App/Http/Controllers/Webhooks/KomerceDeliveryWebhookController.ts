import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition } from './../../../../../wayfinder'
/**
* @see \App\Http\Controllers\Webhooks\KomerceDeliveryWebhookController::__invoke
* @see app/Http/Controllers/Webhooks/KomerceDeliveryWebhookController.php:25
* @route '/webhooks/komerce/delivery'
*/
const KomerceDeliveryWebhookController = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: KomerceDeliveryWebhookController.url(options),
    method: 'post',
})

KomerceDeliveryWebhookController.definition = {
    methods: ["post","put"],
    url: '/webhooks/komerce/delivery',
} satisfies RouteDefinition<["post","put"]>

/**
* @see \App\Http\Controllers\Webhooks\KomerceDeliveryWebhookController::__invoke
* @see app/Http/Controllers/Webhooks/KomerceDeliveryWebhookController.php:25
* @route '/webhooks/komerce/delivery'
*/
KomerceDeliveryWebhookController.url = (options?: RouteQueryOptions) => {
    return KomerceDeliveryWebhookController.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\Webhooks\KomerceDeliveryWebhookController::__invoke
* @see app/Http/Controllers/Webhooks/KomerceDeliveryWebhookController.php:25
* @route '/webhooks/komerce/delivery'
*/
KomerceDeliveryWebhookController.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: KomerceDeliveryWebhookController.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\Webhooks\KomerceDeliveryWebhookController::__invoke
* @see app/Http/Controllers/Webhooks/KomerceDeliveryWebhookController.php:25
* @route '/webhooks/komerce/delivery'
*/
KomerceDeliveryWebhookController.put = (options?: RouteQueryOptions): RouteDefinition<'put'> => ({
    url: KomerceDeliveryWebhookController.url(options),
    method: 'put',
})

/**
* @see \App\Http\Controllers\Webhooks\KomerceDeliveryWebhookController::__invoke
* @see app/Http/Controllers/Webhooks/KomerceDeliveryWebhookController.php:25
* @route '/webhooks/komerce/delivery'
*/
const KomerceDeliveryWebhookControllerForm = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: KomerceDeliveryWebhookController.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\Webhooks\KomerceDeliveryWebhookController::__invoke
* @see app/Http/Controllers/Webhooks/KomerceDeliveryWebhookController.php:25
* @route '/webhooks/komerce/delivery'
*/
KomerceDeliveryWebhookControllerForm.post = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: KomerceDeliveryWebhookController.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\Webhooks\KomerceDeliveryWebhookController::__invoke
* @see app/Http/Controllers/Webhooks/KomerceDeliveryWebhookController.php:25
* @route '/webhooks/komerce/delivery'
*/
KomerceDeliveryWebhookControllerForm.put = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: KomerceDeliveryWebhookController.url({
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'PUT',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'post',
})

KomerceDeliveryWebhookController.form = KomerceDeliveryWebhookControllerForm

export default KomerceDeliveryWebhookController