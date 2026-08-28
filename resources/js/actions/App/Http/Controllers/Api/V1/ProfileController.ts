import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition } from './../../../../../../wayfinder'
/**
* @see \App\Http\Controllers\Api\V1\ProfileController::forgotPassword
* @see app/Http/Controllers/Api/V1/ProfileController.php:44
* @route '/api/v1/auth/forgot-password'
*/
export const forgotPassword = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: forgotPassword.url(options),
    method: 'post',
})

forgotPassword.definition = {
    methods: ["post"],
    url: '/api/v1/auth/forgot-password',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\Api\V1\ProfileController::forgotPassword
* @see app/Http/Controllers/Api/V1/ProfileController.php:44
* @route '/api/v1/auth/forgot-password'
*/
forgotPassword.url = (options?: RouteQueryOptions) => {
    return forgotPassword.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\Api\V1\ProfileController::forgotPassword
* @see app/Http/Controllers/Api/V1/ProfileController.php:44
* @route '/api/v1/auth/forgot-password'
*/
forgotPassword.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: forgotPassword.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\Api\V1\ProfileController::forgotPassword
* @see app/Http/Controllers/Api/V1/ProfileController.php:44
* @route '/api/v1/auth/forgot-password'
*/
const forgotPasswordForm = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: forgotPassword.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\Api\V1\ProfileController::forgotPassword
* @see app/Http/Controllers/Api/V1/ProfileController.php:44
* @route '/api/v1/auth/forgot-password'
*/
forgotPasswordForm.post = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: forgotPassword.url(options),
    method: 'post',
})

forgotPassword.form = forgotPasswordForm

/**
* @see \App\Http\Controllers\Api\V1\ProfileController::resetPassword
* @see app/Http/Controllers/Api/V1/ProfileController.php:57
* @route '/api/v1/auth/reset-password'
*/
export const resetPassword = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: resetPassword.url(options),
    method: 'post',
})

resetPassword.definition = {
    methods: ["post"],
    url: '/api/v1/auth/reset-password',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\Api\V1\ProfileController::resetPassword
* @see app/Http/Controllers/Api/V1/ProfileController.php:57
* @route '/api/v1/auth/reset-password'
*/
resetPassword.url = (options?: RouteQueryOptions) => {
    return resetPassword.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\Api\V1\ProfileController::resetPassword
* @see app/Http/Controllers/Api/V1/ProfileController.php:57
* @route '/api/v1/auth/reset-password'
*/
resetPassword.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: resetPassword.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\Api\V1\ProfileController::resetPassword
* @see app/Http/Controllers/Api/V1/ProfileController.php:57
* @route '/api/v1/auth/reset-password'
*/
const resetPasswordForm = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: resetPassword.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\Api\V1\ProfileController::resetPassword
* @see app/Http/Controllers/Api/V1/ProfileController.php:57
* @route '/api/v1/auth/reset-password'
*/
resetPasswordForm.post = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: resetPassword.url(options),
    method: 'post',
})

resetPassword.form = resetPasswordForm

/**
* @see \App\Http\Controllers\Api\V1\ProfileController::update
* @see app/Http/Controllers/Api/V1/ProfileController.php:18
* @route '/api/v1/auth/profile'
*/
export const update = (options?: RouteQueryOptions): RouteDefinition<'patch'> => ({
    url: update.url(options),
    method: 'patch',
})

update.definition = {
    methods: ["patch"],
    url: '/api/v1/auth/profile',
} satisfies RouteDefinition<["patch"]>

/**
* @see \App\Http\Controllers\Api\V1\ProfileController::update
* @see app/Http/Controllers/Api/V1/ProfileController.php:18
* @route '/api/v1/auth/profile'
*/
update.url = (options?: RouteQueryOptions) => {
    return update.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\Api\V1\ProfileController::update
* @see app/Http/Controllers/Api/V1/ProfileController.php:18
* @route '/api/v1/auth/profile'
*/
update.patch = (options?: RouteQueryOptions): RouteDefinition<'patch'> => ({
    url: update.url(options),
    method: 'patch',
})

/**
* @see \App\Http\Controllers\Api\V1\ProfileController::update
* @see app/Http/Controllers/Api/V1/ProfileController.php:18
* @route '/api/v1/auth/profile'
*/
const updateForm = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: update.url({
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'PATCH',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'post',
})

/**
* @see \App\Http\Controllers\Api\V1\ProfileController::update
* @see app/Http/Controllers/Api/V1/ProfileController.php:18
* @route '/api/v1/auth/profile'
*/
updateForm.patch = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: update.url({
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'PATCH',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'post',
})

update.form = updateForm

/**
* @see \App\Http\Controllers\Api\V1\ProfileController::updatePassword
* @see app/Http/Controllers/Api/V1/ProfileController.php:35
* @route '/api/v1/auth/password'
*/
export const updatePassword = (options?: RouteQueryOptions): RouteDefinition<'put'> => ({
    url: updatePassword.url(options),
    method: 'put',
})

updatePassword.definition = {
    methods: ["put"],
    url: '/api/v1/auth/password',
} satisfies RouteDefinition<["put"]>

/**
* @see \App\Http\Controllers\Api\V1\ProfileController::updatePassword
* @see app/Http/Controllers/Api/V1/ProfileController.php:35
* @route '/api/v1/auth/password'
*/
updatePassword.url = (options?: RouteQueryOptions) => {
    return updatePassword.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\Api\V1\ProfileController::updatePassword
* @see app/Http/Controllers/Api/V1/ProfileController.php:35
* @route '/api/v1/auth/password'
*/
updatePassword.put = (options?: RouteQueryOptions): RouteDefinition<'put'> => ({
    url: updatePassword.url(options),
    method: 'put',
})

/**
* @see \App\Http\Controllers\Api\V1\ProfileController::updatePassword
* @see app/Http/Controllers/Api/V1/ProfileController.php:35
* @route '/api/v1/auth/password'
*/
const updatePasswordForm = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: updatePassword.url({
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'PUT',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'post',
})

/**
* @see \App\Http\Controllers\Api\V1\ProfileController::updatePassword
* @see app/Http/Controllers/Api/V1/ProfileController.php:35
* @route '/api/v1/auth/password'
*/
updatePasswordForm.put = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: updatePassword.url({
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'PUT',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'post',
})

updatePassword.form = updatePasswordForm

const ProfileController = { forgotPassword, resetPassword, update, updatePassword }

export default ProfileController