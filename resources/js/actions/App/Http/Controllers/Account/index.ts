import OrderController from './OrderController'
import RetryKomercePaymentController from './RetryKomercePaymentController'
import SyncKomercePaymentStatusController from './SyncKomercePaymentStatusController'
import TrackShipmentController from './TrackShipmentController'
import ConfirmOrderReceivedController from './ConfirmOrderReceivedController'
import CancelOrderController from './CancelOrderController'
import NotificationController from './NotificationController'
import AddressController from './AddressController'

const Account = {
    OrderController: Object.assign(OrderController, OrderController),
    RetryKomercePaymentController: Object.assign(RetryKomercePaymentController, RetryKomercePaymentController),
    SyncKomercePaymentStatusController: Object.assign(SyncKomercePaymentStatusController, SyncKomercePaymentStatusController),
    TrackShipmentController: Object.assign(TrackShipmentController, TrackShipmentController),
    ConfirmOrderReceivedController: Object.assign(ConfirmOrderReceivedController, ConfirmOrderReceivedController),
    CancelOrderController: Object.assign(CancelOrderController, CancelOrderController),
    NotificationController: Object.assign(NotificationController, NotificationController),
    AddressController: Object.assign(AddressController, AddressController),
}

export default Account