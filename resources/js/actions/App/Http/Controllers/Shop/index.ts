import HomeController from './HomeController'
import ProductController from './ProductController'
import ProductReviewController from './ProductReviewController'
import CategoryController from './CategoryController'
import CollectionController from './CollectionController'
import SearchController from './SearchController'
import CartController from './CartController'
import CartCouponController from './CartCouponController'
import ZoneController from './ZoneController'
import CheckoutController from './CheckoutController'
import DestinationSearchController from './DestinationSearchController'
import StripePaymentController from './StripePaymentController'
import CheckoutSuccessController from './CheckoutSuccessController'

const Shop = {
    HomeController: Object.assign(HomeController, HomeController),
    ProductController: Object.assign(ProductController, ProductController),
    ProductReviewController: Object.assign(ProductReviewController, ProductReviewController),
    CategoryController: Object.assign(CategoryController, CategoryController),
    CollectionController: Object.assign(CollectionController, CollectionController),
    SearchController: Object.assign(SearchController, SearchController),
    CartController: Object.assign(CartController, CartController),
    CartCouponController: Object.assign(CartCouponController, CartCouponController),
    ZoneController: Object.assign(ZoneController, ZoneController),
    CheckoutController: Object.assign(CheckoutController, CheckoutController),
    DestinationSearchController: Object.assign(DestinationSearchController, DestinationSearchController),
    StripePaymentController: Object.assign(StripePaymentController, StripePaymentController),
    CheckoutSuccessController: Object.assign(CheckoutSuccessController, CheckoutSuccessController),
}

export default Shop