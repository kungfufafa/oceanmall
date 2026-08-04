import Shop from './Shop'
import StripeWebhookController from './StripeWebhookController'
import Webhooks from './Webhooks'
import Account from './Account'
import Admin from './Admin'
import Settings from './Settings'

const Controllers = {
    Shop: Object.assign(Shop, Shop),
    StripeWebhookController: Object.assign(StripeWebhookController, StripeWebhookController),
    Webhooks: Object.assign(Webhooks, Webhooks),
    Account: Object.assign(Account, Account),
    Admin: Object.assign(Admin, Admin),
    Settings: Object.assign(Settings, Settings),
}

export default Controllers