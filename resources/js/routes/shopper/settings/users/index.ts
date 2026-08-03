import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition, applyUrlDefaults } from './../../../../wayfinder'
/**
* @see \Shopper\Livewire\Pages\Settings\Team\RolePermission::__invoke
* @see vendor/shopper/framework/src/Livewire/Pages/Settings/Team/RolePermission.php:7
* @route '/cpanel/setting/team/roles/{role}'
*/
export const role = (args: { role: string | number } | [role: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: role.url(args, options),
    method: 'get',
})

role.definition = {
    methods: ["get","head"],
    url: '/cpanel/setting/team/roles/{role}',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \Shopper\Livewire\Pages\Settings\Team\RolePermission::__invoke
* @see vendor/shopper/framework/src/Livewire/Pages/Settings/Team/RolePermission.php:7
* @route '/cpanel/setting/team/roles/{role}'
*/
role.url = (args: { role: string | number } | [role: string | number ] | string | number, options?: RouteQueryOptions) => {
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

    return role.definition.url
            .replace('{role}', parsedArgs.role.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \Shopper\Livewire\Pages\Settings\Team\RolePermission::__invoke
* @see vendor/shopper/framework/src/Livewire/Pages/Settings/Team/RolePermission.php:7
* @route '/cpanel/setting/team/roles/{role}'
*/
role.get = (args: { role: string | number } | [role: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: role.url(args, options),
    method: 'get',
})

/**
* @see \Shopper\Livewire\Pages\Settings\Team\RolePermission::__invoke
* @see vendor/shopper/framework/src/Livewire/Pages/Settings/Team/RolePermission.php:7
* @route '/cpanel/setting/team/roles/{role}'
*/
role.head = (args: { role: string | number } | [role: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: role.url(args, options),
    method: 'head',
})

/**
* @see \Shopper\Livewire\Pages\Settings\Team\RolePermission::__invoke
* @see vendor/shopper/framework/src/Livewire/Pages/Settings/Team/RolePermission.php:7
* @route '/cpanel/setting/team/roles/{role}'
*/
const roleForm = (args: { role: string | number } | [role: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: role.url(args, options),
    method: 'get',
})

/**
* @see \Shopper\Livewire\Pages\Settings\Team\RolePermission::__invoke
* @see vendor/shopper/framework/src/Livewire/Pages/Settings/Team/RolePermission.php:7
* @route '/cpanel/setting/team/roles/{role}'
*/
roleForm.get = (args: { role: string | number } | [role: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: role.url(args, options),
    method: 'get',
})

/**
* @see \Shopper\Livewire\Pages\Settings\Team\RolePermission::__invoke
* @see vendor/shopper/framework/src/Livewire/Pages/Settings/Team/RolePermission.php:7
* @route '/cpanel/setting/team/roles/{role}'
*/
roleForm.head = (args: { role: string | number } | [role: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: role.url(args, {
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

role.form = roleForm

const users = {
    role: Object.assign(role, role),
}

export default users