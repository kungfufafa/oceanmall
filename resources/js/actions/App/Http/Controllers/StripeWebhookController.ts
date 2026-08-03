import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition } from './../../../../wayfinder'
/**
* @see \App\Http\Controllers\StripeWebhookController::__invoke
* @see app/Http/Controllers/StripeWebhookController.php:17
* @route '/webhooks/stripe'
*/
const StripeWebhookController = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: StripeWebhookController.url(options),
    method: 'post',
})

StripeWebhookController.definition = {
    methods: ["post"],
    url: '/webhooks/stripe',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\StripeWebhookController::__invoke
* @see app/Http/Controllers/StripeWebhookController.php:17
* @route '/webhooks/stripe'
*/
StripeWebhookController.url = (options?: RouteQueryOptions) => {
    return StripeWebhookController.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\StripeWebhookController::__invoke
* @see app/Http/Controllers/StripeWebhookController.php:17
* @route '/webhooks/stripe'
*/
StripeWebhookController.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: StripeWebhookController.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\StripeWebhookController::__invoke
* @see app/Http/Controllers/StripeWebhookController.php:17
* @route '/webhooks/stripe'
*/
const StripeWebhookControllerForm = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: StripeWebhookController.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\StripeWebhookController::__invoke
* @see app/Http/Controllers/StripeWebhookController.php:17
* @route '/webhooks/stripe'
*/
StripeWebhookControllerForm.post = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: StripeWebhookController.url(options),
    method: 'post',
})

StripeWebhookController.form = StripeWebhookControllerForm

export default StripeWebhookController