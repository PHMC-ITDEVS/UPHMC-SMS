import moment from 'moment';

export default {
    filters: {
    },

    computed: {
        _cookie_username() {
            return this.$page.props.username;
        },
        
        _cookie_password() {
            return this.$page.props.password;
        },

        _app_name() {
            return this.$page.props.appName;
        },

        _user() {
            return this.$page.props.auth.user;
        },

        _account() {
            return this.$page.props.auth.account;
        },

        _role(){
            return this.$page.props.auth.user.role_name;
        },

        _is_admin(){
            return this.$page.props.auth.user.role_name == 'admin';
        },

        _current_date(){
            return moment().format('YYYY-MM-DD');
        },

        _max_date(){
            return this.parseDate(new Date());
        },

        _hostname()
        {
            return location.protocol + '//' + location.host;
        }
    },

    mounted() {
        
        let id = Math.random().toString(36).slice(2);
        let obj_name = "aid";
        
        if (!this.getCookie(obj_name))
        {
            var expiration_date = new Date();
            expiration_date.setFullYear(expiration_date.getFullYear() + 1);
            // Build the set-cookie string:
            document.cookie = `${obj_name}=${id}; path=/; expires=${ expiration_date.toUTCString()}`;
        } 
    },
    methods: {
        getCookie(name) {
            const value = `; ${document.cookie}`;
            const parts = value.split(`; ${name}=`);
            if (parts.length === 2) return parts.pop().split(';').shift();
        },
        randString(length){
            let data = Math.random().toString(36).slice(0,length);
            return data
        },
        parseNumber(number){
            return (number) ? parseFloat(number) : 0;
        },
        parseDate(date, type = 0){ //date object - new Date()
            if (!date) return null;
            const parsedDate = moment(date);
            switch (type) {
                case 0: // Returns the same date with 00:00:00 time
                    return parsedDate.startOf('day').toDate();
                case 1: // Start of month
                    return parsedDate.startOf('month').toDate();
                case 2: // End of month
                    return parsedDate.endOf('month').toDate();
                case 3: //  Same date and time
                    return parsedDate.toDate();
                case 4:
                    return parsedDate.add(1, 'days').toDate();
                case 5:
                    return parsedDate.subtract(1, 'days').toDate();
                default:
                    return null; // or throw an error for an invalid condition
            }
        },
        formatNumber(number,decimal=1,empty_display=0)
        {   
            if (isNaN(number)) return 0;
            if(number<1 & empty_display) return "-"; 
            if(decimal==1)
            {
                return (!["","-"].includes(number)) ? Number(number).toLocaleString() : 0;
            }
            else if(decimal==2)
            {
                return (!["","-",0].includes(number)) ? parseFloat(number).toLocaleString(undefined, {minimumFractionDigits: 2}) : 0;
            }
            else
            {
                return (!["","-"].includes(number)) ? Math.round(number).toLocaleString() : 0;
            }
        },

        abbreviateNumber(value) {
            const suffixes = ['', 'k', 'M', 'B', 'T'];
            const suffixNum = Math.floor(('' + value).length / 3);
            let shortValue = parseFloat((suffixNum != 0 ? (value / Math.pow(1000, suffixNum)) : value).toPrecision(2));
            if (shortValue % 1 != 0) {
              shortValue = shortValue.toFixed(1);
            }
            return shortValue + suffixes[suffixNum];
        },

        toast(type,message){
            const Toast = Swal.mixin({
                toast: true,
                position: 'top-end',
                showConfirmButton: false,
                timer: 5000,
                timerProgressBar: true,
                didOpen: (toast) => {
                  toast.addEventListener('mouseenter', Swal.stopTimer)
                  toast.addEventListener('mouseleave', Swal.resumeTimer)
                }
            });

            Toast.fire({
                icon: type,
                title: message
            })
        },

        async swalMessage(icon="",title , confirmBtn , denyBtn , html = '' , showDeny = true , showCancelButton = false , allowClickOutside = true , position = 'center'){
            return Swal.fire({
                icon: icon,
                title: title,
                html: html,
                showDenyButton: showDeny,
                showCancelButton: showCancelButton,
                confirmButtonText: confirmBtn,
                denyButtonText: denyBtn,
                allowOutsideClick: allowClickOutside,
                position: position,
                }).then((result) => {
                /* Read more about isConfirmed, isDenied below */
                if (result.isConfirmed) return true;
                else if(result.dismiss === Swal.DismissReason.cancel) return 'cancel';
                else return false;
            })
        },

        getDateMonth(date,type=0){
            var firstDay = moment(date).startOf('month').format('YYYY-MM-DD');
            var lastDay = moment(date).endOf('month').format('YYYY-MM-DD');
            return (type) ? lastDay : firstDay;
        },

        statusClass(status, type) {
            let txt_class = "",
                color_class = "";

            if(["ACTIVE", "COMPLETED","AVAILABLE"].includes(status)) {
                color_class = "success";
            }
            else if(["DELIVERED","PROCESSING"].includes(status)) {
                color_class = "info";
            }
            else if(["INACTIVE", "REFUNDED", "DEACTIVATED"].includes(status)) {
                color_class = "secondary";
            }
            else if(["PENDING"].includes(status)) {
                color_class = "warning";
            }
            else if(["ONGOING"].includes(status)) {
                color_class = "primary";
            }
            else if(["CANCELLED","USED"].includes(status)) {
                color_class = "danger";
            }
            
            // For type
            if(typeof type == "undefined") {
                type = "bg";
            }

            txt_class = `${type}-${color_class}`;
            
            return txt_class;
        },

        statusColor(status)
        {
            let cs = "";
            if (["ACTIVE", "COMPLETED"].includes(status))
            {
                cs="success";
            }
            if (["DELIVERED"].includes(status))
            {
                cs="info";
            }
            else if (["INACTIVE","REFUNDED","DEACTIVATED"].includes(status))
            {
                cs="secondary";
            }
            else if (["PENDING"].includes(status))
            {
                cs="warning";
            }
            else if (["ONGOING"].includes(status))
            {
                cs="primary";
            }
            else if (["CANCELLED"].includes(status))
            {
                cs="danger";
            }
            return cs;
        },

        auth_role()
        {
            return this._user.role_name;
        },
        
        auth_role_admin()
        {
            return (this._user.role_name=="ADMIN") ? true : false;
        },
       
        auth_fullname()
        {
            return [
                this.uppercase(this._account.first_name),
                this.uppercase(this._account.middle_name),
                this.uppercase(this._account.last_name)
            ]
            .filter(Boolean)
            .join(' ');
        },

        auth_position()
        {
            let pos = this._user.position_name;

            if(!pos) pos = this._user.role_name;
            return pos;
        },

        uppercase(str) {
            if (!str) return "";
            var splitStr = str.toLowerCase().split(' ');
            for (var i = 0; i < splitStr.length; i++) {
                // You do not need to check if i is larger than splitStr length, as your for does that for you
                // Assign it back to the array
                splitStr[i] = splitStr[i].charAt(0).toUpperCase() + splitStr[i].substring(1);     
            }
            // Directly return the joined string
            return splitStr.join(' '); 
        },

        addDay(date,days)
        {
            return moment(date, "YYYY-MM-DD").add(days, 'days');
        },
        dateDiff(date1,date2)
        {
            let a = moment(date1);
            let b = moment(date2);
            return a.diff(b, 'days');
        },
        formatDate(date, type=0) {
            if (type==1) return (date) ? moment(date).format('MMMM DD') : "";
            if (type==2) return (date) ? moment(date).format('MMMM DD, YYYY') : "";
            if (type==3) return (date) ? moment(date).format('YYYY-MM-DD') : "";
            else return (date) ? moment(new Date(date)).format('MM/DD/YYYY') : "";
        },

        formatDateTime(datetime, type=0) {
            if (type==1) return (datetime) ? moment(datetime).startOf('day').format('YYYY-MM-DD HH:mm:ss') : "";
            if (type==2) return (datetime) ? moment(datetime).endOf('day').format('YYYY-MM-DD HH:mm:ss') : "";
            else return (datetime) ? moment(new Date(datetime)).format('MM/DD/YYYY h:mm A') : "";
        },
        padWithZeros(str, length) {
            return str.toString().padStart(length, "0");
        },
        sortObjectDesc(obj) {
            return Object.entries(obj)
                .sort(([, a], [, b]) => b - a)
                .reduce(
                (r, [k, v]) => ({
                    ...r,
                    [k]: v
                }),
                {}
            )
        },

        getError(error, render_bad_request=false)  {
            const error_message = import.meta.env.VITE_DEFAULT_ERROR_MESSAGE;

            // if(import.meta.env.VITE_APP_DEBUG) {
            //     console.log(error.response.data);
            //     console.log(error.response.status);
            //     console.log(error.response.headers);
            // }
        
            if(error.response.data && error.response.data.message && typeof error.response.data.errors == "undefined") {
                // this.$toast.add({
                //     severity:'error', 
                //     summary: 'Error Message', 
                //     detail:error.response.data.message, 
                //     life: 3000
                // });
                this.swal("error", "Error!", error.response.data.message, "Confirm");
                return;
            }
        
            if(error.response.status == 400) {
                if(render_bad_request) {
                    this.prototype.$bus.$emit('render_bad_request', error.response.data.errors);
                    return error.response.data.errors;
                }
                else {
                    if (error.response.data.show_error) {
                        this.prototype.$bus.$emit('render_bad_request', error.response.data.errors);
                        return error.response.data.errors;
                    }
                }
            }
        
            if(error.response.data && error.response.data.errors) {        
                // this.$toast.error('A problem has occured please contact customer support.');
                return error.response.data.errors;
            }
            
            if(error.response.status == 500 || error.response.data.message == 'The payload is invalid.') {
                // this.$toast.add({
                //     severity:'error', 
                //     summary: 'Error Message', 
                //     detail:error.response.data.message, 
                //     life: 3000
                // });
                this.swal("error", "Error!", error.response.data.message, "Confirm");
            }    
        
            if(error.response.status == 401 || error.response.status == 419) {
                var message = '';
        
                if (error.response.status == 401) message = 'Access Denied.';
                if (error.response.status == 419) message = 'Unauthenticated.';
        
                this.swal("error", "Error!", message, "Confirm");
                window.Permissions = {};
                window.location.reload();
            }
            
            return error_message;
        },

        async swal(icon, title, text, confirm_button_text) {
            return Swal.fire({
                title: title,
                text: text,
                icon: icon,
                confirmButtonText: confirm_button_text
            })
        },

        async swal2(icon, title, text, confirm_button_text, deny_button_text,allow_escape) {
            return Swal.fire({
                showDenyButton: true,
                showCancelButton: false,
                title: title,
                text: text,
                icon: icon,
                confirmButtonText: confirm_button_text,
                denyButtonText: deny_button_text,
                allowEscapeKey:allow_escape,
                allowOutsideClick:allow_escape,
                customClass: {
                    confirmButton: 'order-2',
                    denyButton: 'order-1',
                  },
            })
        },

        removeReplaceUnderscore(text, type = 0) {
            if(!text) return;

            if (type == 1) return text.replace(/_/g, '-');
            else return text.replace(/_/g, ' ');
        },
    },
}