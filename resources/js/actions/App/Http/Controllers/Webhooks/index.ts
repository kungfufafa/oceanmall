import KomercePaymentWebhookController from './KomercePaymentWebhookController'
import KomerceDeliveryWebhookController from './KomerceDeliveryWebhookController'
import KomerceQrislyWebhookController from './KomerceQrislyWebhookController'

const Webhooks = {
    KomercePaymentWebhookController: Object.assign(KomercePaymentWebhookController, KomercePaymentWebhookController),
    KomerceDeliveryWebhookController: Object.assign(KomerceDeliveryWebhookController, KomerceDeliveryWebhookController),
    KomerceQrislyWebhookController: Object.assign(KomerceQrislyWebhookController, KomerceQrislyWebhookController),
}

export default Webhooks