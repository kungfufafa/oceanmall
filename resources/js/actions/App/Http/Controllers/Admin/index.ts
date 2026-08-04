import OrderShowController from './OrderShowController'
import OverrideAllocationController from './OverrideAllocationController'
import PrintShipmentLabelController from './PrintShipmentLabelController'

const Admin = {
    OrderShowController: Object.assign(OrderShowController, OrderShowController),
    OverrideAllocationController: Object.assign(OverrideAllocationController, OverrideAllocationController),
    PrintShipmentLabelController: Object.assign(PrintShipmentLabelController, PrintShipmentLabelController),
}

export default Admin