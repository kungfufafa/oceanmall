import Shop from './Shop'
import StripeWebhookController from './StripeWebhookController'
import Account from './Account'
import Settings from './Settings'

const Controllers = {
    Shop: Object.assign(Shop, Shop),
    StripeWebhookController: Object.assign(StripeWebhookController, StripeWebhookController),
    Account: Object.assign(Account, Account),
    Settings: Object.assign(Settings, Settings),
}

export default Controllers