import Index from './Index'
import Edit from './Edit'
import Variant from './Variant'

const Product = {
    Index: Object.assign(Index, Index),
    Edit: Object.assign(Edit, Edit),
    Variant: Object.assign(Variant, Variant),
}

export default Product