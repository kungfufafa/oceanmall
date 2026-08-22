import Index from './Index'
import Create from './Create'
import Show from './Show'

const Customers = {
    Index: Object.assign(Index, Index),
    Create: Object.assign(Create, Create),
    Show: Object.assign(Show, Show),
}

export default Customers