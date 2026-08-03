import Auth from './Auth'
import Initialization from './Initialization'
import Forbidden from './Forbidden'
import Dashboard from './Dashboard'
import Account from './Account'
import Settings from './Settings'
import Customers from './Customers'
import Order from './Order'
import Product from './Product'
import Attribute from './Attribute'
import Tag from './Tag'
import Brand from './Brand'
import Category from './Category'
import Collection from './Collection'
import Discount from './Discount'
import Reviews from './Reviews'

const Pages = {
    Auth: Object.assign(Auth, Auth),
    Initialization: Object.assign(Initialization, Initialization),
    Forbidden: Object.assign(Forbidden, Forbidden),
    Dashboard: Object.assign(Dashboard, Dashboard),
    Account: Object.assign(Account, Account),
    Settings: Object.assign(Settings, Settings),
    Customers: Object.assign(Customers, Customers),
    Order: Object.assign(Order, Order),
    Product: Object.assign(Product, Product),
    Attribute: Object.assign(Attribute, Attribute),
    Tag: Object.assign(Tag, Tag),
    Brand: Object.assign(Brand, Brand),
    Category: Object.assign(Category, Category),
    Collection: Object.assign(Collection, Collection),
    Discount: Object.assign(Discount, Discount),
    Reviews: Object.assign(Reviews, Reviews),
}

export default Pages