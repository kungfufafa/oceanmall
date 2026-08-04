import OrderController from './OrderController'
import TrackShipmentController from './TrackShipmentController'
import ConfirmOrderReceivedController from './ConfirmOrderReceivedController'
import AddressController from './AddressController'

const Account = {
    OrderController: Object.assign(OrderController, OrderController),
    TrackShipmentController: Object.assign(TrackShipmentController, TrackShipmentController),
    ConfirmOrderReceivedController: Object.assign(ConfirmOrderReceivedController, ConfirmOrderReceivedController),
    AddressController: Object.assign(AddressController, AddressController),
}

export default Account