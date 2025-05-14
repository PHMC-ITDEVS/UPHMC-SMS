/* Custom Components */
import Breadcrumbs from '@/components/breadcrumbs';
import ApplicationLogo from '@/components/application-logo';
import LoaderModal from '@/components/loader-modal';
import Loader from '@/components/loader';
import FormWizard from '@/components/wizard-form';

/* Custom Layouts */
import GuestLayout from '@/layouts/guest-layout';
import AdminLayout from '@/layouts/admin-layout';

/*other packages*/
import { CountTo }  from 'vue3-count-to';
// import Datepicker from 'vue3-datepicker'

import $ from "jquery";


window.$ = $;

const item = {
    components: [
        { name: "app-logo", val: ApplicationLogo },
        { name: "app-breadcrumb", val: Breadcrumbs },
        { name: "app-loader-modal", val: LoaderModal },
        { name: "app-loader", val: Loader },
        { name: "app-wizard", val: FormWizard },

        { name: "app-guest", val: GuestLayout },
        { name: "app-layout", val: AdminLayout },

        { name: "count-to", val: CountTo },
        // { name: "p-datepicker", val: Datepicker },
    ]
};

export default item;


