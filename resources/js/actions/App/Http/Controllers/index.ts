import Shop from './Shop'
import StripeWebhookController from './StripeWebhookController'
import Webhooks from './Webhooks'
import Account from './Account'
import Cpanel from './Cpanel'
import DashboardController from './DashboardController'
import Settings from './Settings'

const Controllers = {
    Shop: Object.assign(Shop, Shop),
    StripeWebhookController: Object.assign(StripeWebhookController, StripeWebhookController),
    Webhooks: Object.assign(Webhooks, Webhooks),
    Account: Object.assign(Account, Account),
    Cpanel: Object.assign(Cpanel, Cpanel),
    DashboardController: Object.assign(DashboardController, DashboardController),
    Settings: Object.assign(Settings, Settings),
}

export default Controllers