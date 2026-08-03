import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition } from './../../../../../wayfinder'
/**
* @see \Shopper\Livewire\Pages\Settings\PaymentMethods::__invoke
* @see vendor/shopper/framework/src/Livewire/Pages/Settings/PaymentMethods.php:7
* @route '/cpanel/setting/payment-methods'
*/
const PaymentMethods = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: PaymentMethods.url(options),
    method: 'get',
})

PaymentMethods.definition = {
    methods: ["get","head"],
    url: '/cpanel/setting/payment-methods',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \Shopper\Livewire\Pages\Settings\PaymentMethods::__invoke
* @see vendor/shopper/framework/src/Livewire/Pages/Settings/PaymentMethods.php:7
* @route '/cpanel/setting/payment-methods'
*/
PaymentMethods.url = (options?: RouteQueryOptions) => {
    return PaymentMethods.definition.url + queryParams(options)
}

/**
* @see \Shopper\Livewire\Pages\Settings\PaymentMethods::__invoke
* @see vendor/shopper/framework/src/Livewire/Pages/Settings/PaymentMethods.php:7
* @route '/cpanel/setting/payment-methods'
*/
PaymentMethods.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: PaymentMethods.url(options),
    method: 'get',
})

/**
* @see \Shopper\Livewire\Pages\Settings\PaymentMethods::__invoke
* @see vendor/shopper/framework/src/Livewire/Pages/Settings/PaymentMethods.php:7
* @route '/cpanel/setting/payment-methods'
*/
PaymentMethods.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: PaymentMethods.url(options),
    method: 'head',
})

/**
* @see \Shopper\Livewire\Pages\Settings\PaymentMethods::__invoke
* @see vendor/shopper/framework/src/Livewire/Pages/Settings/PaymentMethods.php:7
* @route '/cpanel/setting/payment-methods'
*/
const PaymentMethodsForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: PaymentMethods.url(options),
    method: 'get',
})

/**
* @see \Shopper\Livewire\Pages\Settings\PaymentMethods::__invoke
* @see vendor/shopper/framework/src/Livewire/Pages/Settings/PaymentMethods.php:7
* @route '/cpanel/setting/payment-methods'
*/
PaymentMethodsForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: PaymentMethods.url(options),
    method: 'get',
})

/**
* @see \Shopper\Livewire\Pages\Settings\PaymentMethods::__invoke
* @see vendor/shopper/framework/src/Livewire/Pages/Settings/PaymentMethods.php:7
* @route '/cpanel/setting/payment-methods'
*/
PaymentMethodsForm.head = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: PaymentMethods.url({
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

PaymentMethods.form = PaymentMethodsForm

export default PaymentMethods