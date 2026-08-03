import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition } from './../../wayfinder'
/**
* @see \App\Http\Controllers\StripeWebhookController::__invoke
* @see app/Http/Controllers/StripeWebhookController.php:17
* @route '/webhooks/stripe'
*/
export const stripe = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: stripe.url(options),
    method: 'post',
})

stripe.definition = {
    methods: ["post"],
    url: '/webhooks/stripe',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\StripeWebhookController::__invoke
* @see app/Http/Controllers/StripeWebhookController.php:17
* @route '/webhooks/stripe'
*/
stripe.url = (options?: RouteQueryOptions) => {
    return stripe.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\StripeWebhookController::__invoke
* @see app/Http/Controllers/StripeWebhookController.php:17
* @route '/webhooks/stripe'
*/
stripe.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: stripe.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\StripeWebhookController::__invoke
* @see app/Http/Controllers/StripeWebhookController.php:17
* @route '/webhooks/stripe'
*/
const stripeForm = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: stripe.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\StripeWebhookController::__invoke
* @see app/Http/Controllers/StripeWebhookController.php:17
* @route '/webhooks/stripe'
*/
stripeForm.post = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: stripe.url(options),
    method: 'post',
})

stripe.form = stripeForm

const webhooks = {
    stripe: Object.assign(stripe, stripe),
}

export default webhooks