import AuthController from './AuthController'
import ProfileController from './ProfileController'
import CatalogController from './CatalogController'
import CartController from './CartController'
import CheckoutController from './CheckoutController'
import DestinationController from './DestinationController'
import OrderController from './OrderController'
import AddressController from './AddressController'
import NotificationController from './NotificationController'

const V1 = {
    AuthController: Object.assign(AuthController, AuthController),
    ProfileController: Object.assign(ProfileController, ProfileController),
    CatalogController: Object.assign(CatalogController, CatalogController),
    CartController: Object.assign(CartController, CartController),
    CheckoutController: Object.assign(CheckoutController, CheckoutController),
    DestinationController: Object.assign(DestinationController, DestinationController),
    OrderController: Object.assign(OrderController, OrderController),
    AddressController: Object.assign(AddressController, AddressController),
    NotificationController: Object.assign(NotificationController, NotificationController),
}

export default V1