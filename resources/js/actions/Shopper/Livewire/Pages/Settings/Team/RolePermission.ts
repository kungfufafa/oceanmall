import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition, applyUrlDefaults } from './../../../../../../wayfinder'
/**
* @see \Shopper\Livewire\Pages\Settings\Team\RolePermission::__invoke
* @see vendor/shopper/framework/src/Livewire/Pages/Settings/Team/RolePermission.php:7
* @route '/cpanel/setting/team/roles/{role}'
*/
const RolePermission = (args: { role: string | number } | [role: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: RolePermission.url(args, options),
    method: 'get',
})

RolePermission.definition = {
    methods: ["get","head"],
    url: '/cpanel/setting/team/roles/{role}',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \Shopper\Livewire\Pages\Settings\Team\RolePermission::__invoke
* @see vendor/shopper/framework/src/Livewire/Pages/Settings/Team/RolePermission.php:7
* @route '/cpanel/setting/team/roles/{role}'
*/
RolePermission.url = (args: { role: string | number } | [role: string | number ] | string | number, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { role: args }
    }

    if (Array.isArray(args)) {
        args = {
            role: args[0],
        }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
        role: args.role,
    }

    return RolePermission.definition.url
            .replace('{role}', parsedArgs.role.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \Shopper\Livewire\Pages\Settings\Team\RolePermission::__invoke
* @see vendor/shopper/framework/src/Livewire/Pages/Settings/Team/RolePermission.php:7
* @route '/cpanel/setting/team/roles/{role}'
*/
RolePermission.get = (args: { role: string | number } | [role: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: RolePermission.url(args, options),
    method: 'get',
})

/**
* @see \Shopper\Livewire\Pages\Settings\Team\RolePermission::__invoke
* @see vendor/shopper/framework/src/Livewire/Pages/Settings/Team/RolePermission.php:7
* @route '/cpanel/setting/team/roles/{role}'
*/
RolePermission.head = (args: { role: string | number } | [role: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: RolePermission.url(args, options),
    method: 'head',
})

/**
* @see \Shopper\Livewire\Pages\Settings\Team\RolePermission::__invoke
* @see vendor/shopper/framework/src/Livewire/Pages/Settings/Team/RolePermission.php:7
* @route '/cpanel/setting/team/roles/{role}'
*/
const RolePermissionForm = (args: { role: string | number } | [role: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: RolePermission.url(args, options),
    method: 'get',
})

/**
* @see \Shopper\Livewire\Pages\Settings\Team\RolePermission::__invoke
* @see vendor/shopper/framework/src/Livewire/Pages/Settings/Team/RolePermission.php:7
* @route '/cpanel/setting/team/roles/{role}'
*/
RolePermissionForm.get = (args: { role: string | number } | [role: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: RolePermission.url(args, options),
    method: 'get',
})

/**
* @see \Shopper\Livewire\Pages\Settings\Team\RolePermission::__invoke
* @see vendor/shopper/framework/src/Livewire/Pages/Settings/Team/RolePermission.php:7
* @route '/cpanel/setting/team/roles/{role}'
*/
RolePermissionForm.head = (args: { role: string | number } | [role: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: RolePermission.url(args, {
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

RolePermission.form = RolePermissionForm

export default RolePermission