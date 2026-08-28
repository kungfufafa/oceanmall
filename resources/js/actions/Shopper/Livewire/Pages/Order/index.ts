import Index from './Index'
import AbandonedCarts from './AbandonedCarts'
import Detail from './Detail'

const Order = {
    Index: Object.assign(Index, Index),
    AbandonedCarts: Object.assign(AbandonedCarts, AbandonedCarts),
    Detail: Object.assign(Detail, Detail),
}

export default Order