import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition } from './../../../../../wayfinder'
/**
* @see \App\Http\Controllers\Webhooks\KomerceQrislyWebhookController::__invoke
* @see app/Http/Controllers/Webhooks/KomerceQrislyWebhookController.php:21
* @route '/webhooks/komerce/qrisly'
*/
const KomerceQrislyWebhookController = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: KomerceQrislyWebhookController.url(options),
    method: 'post',
})

KomerceQrislyWebhookController.definition = {
    methods: ["post"],
    url: '/webhooks/komerce/qrisly',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\Webhooks\KomerceQrislyWebhookController::__invoke
* @see app/Http/Controllers/Webhooks/KomerceQrislyWebhookController.php:21
* @route '/webhooks/komerce/qrisly'
*/
KomerceQrislyWebhookController.url = (options?: RouteQueryOptions) => {
    return KomerceQrislyWebhookController.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\Webhooks\KomerceQrislyWebhookController::__invoke
* @see app/Http/Controllers/Webhooks/KomerceQrislyWebhookController.php:21
* @route '/webhooks/komerce/qrisly'
*/
KomerceQrislyWebhookController.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: KomerceQrislyWebhookController.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\Webhooks\KomerceQrislyWebhookController::__invoke
* @see app/Http/Controllers/Webhooks/KomerceQrislyWebhookController.php:21
* @route '/webhooks/komerce/qrisly'
*/
const KomerceQrislyWebhookControllerForm = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: KomerceQrislyWebhookController.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\Webhooks\KomerceQrislyWebhookController::__invoke
* @see app/Http/Controllers/Webhooks/KomerceQrislyWebhookController.php:21
* @route '/webhooks/komerce/qrisly'
*/
KomerceQrislyWebhookControllerForm.post = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: KomerceQrislyWebhookController.url(options),
    method: 'post',
})

KomerceQrislyWebhookController.form = KomerceQrislyWebhookControllerForm

export default KomerceQrislyWebhookController