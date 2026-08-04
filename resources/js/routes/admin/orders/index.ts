import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition, applyUrlDefaults } from './../../../wayfinder'
/**
* @see \App\Http\Controllers\Admin\OrderShowController::__invoke
* @see app/Http/Controllers/Admin/OrderShowController.php:18
* @route '/admin/orders/{order}'
*/
export const show = (args: { order: number | { id: number } } | [order: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: show.url(args, options),
    method: 'get',
})

show.definition = {
    methods: ["get","head"],
    url: '/admin/orders/{order}',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\Admin\OrderShowController::__invoke
* @see app/Http/Controllers/Admin/OrderShowController.php:18
* @route '/admin/orders/{order}'
*/
show.url = (args: { order: number | { id: number } } | [order: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { order: args }
    }

    if (typeof args === 'object' && !Array.isArray(args) && 'id' in args) {
        args = { order: args.id }
    }

    if (Array.isArray(args)) {
        args = {
            order: args[0],
        }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
        order: typeof args.order === 'object'
        ? args.order.id
        : args.order,
    }

    return show.definition.url
            .replace('{order}', parsedArgs.order.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\Admin\OrderShowController::__invoke
* @see app/Http/Controllers/Admin/OrderShowController.php:18
* @route '/admin/orders/{order}'
*/
show.get = (args: { order: number | { id: number } } | [order: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: show.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Admin\OrderShowController::__invoke
* @see app/Http/Controllers/Admin/OrderShowController.php:18
* @route '/admin/orders/{order}'
*/
show.head = (args: { order: number | { id: number } } | [order: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: show.url(args, options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\Admin\OrderShowController::__invoke
* @see app/Http/Controllers/Admin/OrderShowController.php:18
* @route '/admin/orders/{order}'
*/
const showForm = (args: { order: number | { id: number } } | [order: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: show.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Admin\OrderShowController::__invoke
* @see app/Http/Controllers/Admin/OrderShowController.php:18
* @route '/admin/orders/{order}'
*/
showForm.get = (args: { order: number | { id: number } } | [order: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: show.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Admin\OrderShowController::__invoke
* @see app/Http/Controllers/Admin/OrderShowController.php:18
* @route '/admin/orders/{order}'
*/
showForm.head = (args: { order: number | { id: number } } | [order: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: show.url(args, {
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

show.form = showForm

/**
* @see \App\Http\Controllers\Admin\OverrideAllocationController::__invoke
* @see app/Http/Controllers/Admin/OverrideAllocationController.php:18
* @route '/admin/orders/{order}/override-allocation'
*/
export const overrideAllocation = (args: { order: number | { id: number } } | [order: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: overrideAllocation.url(args, options),
    method: 'post',
})

overrideAllocation.definition = {
    methods: ["post"],
    url: '/admin/orders/{order}/override-allocation',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\Admin\OverrideAllocationController::__invoke
* @see app/Http/Controllers/Admin/OverrideAllocationController.php:18
* @route '/admin/orders/{order}/override-allocation'
*/
overrideAllocation.url = (args: { order: number | { id: number } } | [order: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { order: args }
    }

    if (typeof args === 'object' && !Array.isArray(args) && 'id' in args) {
        args = { order: args.id }
    }

    if (Array.isArray(args)) {
        args = {
            order: args[0],
        }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
        order: typeof args.order === 'object'
        ? args.order.id
        : args.order,
    }

    return overrideAllocation.definition.url
            .replace('{order}', parsedArgs.order.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\Admin\OverrideAllocationController::__invoke
* @see app/Http/Controllers/Admin/OverrideAllocationController.php:18
* @route '/admin/orders/{order}/override-allocation'
*/
overrideAllocation.post = (args: { order: number | { id: number } } | [order: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: overrideAllocation.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\Admin\OverrideAllocationController::__invoke
* @see app/Http/Controllers/Admin/OverrideAllocationController.php:18
* @route '/admin/orders/{order}/override-allocation'
*/
const overrideAllocationForm = (args: { order: number | { id: number } } | [order: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: overrideAllocation.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\Admin\OverrideAllocationController::__invoke
* @see app/Http/Controllers/Admin/OverrideAllocationController.php:18
* @route '/admin/orders/{order}/override-allocation'
*/
overrideAllocationForm.post = (args: { order: number | { id: number } } | [order: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: overrideAllocation.url(args, options),
    method: 'post',
})

overrideAllocation.form = overrideAllocationForm

/**
* @see \App\Http\Controllers\Admin\PrintShipmentLabelController::__invoke
* @see app/Http/Controllers/Admin/PrintShipmentLabelController.php:23
* @route '/admin/orders/{order}/label'
*/
export const printLabel = (args: { order: number | { id: number } } | [order: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: printLabel.url(args, options),
    method: 'get',
})

printLabel.definition = {
    methods: ["get","head"],
    url: '/admin/orders/{order}/label',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\Admin\PrintShipmentLabelController::__invoke
* @see app/Http/Controllers/Admin/PrintShipmentLabelController.php:23
* @route '/admin/orders/{order}/label'
*/
printLabel.url = (args: { order: number | { id: number } } | [order: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { order: args }
    }

    if (typeof args === 'object' && !Array.isArray(args) && 'id' in args) {
        args = { order: args.id }
    }

    if (Array.isArray(args)) {
        args = {
            order: args[0],
        }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
        order: typeof args.order === 'object'
        ? args.order.id
        : args.order,
    }

    return printLabel.definition.url
            .replace('{order}', parsedArgs.order.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\Admin\PrintShipmentLabelController::__invoke
* @see app/Http/Controllers/Admin/PrintShipmentLabelController.php:23
* @route '/admin/orders/{order}/label'
*/
printLabel.get = (args: { order: number | { id: number } } | [order: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: printLabel.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Admin\PrintShipmentLabelController::__invoke
* @see app/Http/Controllers/Admin/PrintShipmentLabelController.php:23
* @route '/admin/orders/{order}/label'
*/
printLabel.head = (args: { order: number | { id: number } } | [order: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: printLabel.url(args, options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\Admin\PrintShipmentLabelController::__invoke
* @see app/Http/Controllers/Admin/PrintShipmentLabelController.php:23
* @route '/admin/orders/{order}/label'
*/
const printLabelForm = (args: { order: number | { id: number } } | [order: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: printLabel.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Admin\PrintShipmentLabelController::__invoke
* @see app/Http/Controllers/Admin/PrintShipmentLabelController.php:23
* @route '/admin/orders/{order}/label'
*/
printLabelForm.get = (args: { order: number | { id: number } } | [order: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: printLabel.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Admin\PrintShipmentLabelController::__invoke
* @see app/Http/Controllers/Admin/PrintShipmentLabelController.php:23
* @route '/admin/orders/{order}/label'
*/
printLabelForm.head = (args: { order: number | { id: number } } | [order: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: printLabel.url(args, {
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

printLabel.form = printLabelForm

const orders = {
    show: Object.assign(show, show),
    overrideAllocation: Object.assign(overrideAllocation, overrideAllocation),
    printLabel: Object.assign(printLabel, printLabel),
}

export default orders