import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition, applyUrlDefaults } from './../../../../../wayfinder'
/**
* @see \Shopper\Livewire\Pages\Order\Detail::__invoke
* @see vendor/shopper/framework/src/Livewire/Pages/Order/Detail.php:7
* @route '/cpanel/orders/{order}/detail'
*/
const Detail = (args: { order: string | number } | [order: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: Detail.url(args, options),
    method: 'get',
})

Detail.definition = {
    methods: ["get","head"],
    url: '/cpanel/orders/{order}/detail',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \Shopper\Livewire\Pages\Order\Detail::__invoke
* @see vendor/shopper/framework/src/Livewire/Pages/Order/Detail.php:7
* @route '/cpanel/orders/{order}/detail'
*/
Detail.url = (args: { order: string | number } | [order: string | number ] | string | number, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { order: args }
    }

    if (Array.isArray(args)) {
        args = {
            order: args[0],
        }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
        order: args.order,
    }

    return Detail.definition.url
            .replace('{order}', parsedArgs.order.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \Shopper\Livewire\Pages\Order\Detail::__invoke
* @see vendor/shopper/framework/src/Livewire/Pages/Order/Detail.php:7
* @route '/cpanel/orders/{order}/detail'
*/
Detail.get = (args: { order: string | number } | [order: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: Detail.url(args, options),
    method: 'get',
})

/**
* @see \Shopper\Livewire\Pages\Order\Detail::__invoke
* @see vendor/shopper/framework/src/Livewire/Pages/Order/Detail.php:7
* @route '/cpanel/orders/{order}/detail'
*/
Detail.head = (args: { order: string | number } | [order: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: Detail.url(args, options),
    method: 'head',
})

/**
* @see \Shopper\Livewire\Pages\Order\Detail::__invoke
* @see vendor/shopper/framework/src/Livewire/Pages/Order/Detail.php:7
* @route '/cpanel/orders/{order}/detail'
*/
const DetailForm = (args: { order: string | number } | [order: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: Detail.url(args, options),
    method: 'get',
})

/**
* @see \Shopper\Livewire\Pages\Order\Detail::__invoke
* @see vendor/shopper/framework/src/Livewire/Pages/Order/Detail.php:7
* @route '/cpanel/orders/{order}/detail'
*/
DetailForm.get = (args: { order: string | number } | [order: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: Detail.url(args, options),
    method: 'get',
})

/**
* @see \Shopper\Livewire\Pages\Order\Detail::__invoke
* @see vendor/shopper/framework/src/Livewire/Pages/Order/Detail.php:7
* @route '/cpanel/orders/{order}/detail'
*/
DetailForm.head = (args: { order: string | number } | [order: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: Detail.url(args, {
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

Detail.form = DetailForm

export default Detail