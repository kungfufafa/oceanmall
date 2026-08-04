import PrintShipmentLabelController from './PrintShipmentLabelController'
import OverrideAllocationController from './OverrideAllocationController'

const Cpanel = {
    PrintShipmentLabelController: Object.assign(PrintShipmentLabelController, PrintShipmentLabelController),
    OverrideAllocationController: Object.assign(OverrideAllocationController, OverrideAllocationController),
}

export default Cpanel