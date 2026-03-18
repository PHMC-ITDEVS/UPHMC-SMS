import PrimeVue from 'primevue/config';

/* Theme */
import Aura from '@primeuix/themes/aura';

/* Icons */
import 'primeicons/primeicons.css';

// PrimeVue Services
import ConfirmationService from 'primevue/confirmationservice';
import ToastService from 'primevue/toastservice';

/* PrimeVue Components */
import Card from 'primevue/card';
import Panel from 'primevue/panel';
import Dialog from 'primevue/dialog';
import ConfirmDialog from 'primevue/confirmdialog';
import Toast from 'primevue/toast';

import Message from 'primevue/message';

import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import ColumnGroup from 'primevue/columngroup';
import Row from 'primevue/row';
import Paginator from 'primevue/paginator';

import ProgressSpinner from 'primevue/progressspinner';
import ProgressBar from 'primevue/progressbar';

import Password from 'primevue/password';
import InputText from 'primevue/inputtext';
import InputNumber from 'primevue/inputnumber';
import Checkbox from 'primevue/checkbox';
import Textarea from 'primevue/textarea';
import Dropdown from 'primevue/dropdown';
import DatePicker from 'primevue/datepicker';
import InputSwitch from 'primevue/inputswitch';
import Button from 'primevue/button';
import Skeleton from 'primevue/skeleton';
import IconField from 'primevue/iconfield';
import InputIcon from 'primevue/inputicon';
import MultiSelect from 'primevue/multiselect';
import AutoComplete from 'primevue/autocomplete';
import Timeline from 'primevue/timeline';
import Popover from 'primevue/popover';
import FloatLabel from 'primevue/floatlabel';
import InputGroup from 'primevue/inputgroup';
import InputGroupAddon from 'primevue/inputgroupaddon';

import Image from 'primevue/image';
import Avatar from 'primevue/avatar';
import AvatarGroup from 'primevue/avatargroup'; 

import Ripple from 'primevue/ripple';
import Tooltip from 'primevue/tooltip';

const primevue = {
    components: [
        { name: "p-card", val: Card },
        { name: "p-panel", val: Panel },
        { name: "p-dialog", val: Dialog },
        { name: "p-confirm", val: ConfirmDialog },
        { name: "p-toast", val: Toast },
        { name: "p-table", val: DataTable },
        { name: "p-column", val: Column },
        { name: "p-column-group", val: ColumnGroup },
        { name: "p-row", val: Row },
        { name: "p-paginator", val: Paginator },
        { name: "p-input-password", val: Password },
        { name: "p-input-text", val: InputText },
        { name: "p-input-number", val: InputNumber },
        { name: "p-checkbox", val: Checkbox },
        { name: "p-textarea", val: Textarea },
        { name: "p-dropdown", val: Dropdown },
        { name: "p-datepicker", val: DatePicker },
        { name: "p-switch", val: InputSwitch },
        { name: "p-button", val: Button },
        { name: "p-skeleton", val: Skeleton },
        { name: "p-message", val: Message },
        { name: "p-field-icon", val: IconField },
        { name: "p-input-icon", val: InputIcon },
        { name: "p-multiselect", val: MultiSelect },
        { name: "p-progress-bar", val: ProgressBar },
        { name: "p-progress-spinner", val: ProgressSpinner },
        { name: "p-autocomplete", val: AutoComplete },
        { name: "p-timeline", val: Timeline },
        { name: "p-popover", val: Popover },
        { name: "p-float-label", val: FloatLabel },
        { name: "p-input-group", val: InputGroup },
        { name: "p-input-group-addon", val: InputGroupAddon },
        { name: "p-image", val: Image },
        { name: "p-avatar", val: Avatar },
        { name: "p-avatar-group", val: AvatarGroup }

    ],
    directive: [
        { name: "ripple", val: Ripple },
        { name: "tooltip", val: Tooltip }
    ],
    service: [ToastService, ConfirmationService],
    config: PrimeVue
};

// PrimeVue Configuration with Aura Theme
export function setupPrimeVue(app) {
    app.use(PrimeVue, {
        theme: {
            preset: Aura,
            options: {
                prefix: "p",
                darkModeSelector: false,
            }
        }
    });

    // Register services
    app.use(ToastService);
    app.use(ConfirmationService);

    // Register components
    primevue.components.forEach(({ name, val }) => {
        app.component(name, val);
    });

    // Register directives
    primevue.directive.forEach(({ name, val }) => {
        app.directive(name, val);
    });
}

export default primevue;
