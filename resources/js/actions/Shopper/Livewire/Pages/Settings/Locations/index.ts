import Index from './Index'
import Create from './Create'
import Edit from './Edit'

const Locations = {
    Index: Object.assign(Index, Index),
    Create: Object.assign(Create, Create),
    Edit: Object.assign(Edit, Edit),
}

export default Locations