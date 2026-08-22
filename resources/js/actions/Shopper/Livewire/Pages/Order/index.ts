import Index from './Index'
import Shipments from './Shipments'
import AbandonedCarts from './AbandonedCarts'
import Detail from './Detail'

const Order = {
    Index: Object.assign(Index, Index),
    Shipments: Object.assign(Shipments, Shipments),
    AbandonedCarts: Object.assign(AbandonedCarts, AbandonedCarts),
    Detail: Object.assign(Detail, Detail),
}

export default Order