import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition } from './../../../../wayfinder'
/**
* @see \Shopper\Livewire\Pages\Account::__invoke
* @see vendor/shopper/framework/src/Livewire/Pages/Account.php:7
* @route '/cpanel/profile'
*/
const Account = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: Account.url(options),
    method: 'get',
})

Account.definition = {
    methods: ["get","head"],
    url: '/cpanel/profile',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \Shopper\Livewire\Pages\Account::__invoke
* @see vendor/shopper/framework/src/Livewire/Pages/Account.php:7
* @route '/cpanel/profile'
*/
Account.url = (options?: RouteQueryOptions) => {
    return Account.definition.url + queryParams(options)
}

/**
* @see \Shopper\Livewire\Pages\Account::__invoke
* @see vendor/shopper/framework/src/Livewire/Pages/Account.php:7
* @route '/cpanel/profile'
*/
Account.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: Account.url(options),
    method: 'get',
})

/**
* @see \Shopper\Livewire\Pages\Account::__invoke
* @see vendor/shopper/framework/src/Livewire/Pages/Account.php:7
* @route '/cpanel/profile'
*/
Account.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: Account.url(options),
    method: 'head',
})

/**
* @see \Shopper\Livewire\Pages\Account::__invoke
* @see vendor/shopper/framework/src/Livewire/Pages/Account.php:7
* @route '/cpanel/profile'
*/
const AccountForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: Account.url(options),
    method: 'get',
})

/**
* @see \Shopper\Livewire\Pages\Account::__invoke
* @see vendor/shopper/framework/src/Livewire/Pages/Account.php:7
* @route '/cpanel/profile'
*/
AccountForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: Account.url(options),
    method: 'get',
})

/**
* @see \Shopper\Livewire\Pages\Account::__invoke
* @see vendor/shopper/framework/src/Livewire/Pages/Account.php:7
* @route '/cpanel/profile'
*/
AccountForm.head = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: Account.url({
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

Account.form = AccountForm

export default Account