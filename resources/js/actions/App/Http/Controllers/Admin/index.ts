import PrintShipmentLabelController from './PrintShipmentLabelController'
import OverrideAllocationController from './OverrideAllocationController'

const Admin = {
    PrintShipmentLabelController: Object.assign(PrintShipmentLabelController, PrintShipmentLabelController),
    OverrideAllocationController: Object.assign(OverrideAllocationController, OverrideAllocationController),
}

export default Admin