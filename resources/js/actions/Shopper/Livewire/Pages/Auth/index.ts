import Login from './Login'
import ForgotPassword from './ForgotPassword'
import ResetPassword from './ResetPassword'

const Auth = {
    Login: Object.assign(Login, Login),
    ForgotPassword: Object.assign(ForgotPassword, ForgotPassword),
    ResetPassword: Object.assign(ResetPassword, ResetPassword),
}

export default Auth