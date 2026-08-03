import OrderController from './OrderController'
import AddressController from './AddressController'

const Account = {
    OrderController: Object.assign(OrderController, OrderController),
    AddressController: Object.assign(AddressController, AddressController),
}

export default Account