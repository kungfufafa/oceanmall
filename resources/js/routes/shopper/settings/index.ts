import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition } from './../../../wayfinder'
import locations859476 from './locations'
import users48860f from './users'
/**
* @see \Shopper\Livewire\Pages\Settings\Index::__invoke
* @see vendor/shopper/framework/src/Livewire/Pages/Settings/Index.php:7
* @route '/cpanel/setting'
*/
export const index = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
})

index.definition = {
    methods: ["get","head"],
    url: '/cpanel/setting',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \Shopper\Livewire\Pages\Settings\Index::__invoke
* @see vendor/shopper/framework/src/Livewire/Pages/Settings/Index.php:7
* @route '/cpanel/setting'
*/
index.url = (options?: RouteQueryOptions) => {
    return index.definition.url + queryParams(options)
}

/**
* @see \Shopper\Livewire\Pages\Settings\Index::__invoke
* @see vendor/shopper/framework/src/Livewire/Pages/Settings/Index.php:7
* @route '/cpanel/setting'
*/
index.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
})

/**
* @see \Shopper\Livewire\Pages\Settings\Index::__invoke
* @see vendor/shopper/framework/src/Livewire/Pages/Settings/Index.php:7
* @route '/cpanel/setting'
*/
index.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: index.url(options),
    method: 'head',
})

/**
* @see \Shopper\Livewire\Pages\Settings\Index::__invoke
* @see vendor/shopper/framework/src/Livewire/Pages/Settings/Index.php:7
* @route '/cpanel/setting'
*/
const indexForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: index.url(options),
    method: 'get',
})

/**
* @see \Shopper\Livewire\Pages\Settings\Index::__invoke
* @see vendor/shopper/framework/src/Livewire/Pages/Settings/Index.php:7
* @route '/cpanel/setting'
*/
indexForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: index.url(options),
    method: 'get',
})

/**
* @see \Shopper\Livewire\Pages\Settings\Index::__invoke
* @see vendor/shopper/framework/src/Livewire/Pages/Settings/Index.php:7
* @route '/cpanel/setting'
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
* @see \Shopper\Livewire\Pages\Settings\General::__invoke
* @see vendor/shopper/framework/src/Livewire/Pages/Settings/General.php:7
* @route '/cpanel/setting/general'
*/
export const shop = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: shop.url(options),
    method: 'get',
})

shop.definition = {
    methods: ["get","head"],
    url: '/cpanel/setting/general',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \Shopper\Livewire\Pages\Settings\General::__invoke
* @see vendor/shopper/framework/src/Livewire/Pages/Settings/General.php:7
* @route '/cpanel/setting/general'
*/
shop.url = (options?: RouteQueryOptions) => {
    return shop.definition.url + queryParams(options)
}

/**
* @see \Shopper\Livewire\Pages\Settings\General::__invoke
* @see vendor/shopper/framework/src/Livewire/Pages/Settings/General.php:7
* @route '/cpanel/setting/general'
*/
shop.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: shop.url(options),
    method: 'get',
})

/**
* @see \Shopper\Livewire\Pages\Settings\General::__invoke
* @see vendor/shopper/framework/src/Livewire/Pages/Settings/General.php:7
* @route '/cpanel/setting/general'
*/
shop.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: shop.url(options),
    method: 'head',
})

/**
* @see \Shopper\Livewire\Pages\Settings\General::__invoke
* @see vendor/shopper/framework/src/Livewire/Pages/Settings/General.php:7
* @route '/cpanel/setting/general'
*/
const shopForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: shop.url(options),
    method: 'get',
})

/**
* @see \Shopper\Livewire\Pages\Settings\General::__invoke
* @see vendor/shopper/framework/src/Livewire/Pages/Settings/General.php:7
* @route '/cpanel/setting/general'
*/
shopForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: shop.url(options),
    method: 'get',
})

/**
* @see \Shopper\Livewire\Pages\Settings\General::__invoke
* @see vendor/shopper/framework/src/Livewire/Pages/Settings/General.php:7
* @route '/cpanel/setting/general'
*/
shopForm.head = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: shop.url({
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

shop.form = shopForm

/**
* @see \Shopper\Livewire\Pages\Settings\Locations\Index::__invoke
* @see vendor/shopper/framework/src/Livewire/Pages/Settings/Locations/Index.php:7
* @route '/cpanel/setting/locations'
*/
export const locations = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: locations.url(options),
    method: 'get',
})

locations.definition = {
    methods: ["get","head"],
    url: '/cpanel/setting/locations',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \Shopper\Livewire\Pages\Settings\Locations\Index::__invoke
* @see vendor/shopper/framework/src/Livewire/Pages/Settings/Locations/Index.php:7
* @route '/cpanel/setting/locations'
*/
locations.url = (options?: RouteQueryOptions) => {
    return locations.definition.url + queryParams(options)
}

/**
* @see \Shopper\Livewire\Pages\Settings\Locations\Index::__invoke
* @see vendor/shopper/framework/src/Livewire/Pages/Settings/Locations/Index.php:7
* @route '/cpanel/setting/locations'
*/
locations.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: locations.url(options),
    method: 'get',
})

/**
* @see \Shopper\Livewire\Pages\Settings\Locations\Index::__invoke
* @see vendor/shopper/framework/src/Livewire/Pages/Settings/Locations/Index.php:7
* @route '/cpanel/setting/locations'
*/
locations.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: locations.url(options),
    method: 'head',
})

/**
* @see \Shopper\Livewire\Pages\Settings\Locations\Index::__invoke
* @see vendor/shopper/framework/src/Livewire/Pages/Settings/Locations/Index.php:7
* @route '/cpanel/setting/locations'
*/
const locationsForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: locations.url(options),
    method: 'get',
})

/**
* @see \Shopper\Livewire\Pages\Settings\Locations\Index::__invoke
* @see vendor/shopper/framework/src/Livewire/Pages/Settings/Locations/Index.php:7
* @route '/cpanel/setting/locations'
*/
locationsForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: locations.url(options),
    method: 'get',
})

/**
* @see \Shopper\Livewire\Pages\Settings\Locations\Index::__invoke
* @see vendor/shopper/framework/src/Livewire/Pages/Settings/Locations/Index.php:7
* @route '/cpanel/setting/locations'
*/
locationsForm.head = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: locations.url({
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

locations.form = locationsForm

/**
* @see \Shopper\Livewire\Pages\Settings\LegalPage::__invoke
* @see vendor/shopper/framework/src/Livewire/Pages/Settings/LegalPage.php:7
* @route '/cpanel/setting/legal'
*/
export const legal = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: legal.url(options),
    method: 'get',
})

legal.definition = {
    methods: ["get","head"],
    url: '/cpanel/setting/legal',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \Shopper\Livewire\Pages\Settings\LegalPage::__invoke
* @see vendor/shopper/framework/src/Livewire/Pages/Settings/LegalPage.php:7
* @route '/cpanel/setting/legal'
*/
legal.url = (options?: RouteQueryOptions) => {
    return legal.definition.url + queryParams(options)
}

/**
* @see \Shopper\Livewire\Pages\Settings\LegalPage::__invoke
* @see vendor/shopper/framework/src/Livewire/Pages/Settings/LegalPage.php:7
* @route '/cpanel/setting/legal'
*/
legal.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: legal.url(options),
    method: 'get',
})

/**
* @see \Shopper\Livewire\Pages\Settings\LegalPage::__invoke
* @see vendor/shopper/framework/src/Livewire/Pages/Settings/LegalPage.php:7
* @route '/cpanel/setting/legal'
*/
legal.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: legal.url(options),
    method: 'head',
})

/**
* @see \Shopper\Livewire\Pages\Settings\LegalPage::__invoke
* @see vendor/shopper/framework/src/Livewire/Pages/Settings/LegalPage.php:7
* @route '/cpanel/setting/legal'
*/
const legalForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: legal.url(options),
    method: 'get',
})

/**
* @see \Shopper\Livewire\Pages\Settings\LegalPage::__invoke
* @see vendor/shopper/framework/src/Livewire/Pages/Settings/LegalPage.php:7
* @route '/cpanel/setting/legal'
*/
legalForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: legal.url(options),
    method: 'get',
})

/**
* @see \Shopper\Livewire\Pages\Settings\LegalPage::__invoke
* @see vendor/shopper/framework/src/Livewire/Pages/Settings/LegalPage.php:7
* @route '/cpanel/setting/legal'
*/
legalForm.head = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: legal.url({
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

legal.form = legalForm

/**
* @see vendor/laravel/framework/src/Illuminate/Routing/RouteAction.php:63
* @route '/cpanel/setting/analytics'
*/
export const analytics = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: analytics.url(options),
    method: 'get',
})

analytics.definition = {
    methods: ["get","head"],
    url: '/cpanel/setting/analytics',
} satisfies RouteDefinition<["get","head"]>

/**
* @see vendor/laravel/framework/src/Illuminate/Routing/RouteAction.php:63
* @route '/cpanel/setting/analytics'
*/
analytics.url = (options?: RouteQueryOptions) => {
    return analytics.definition.url + queryParams(options)
}

/**
* @see vendor/laravel/framework/src/Illuminate/Routing/RouteAction.php:63
* @route '/cpanel/setting/analytics'
*/
analytics.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: analytics.url(options),
    method: 'get',
})

/**
* @see vendor/laravel/framework/src/Illuminate/Routing/RouteAction.php:63
* @route '/cpanel/setting/analytics'
*/
analytics.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: analytics.url(options),
    method: 'head',
})

/**
* @see vendor/laravel/framework/src/Illuminate/Routing/RouteAction.php:63
* @route '/cpanel/setting/analytics'
*/
const analyticsForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: analytics.url(options),
    method: 'get',
})

/**
* @see vendor/laravel/framework/src/Illuminate/Routing/RouteAction.php:63
* @route '/cpanel/setting/analytics'
*/
analyticsForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: analytics.url(options),
    method: 'get',
})

/**
* @see vendor/laravel/framework/src/Illuminate/Routing/RouteAction.php:63
* @route '/cpanel/setting/analytics'
*/
analyticsForm.head = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: analytics.url({
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

analytics.form = analyticsForm

/**
* @see \Shopper\Livewire\Pages\Settings\PaymentMethods::__invoke
* @see vendor/shopper/framework/src/Livewire/Pages/Settings/PaymentMethods.php:7
* @route '/cpanel/setting/payment-methods'
*/
export const paymentMethods = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: paymentMethods.url(options),
    method: 'get',
})

paymentMethods.definition = {
    methods: ["get","head"],
    url: '/cpanel/setting/payment-methods',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \Shopper\Livewire\Pages\Settings\PaymentMethods::__invoke
* @see vendor/shopper/framework/src/Livewire/Pages/Settings/PaymentMethods.php:7
* @route '/cpanel/setting/payment-methods'
*/
paymentMethods.url = (options?: RouteQueryOptions) => {
    return paymentMethods.definition.url + queryParams(options)
}

/**
* @see \Shopper\Livewire\Pages\Settings\PaymentMethods::__invoke
* @see vendor/shopper/framework/src/Livewire/Pages/Settings/PaymentMethods.php:7
* @route '/cpanel/setting/payment-methods'
*/
paymentMethods.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: paymentMethods.url(options),
    method: 'get',
})

/**
* @see \Shopper\Livewire\Pages\Settings\PaymentMethods::__invoke
* @see vendor/shopper/framework/src/Livewire/Pages/Settings/PaymentMethods.php:7
* @route '/cpanel/setting/payment-methods'
*/
paymentMethods.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: paymentMethods.url(options),
    method: 'head',
})

/**
* @see \Shopper\Livewire\Pages\Settings\PaymentMethods::__invoke
* @see vendor/shopper/framework/src/Livewire/Pages/Settings/PaymentMethods.php:7
* @route '/cpanel/setting/payment-methods'
*/
const paymentMethodsForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: paymentMethods.url(options),
    method: 'get',
})

/**
* @see \Shopper\Livewire\Pages\Settings\PaymentMethods::__invoke
* @see vendor/shopper/framework/src/Livewire/Pages/Settings/PaymentMethods.php:7
* @route '/cpanel/setting/payment-methods'
*/
paymentMethodsForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: paymentMethods.url(options),
    method: 'get',
})

/**
* @see \Shopper\Livewire\Pages\Settings\PaymentMethods::__invoke
* @see vendor/shopper/framework/src/Livewire/Pages/Settings/PaymentMethods.php:7
* @route '/cpanel/setting/payment-methods'
*/
paymentMethodsForm.head = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: paymentMethods.url({
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

paymentMethods.form = paymentMethodsForm

/**
* @see \Shopper\Livewire\Pages\Settings\Carriers::__invoke
* @see vendor/shopper/framework/src/Livewire/Pages/Settings/Carriers.php:7
* @route '/cpanel/setting/carriers'
*/
export const carriers = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: carriers.url(options),
    method: 'get',
})

carriers.definition = {
    methods: ["get","head"],
    url: '/cpanel/setting/carriers',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \Shopper\Livewire\Pages\Settings\Carriers::__invoke
* @see vendor/shopper/framework/src/Livewire/Pages/Settings/Carriers.php:7
* @route '/cpanel/setting/carriers'
*/
carriers.url = (options?: RouteQueryOptions) => {
    return carriers.definition.url + queryParams(options)
}

/**
* @see \Shopper\Livewire\Pages\Settings\Carriers::__invoke
* @see vendor/shopper/framework/src/Livewire/Pages/Settings/Carriers.php:7
* @route '/cpanel/setting/carriers'
*/
carriers.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: carriers.url(options),
    method: 'get',
})

/**
* @see \Shopper\Livewire\Pages\Settings\Carriers::__invoke
* @see vendor/shopper/framework/src/Livewire/Pages/Settings/Carriers.php:7
* @route '/cpanel/setting/carriers'
*/
carriers.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: carriers.url(options),
    method: 'head',
})

/**
* @see \Shopper\Livewire\Pages\Settings\Carriers::__invoke
* @see vendor/shopper/framework/src/Livewire/Pages/Settings/Carriers.php:7
* @route '/cpanel/setting/carriers'
*/
const carriersForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: carriers.url(options),
    method: 'get',
})

/**
* @see \Shopper\Livewire\Pages\Settings\Carriers::__invoke
* @see vendor/shopper/framework/src/Livewire/Pages/Settings/Carriers.php:7
* @route '/cpanel/setting/carriers'
*/
carriersForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: carriers.url(options),
    method: 'get',
})

/**
* @see \Shopper\Livewire\Pages\Settings\Carriers::__invoke
* @see vendor/shopper/framework/src/Livewire/Pages/Settings/Carriers.php:7
* @route '/cpanel/setting/carriers'
*/
carriersForm.head = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: carriers.url({
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

carriers.form = carriersForm

/**
* @see \Shopper\Livewire\Pages\Settings\Zones::__invoke
* @see vendor/shopper/framework/src/Livewire/Pages/Settings/Zones.php:7
* @route '/cpanel/setting/zones'
*/
export const zones = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: zones.url(options),
    method: 'get',
})

zones.definition = {
    methods: ["get","head"],
    url: '/cpanel/setting/zones',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \Shopper\Livewire\Pages\Settings\Zones::__invoke
* @see vendor/shopper/framework/src/Livewire/Pages/Settings/Zones.php:7
* @route '/cpanel/setting/zones'
*/
zones.url = (options?: RouteQueryOptions) => {
    return zones.definition.url + queryParams(options)
}

/**
* @see \Shopper\Livewire\Pages\Settings\Zones::__invoke
* @see vendor/shopper/framework/src/Livewire/Pages/Settings/Zones.php:7
* @route '/cpanel/setting/zones'
*/
zones.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: zones.url(options),
    method: 'get',
})

/**
* @see \Shopper\Livewire\Pages\Settings\Zones::__invoke
* @see vendor/shopper/framework/src/Livewire/Pages/Settings/Zones.php:7
* @route '/cpanel/setting/zones'
*/
zones.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: zones.url(options),
    method: 'head',
})

/**
* @see \Shopper\Livewire\Pages\Settings\Zones::__invoke
* @see vendor/shopper/framework/src/Livewire/Pages/Settings/Zones.php:7
* @route '/cpanel/setting/zones'
*/
const zonesForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: zones.url(options),
    method: 'get',
})

/**
* @see \Shopper\Livewire\Pages\Settings\Zones::__invoke
* @see vendor/shopper/framework/src/Livewire/Pages/Settings/Zones.php:7
* @route '/cpanel/setting/zones'
*/
zonesForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: zones.url(options),
    method: 'get',
})

/**
* @see \Shopper\Livewire\Pages\Settings\Zones::__invoke
* @see vendor/shopper/framework/src/Livewire/Pages/Settings/Zones.php:7
* @route '/cpanel/setting/zones'
*/
zonesForm.head = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: zones.url({
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

zones.form = zonesForm

/**
* @see \Shopper\Livewire\Pages\Settings\Taxes::__invoke
* @see vendor/shopper/framework/src/Livewire/Pages/Settings/Taxes.php:7
* @route '/cpanel/setting/taxes'
*/
export const taxes = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: taxes.url(options),
    method: 'get',
})

taxes.definition = {
    methods: ["get","head"],
    url: '/cpanel/setting/taxes',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \Shopper\Livewire\Pages\Settings\Taxes::__invoke
* @see vendor/shopper/framework/src/Livewire/Pages/Settings/Taxes.php:7
* @route '/cpanel/setting/taxes'
*/
taxes.url = (options?: RouteQueryOptions) => {
    return taxes.definition.url + queryParams(options)
}

/**
* @see \Shopper\Livewire\Pages\Settings\Taxes::__invoke
* @see vendor/shopper/framework/src/Livewire/Pages/Settings/Taxes.php:7
* @route '/cpanel/setting/taxes'
*/
taxes.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: taxes.url(options),
    method: 'get',
})

/**
* @see \Shopper\Livewire\Pages\Settings\Taxes::__invoke
* @see vendor/shopper/framework/src/Livewire/Pages/Settings/Taxes.php:7
* @route '/cpanel/setting/taxes'
*/
taxes.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: taxes.url(options),
    method: 'head',
})

/**
* @see \Shopper\Livewire\Pages\Settings\Taxes::__invoke
* @see vendor/shopper/framework/src/Livewire/Pages/Settings/Taxes.php:7
* @route '/cpanel/setting/taxes'
*/
const taxesForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: taxes.url(options),
    method: 'get',
})

/**
* @see \Shopper\Livewire\Pages\Settings\Taxes::__invoke
* @see vendor/shopper/framework/src/Livewire/Pages/Settings/Taxes.php:7
* @route '/cpanel/setting/taxes'
*/
taxesForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: taxes.url(options),
    method: 'get',
})

/**
* @see \Shopper\Livewire\Pages\Settings\Taxes::__invoke
* @see vendor/shopper/framework/src/Livewire/Pages/Settings/Taxes.php:7
* @route '/cpanel/setting/taxes'
*/
taxesForm.head = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: taxes.url({
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

taxes.form = taxesForm

/**
* @see \Shopper\Livewire\Pages\Settings\Currencies::__invoke
* @see vendor/shopper/framework/src/Livewire/Pages/Settings/Currencies.php:7
* @route '/cpanel/setting/currencies'
*/
export const currencies = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: currencies.url(options),
    method: 'get',
})

currencies.definition = {
    methods: ["get","head"],
    url: '/cpanel/setting/currencies',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \Shopper\Livewire\Pages\Settings\Currencies::__invoke
* @see vendor/shopper/framework/src/Livewire/Pages/Settings/Currencies.php:7
* @route '/cpanel/setting/currencies'
*/
currencies.url = (options?: RouteQueryOptions) => {
    return currencies.definition.url + queryParams(options)
}

/**
* @see \Shopper\Livewire\Pages\Settings\Currencies::__invoke
* @see vendor/shopper/framework/src/Livewire/Pages/Settings/Currencies.php:7
* @route '/cpanel/setting/currencies'
*/
currencies.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: currencies.url(options),
    method: 'get',
})

/**
* @see \Shopper\Livewire\Pages\Settings\Currencies::__invoke
* @see vendor/shopper/framework/src/Livewire/Pages/Settings/Currencies.php:7
* @route '/cpanel/setting/currencies'
*/
currencies.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: currencies.url(options),
    method: 'head',
})

/**
* @see \Shopper\Livewire\Pages\Settings\Currencies::__invoke
* @see vendor/shopper/framework/src/Livewire/Pages/Settings/Currencies.php:7
* @route '/cpanel/setting/currencies'
*/
const currenciesForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: currencies.url(options),
    method: 'get',
})

/**
* @see \Shopper\Livewire\Pages\Settings\Currencies::__invoke
* @see vendor/shopper/framework/src/Livewire/Pages/Settings/Currencies.php:7
* @route '/cpanel/setting/currencies'
*/
currenciesForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: currencies.url(options),
    method: 'get',
})

/**
* @see \Shopper\Livewire\Pages\Settings\Currencies::__invoke
* @see vendor/shopper/framework/src/Livewire/Pages/Settings/Currencies.php:7
* @route '/cpanel/setting/currencies'
*/
currenciesForm.head = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: currencies.url({
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

currencies.form = currenciesForm

/**
* @see \Shopper\Livewire\Pages\Settings\Team\Index::__invoke
* @see vendor/shopper/framework/src/Livewire/Pages/Settings/Team/Index.php:7
* @route '/cpanel/setting/team'
*/
export const users = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: users.url(options),
    method: 'get',
})

users.definition = {
    methods: ["get","head"],
    url: '/cpanel/setting/team',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \Shopper\Livewire\Pages\Settings\Team\Index::__invoke
* @see vendor/shopper/framework/src/Livewire/Pages/Settings/Team/Index.php:7
* @route '/cpanel/setting/team'
*/
users.url = (options?: RouteQueryOptions) => {
    return users.definition.url + queryParams(options)
}

/**
* @see \Shopper\Livewire\Pages\Settings\Team\Index::__invoke
* @see vendor/shopper/framework/src/Livewire/Pages/Settings/Team/Index.php:7
* @route '/cpanel/setting/team'
*/
users.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: users.url(options),
    method: 'get',
})

/**
* @see \Shopper\Livewire\Pages\Settings\Team\Index::__invoke
* @see vendor/shopper/framework/src/Livewire/Pages/Settings/Team/Index.php:7
* @route '/cpanel/setting/team'
*/
users.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: users.url(options),
    method: 'head',
})

/**
* @see \Shopper\Livewire\Pages\Settings\Team\Index::__invoke
* @see vendor/shopper/framework/src/Livewire/Pages/Settings/Team/Index.php:7
* @route '/cpanel/setting/team'
*/
const usersForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: users.url(options),
    method: 'get',
})

/**
* @see \Shopper\Livewire\Pages\Settings\Team\Index::__invoke
* @see vendor/shopper/framework/src/Livewire/Pages/Settings/Team/Index.php:7
* @route '/cpanel/setting/team'
*/
usersForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: users.url(options),
    method: 'get',
})

/**
* @see \Shopper\Livewire\Pages\Settings\Team\Index::__invoke
* @see vendor/shopper/framework/src/Livewire/Pages/Settings/Team/Index.php:7
* @route '/cpanel/setting/team'
*/
usersForm.head = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: users.url({
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

users.form = usersForm

const settings = {
    index: Object.assign(index, index),
    shop: Object.assign(shop, shop),
    locations: Object.assign(locations, locations859476),
    legal: Object.assign(legal, legal),
    analytics: Object.assign(analytics, analytics),
    paymentMethods: Object.assign(paymentMethods, paymentMethods),
    carriers: Object.assign(carriers, carriers),
    zones: Object.assign(zones, zones),
    taxes: Object.assign(taxes, taxes),
    currencies: Object.assign(currencies, currencies),
    users: Object.assign(users, users48860f),
}

export default settings