import Index from './Index'
import General from './General'
import Locations from './Locations'
import LegalPage from './LegalPage'
import PaymentMethods from './PaymentMethods'
import Carriers from './Carriers'
import Zones from './Zones'
import Taxes from './Taxes'
import Currencies from './Currencies'
import Team from './Team'

const Settings = {
    Index: Object.assign(Index, Index),
    General: Object.assign(General, General),
    Locations: Object.assign(Locations, Locations),
    LegalPage: Object.assign(LegalPage, LegalPage),
    PaymentMethods: Object.assign(PaymentMethods, PaymentMethods),
    Carriers: Object.assign(Carriers, Carriers),
    Zones: Object.assign(Zones, Zones),
    Taxes: Object.assign(Taxes, Taxes),
    Currencies: Object.assign(Currencies, Currencies),
    Team: Object.assign(Team, Team),
}

export default Settings