<template>
   <div class="page">
        <app-broadcast />
        <div class="page-main is-expanded">
            <div class="app-sidebar__overlay active"></div>
            <aside class="app-sidebar ps ps--active-y" @mouseenter="toggleSideNav('over')" @mouseleave="toggleSideNav('over')">
                <div class="app-sidebar__logo"> 
                    <a class="header-brand" href="/"> 
                        <img src="images/phmc-header.png" class="header-brand-img desktop-lgo" alt="App logo"> 
                        <img src="images/phmc-header.png" class="header-brand-img dark-logo" alt="App logo"> 
                        <img src="images/phmc-logo.png" class="header-brand-img mobile-logo" alt="App logo"> 
                        <img src="images/phmc-logo.png" class="header-brand-img darkmobile-logo" alt="App logo"> 
                    </a> 
                </div>
                <div class="app-sidebar3">
                    <ul class="side-menu">
                        <li v-for="(menu, index) in visibleMenus" :key="index">
                            <h3>{{ menu?.group }}</h3>
                            <div class="slide" v-for="(item, sub_index) in menu.group_items" :key="sub_index">
                                <a class="side-menu__item" :class="{'active' : isActive(item.url)}" href="javascript:void(0)" @click="goToPage(item.url)">
                                    <span class="shape1"></span> 
                                    <span class="shape2"></span> 
                                    <span class="side-menu__icon" :class="`mdi mdi-${item.icon}`"></span>
                                    <span class="side-menu__label">{{ item.title }}</span> 
                                </a>
                            </div>
                        </li>
                    </ul>
                </div>
            </aside>

            <div class="app-content">
                <div class="side-app">
                    <div class="app-header header">
                        <div class="container-fluid">
                            <div class="d-flex">
                                <a class="header-brand" href="index.html"> 
                                   <img src="images/phmc-header.png" class="header-brand-img desktop-lgo" alt="App logo"> 
                                    <img src="images/phmc-header.png" class="header-brand-img dark-logo" alt="App logo"> 
                                    <img src="images/phmc-banner.png" class="header-brand-img mobile-logo" alt="App logo"> 
                                    <img src="images/phmc-banner.png" class="header-brand-img darkmobile-logo" alt="App logo">  
                                </a> 
                                <div class="app-sidebar__toggle" @click.prevent="toggleSideNav('click')">
                                <a class="open-toggle" href="#">
                                    <svg class="header-icon mt-1" xmlns="http://www.w3.org/2000/svg" height="24" viewBox="0 0 24 24" width="24">
                                        <path d="M0 0h24v24H0V0z" fill="none"></path>
                                        <path d="M21 11.01L3 11v2h18zM3 16h12v2H3zM21 6H3v2.01L21 8z"></path>
                                    </svg>
                                </a>
                                </div>
                                <div class="mt-1">
                                    <div class="search-element">
                                        <global-search />
                                    </div>
                                </div>
                                <!-- SEARCH --> 
                                <div class="d-flex order-lg-2 ml-auto">

                                    <!-- Search -->
                                    <a href="#" data-toggle="search" class="nav-link nav-link-lg d-md-none navsearch">
                                        <svg class="header-icon search-icon" x="1008" y="1248" viewBox="0 0 24 24" height="100%" width="100%" preserveAspectRatio="xMidYMid meet" focusable="false">
                                            <path d="M0 0h24v24H0V0z" fill="none"></path>
                                            <path d="M15.5 14h-.79l-.28-.27C15.41 12.59 16 11.11 16 9.5 16 5.91 13.09 3 9.5 3S3 5.91 3 9.5 5.91 16 9.5 16c1.61 0 3.09-.59 4.23-1.57l.27.28v.79l5 4.99L20.49 19l-4.99-5zm-6 0C7.01 14 5 11.99 5 9.5S7.01 5 9.5 5 14 7.01 14 9.5 11.99 14 9.5 14z"></path>
                                        </svg>
                                    </a>

                                    <!-- Message -->
                                    <!-- <div class="dropdown header-message overlay-custom-override_message">
                                        <a class="nav-link icon p-0" data-toggle="dropdown" @click="toggleOverlayPanel($event, 'message')">
                                            <svg class="header-icon" xmlns="http://www.w3.org/2000/svg" height="24" viewBox="0 0 24 24" width="24">
                                                <path d="M0 0h24v24H0V0z" fill="none"></path>
                                                <path d="M20 8l-8 5-8-5v10h16zm0-2H4l8 4.99z" opacity=".3"></path>
                                                <path d="M4 20h16c1.1 0 2-.9 2-2V6c0-1.1-.9-2-2-2H4c-1.1 0-2 .9-2 2v12c0 1.1.9 2 2 2zM20 6l-8 4.99L4 6h16zM4 8l8 5 8-5v10H4V8z"></path>
                                            </svg>
                                            <span class="badge badge-success">8</span> 
                                        </a>
                                        
                                        <p-popover ref="message" class="overlay-message" appendTo="body">
                                            <div class="message-menu">
                                                <a class="dropdown-item d-flex pb-3 border-bottom" href="#">
                                                <span class="avatar avatar-md brround mr-3 align-self-center cover-image" data-image-src="images/default.png" style="background: url(&quot;images/default.png&quot;) center center;"></span> 
                                                <div>
                                                    <strong>Madeleine</strong> Hey! there I' am available.... 
                                                    <div class="small text-muted"> 3 hours ago </div>
                                                </div>
                                                </a>
                                                <a class="dropdown-item d-flex pb-3 border-bottom" href="#">
                                                <span class="avatar avatar-md brround mr-3 align-self-center cover-image" data-image-src="images/default.png" style="background: url(&quot;images/default.png&quot;) center center;"></span> 
                                                <div>
                                                    <strong>Anthony</strong> New product Launching... 
                                                    <div class="small text-muted"> 5 hour ago </div>
                                                </div>
                                                </a>
                                                <a class="dropdown-item d-flex pb-3 border-bottom" href="#">
                                                <span class="avatar avatar-md brround mr-3 align-self-center cover-image" data-image-src="images/default.png" style="background: url(&quot;images/default.png&quot;) center center;"></span> 
                                                <div>
                                                    <strong>Olivia</strong> New Schedule Realease...... 
                                                    <div class="small text-muted"> 45 mintues ago </div>
                                                </div>
                                                </a>
                                                <a class="dropdown-item d-flex pb-3 border-bottom" href="#">
                                                <span class="avatar avatar-md brround mr-3 align-self-center cover-image" data-image-src="images/default.png" style="background: url(&quot;images/default.png&quot;) center center;"></span> 
                                                <div>
                                                    <strong>Sanderson</strong> New Schedule Realease...... 
                                                    <div class="small text-muted"> 2 days ago </div>
                                                </div>
                                                </a>
                                            </div>
                                            <a href="#" class="dropdown-item text-center">See all Messages</a> 
                                        </p-popover>
                                    </div> -->

                                    <!-- Notificaiton -->
                                    <!-- <div class="dropdown header-notify overlay-custom-override_notif">
                                        <a class="nav-link icon p-0" data-toggle="dropdown" @click="toggleOverlayPanel($event, 'notif')">
                                            <svg class="header-icon" x="1008" y="1248" viewBox="0 0 24 24" height="100%" width="100%" preserveAspectRatio="xMidYMid meet" focusable="false">
                                                <path opacity=".3" d="M12 6.5c-2.49 0-4 2.02-4 4.5v6h8v-6c0-2.48-1.51-4.5-4-4.5z"></path>
                                                <path d="M12 22c1.1 0 2-.9 2-2h-4c0 1.1.9 2 2 2zm6-11c0-3.07-1.63-5.64-4.5-6.32V4c0-.83-.67-1.5-1.5-1.5s-1.5.67-1.5 1.5v.68C7.64 5.36 6 7.92 6 11v5l-2 2v1h16v-1l-2-2v-5zm-2 6H8v-6c0-2.48 1.51-4.5 4-4.5s4 2.02 4 4.5v6zM7.58 4.08L6.15 2.65C3.75 4.48 2.17 7.3 2.03 10.5h2a8.445 8.445 0 013.55-6.42zm12.39 6.42h2c-.15-3.2-1.73-6.02-4.12-7.85l-1.42 1.43a8.495 8.495 0 013.54 6.42z"></path>
                                            </svg>
                                            <span class="pulse "></span> 
                                        </a>

                                        <p-popover ref="notif" class="overlay-notif" appendTo="body">
                                            <div class="notifications-menu">
                                                <a class="dropdown-item d-flex pb-4 border-bottom" href="#">
                                                <span class="avatar avatar-md mr-3 align-self-center cover-image bg-gradient-danger brround"> <i class="mdi mdi-download"></i> </span> 
                                                <div>
                                                    <span class="font-weight-bold"> New file has been Uploaded </span> 
                                                    <div class="small text-muted d-flex"> 5 hour ago </div>
                                                </div>
                                                </a>
                                                <a class="dropdown-item d-flex pb-4 border-bottom" href="#">
                                                <span class="avatar avatar-md  mr-3 align-self-center cover-image bg-gradient-teal brround"> <i class="mdi mdi-account-outline"></i> </span> 
                                                <div>
                                                    <span class="font-weight-bold"> User account has Updated</span> 
                                                    <div class="small text-muted d-flex"> 20 mins ago </div>
                                                </div>
                                                </a>
                                                <a class="dropdown-item d-flex pb-4 border-bottom" href="#">
                                                <span class="avatar avatar-md  mr-3 align-self-center cover-image bg-gradient-info brround"> <i class="mdi mdi-cart-outline"></i> </span> 
                                                <div>
                                                    <span class="font-weight-bold"> New Order Recevied</span> 
                                                    <div class="small text-muted d-flex"> 1 hour ago </div>
                                                </div>
                                                </a>
                                                <a class="dropdown-item d-flex pb-4 border-bottom" href="#">
                                                <span class="avatar avatar-md mr-3 align-self-center cover-image bg-gradient-pink brround"> <i class="mdi mdi-server"></i> </span> 
                                                <div>
                                                    <span class="font-weight-bold">Server Rebooted</span> 
                                                    <div class="small text-muted d-flex"> 2 hour ago </div>
                                                </div>
                                                </a>
                                            </div>
                                            <a href="#" class="dropdown-item text-center">View all Notification</a> 
                                        </p-popover>
                                    </div> -->

                                    <!-- Fullsceen -->
                                    <div class="dropdown header-fullscreen">
                                        <p-button class="nav-link icon full-screen-link p-0 btn btn-transparent" @click.prevent="toggleFullScreen()">
                                            <svg class="header-icon" x="1008" y="1248" viewBox="0 0 24 24" height="100%" width="100%" preserveAspectRatio="xMidYMid meet" focusable="false">
                                                <path d="M7,14 L5,14 L5,19 L10,19 L10,17 L7,17 L7,14 Z M5,10 L7,10 L7,7 L10,7 L10,5 L5,5 L5,10 Z M17,17 L14,17 L14,19 L19,19 L19,14 L17,14 L17,17 Z M14,5 L14,7 L17,7 L17,10 L19,10 L19,5 L14,5 Z"></path>
                                            </svg>
                                        </p-button>
                                    </div>

                                    <!-- Profile -->
                                    <div class="dropdown profile-dropdown overlay-custom-override_profile">
                                        <a class="nav-link pr-0 pl-2 leading-none" data-toggle="dropdown" href="javascript:void(0);" @click="toggleOverlayPanel($event, 'profile')"> 
                                            <img :src="_account.avatar" alt="img" class="avatar avatar-md brround bg-transparent"> 
                                        </a> 

                                        <p-popover ref="profile" class="overlay-profile" appendTo="body">
                                            <div class="">
                                                <div class="text-center border-bottom pb-4 pt-4">
                                                    <a href="#" class="text-center user pb-0 font-weight-bold">{{ auth_fullname() }}</a> 
                                                    <p class="text-center user-semi-title mb-0 text-capitalize">{{ auth_position() }}</p>
                                                </div>

                                                <a class="dropdown-item border-bottom" href="javascript:void(0)" @click.prevent="goToPage('/profile')"> 
                                                    <i class="dropdown-icon mdi mdi-account-outline"></i> My Profile 
                                                </a>
                                                <a class="dropdown-item border-bottom" href="javascript:void(0)" @click.prevent="logout"> 
                                                    <i class="dropdown-icon mdi  mdi-logout-variant"></i> Sign out 
                                                </a> 
                                            </div>
                                        </p-popover>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="page-wrapper" :class="_role">
                        <div class="content">
                            <slot />
                        </div>
                    </div>
                </div>
            </div>

            <footer class="footer pb-0">
                <div class="container">
                    <div class="row align-items-center flex-row-reverse">
                        <div class="col-md-12 col-sm-12 mt-3 mt-lg-0 text-center"> Copyright © 2026 <a href="#">UPHMC-SMS</a>. By <a href="#">UPHMC-LP ITWorks </a>. </div>
                    </div>
                </div>
            </footer>
        </div>
   </div>

   <p-toast />
	<!-- <p-dynamic-dialog /> -->
	<p-confirm
		:draggable="false"
        :pt="{
        	header: { class: confirm_dialog_class.header },
			headerIcons: { class: confirm_dialog_class.header_icon },
			closeButton: { class: confirm_dialog_class.close_button },
			closeButtonIcon: { class: confirm_dialog_class.close_icon },
            root: {  class:'confirm-custom'}
    	}"
	></p-confirm>
</template>
<script>
import GlobalSearch from '@/components/global-search'

export default {
    components: {
        GlobalSearch,
    },
    data() {
        return {
            html: document.getElementsByTagName("html")[0],
            width: window.innerWidth,
            height: window.innerHeight,
            profileDropdownShow: !1,
            items: [],
            interval:null,
            counts:{pending:0,ongoing:0,delivered:0,completed:0,cancelled:0,refunded:0,remitted:0,unremitted:0,total:0,editted:0},
            menus: [
                {
                    group: "Navigation",
					role: ['*'], // for the whole group
                    group_items: [
						{ 
							icon: "home", 
							title: "Dashboard", 
							url: "/", 
							id: "dashboard", 
							pending_count: 0, 
							item_role: ['*'] // for item
						}
                    ]
                },
                {
                    group: "Admin Controls",
					role: ["ADMIN"], // for the whole group
                    group_items: [
						{ 
							icon: "account", 
							title: "Account", 
							url: "/account", 
							id: "account", 
							pending_count: 0, 
							item_role: ["ADMIN"] // for item
						},
                        { 
							icon: "cog-play", 
							title: "Role", 
							url: "/role", 
							id: "role", 
							pending_count: 0, 
							item_role: ["ADMIN"] // for item
						},
                        { 
							icon: "office-building-marker", 
							title: "Department", 
							url: "/department", 
							id: "department", 
							pending_count: 0, 
							item_role: ["ADMIN"] // for item
						},
                        { 
							icon: "account-network", 
							title: "Position", 
							url: "/position", 
							id: "position", 
							pending_count: 0, 
							item_role: ["ADMIN"] // for item
						},
                        { 
							icon: "file-search", 
							title: "Audit Trail", 
							url: "/audit-trail", 
							id: "audit_trail", 
							pending_count: 0, 
							item_role: ["ADMIN"] // for item
						},
                        { 
							icon: "api", 
							title: "API Clients", 
							url: "/api-client", 
							id: "api_client", 
							pending_count: 0, 
							item_role: ["ADMIN"] // for item
						}
                    ]
                },
                {
                    group: "People",
					role: ['*'], // for the whole group
                    group_items: [
                        { 
							icon: "contacts", 
							title: "Phonebook", 
							url: "/phonebook", 
							id: "phonebook", 
							pending_count: 0, 
							item_role: ['*']
						},
                        { 
							icon: "account-group", 
							title: "Group", 
							url: "/group", 
							id: "group", 
							pending_count: 0, 
							item_role: ['ADMIN','MANAGER']
						},
                        { 
							icon: "message-processing", 
							title: "Sms", 
							url: "/sms", 
							id: "sms", 
							pending_count: 0, 
							item_role: ['*']
						}
                    ]
                }
			],
        };
    },
    
    computed: {
        confirm_dialog_class() {
			return this.$store.state.confirm_dialog;
		},

        account(){
            return this._account();
        },

        currentRole() {
            return String(this.$page.props.auth?.user?.role_name || '').toUpperCase();
        },

        visibleMenus() {
            return this.menus
                .map((menu) => {
                    const items = (menu.group_items || []).filter((item) => this.hasRoleAccess(item.item_role));

                    return {
                        ...menu,
                        group_items: items,
                    };
                })
                .filter((menu) => this.hasRoleAccess(menu.role) && menu.group_items.length > 0);
        },

        allowedPages() {
            return [
                '/',
                '/profile',
                ...this.visibleMenus.flatMap((menu) => menu.group_items.map((item) => item.url)),
            ];
        }
    },

    watch: {
        $page: {
            handler() {
                const message = this.$page.props.flash.message;
                
                if (message != null) {
                    switch (message.type) {
                        case "success":
                            this.swalMessage("success", message.text, "Okay", "Cancel", "", false, false, "", false);
                            // this.$toast.success(message.text);
                            break;
                        case "error":
                            this.swalMessage("error", message.text, "Okay", "Cancel", "", false, false, "", false);
                            // this.$toast.error(message.text);
                        break;
                    }
                }
            }
        },

        width() {
            this.adjustSidebar();
        },

        '$page.url'() {
            this.enforcePageAccess();
        }
    },

    methods: {
        hasRoleAccess(roles = []) {
            if (!roles || roles.length === 0) return true;

            const normalizedRoles = roles.map((role) => String(role).toUpperCase());

            if (normalizedRoles.includes('*')) {
                return true;
            }

            return normalizedRoles.includes(this.currentRole);
        },

        normalizePath(path) {
            if (!path) return '/';

            return path.length > 1 ? path.replace(/\/+$/, '') : path;
        },

        isPathMatch(allowedPath, currentPath) {
            const normalizedAllowed = this.normalizePath(allowedPath);
            const normalizedCurrent = this.normalizePath(currentPath);

            if (normalizedAllowed === '/') {
                return normalizedCurrent === '/';
            }

            return normalizedCurrent === normalizedAllowed || normalizedCurrent.startsWith(`${normalizedAllowed}/`);
        },

        firstAllowedPage() {
            return this.allowedPages.find((page) => page && page !== '/profile') || '/';
        },

        enforcePageAccess() {
            const currentPath = this.normalizePath(window.location.pathname);
            const isAllowed = this.allowedPages.some((page) => this.isPathMatch(page, currentPath));

            if (!isAllowed) {
                this.$inertia.visit(this.firstAllowedPage());
            }
        },

        toggleMenu() { 
            let sidebarNavSize = "default";

            if(this.width <= 1140) {
                sidebarNavSize = "full";

                if(this.html.classList.contains("sidebar-enable")) {
                    this.hideBackdrop();
                }
                else {
                    this.showBackdrop();
                }
            }
            else {
                if(!this.html.classList.contains("sidebar-enable")) {
                    sidebarNavSize = "condensed";
                }
            }
            
            this.html.classList.toggle("sidebar-enable");
            this.html.setAttribute("data-sidenav-size", sidebarNavSize);
        },

        toggleProfileDropdown() {
            let e = document.getElementById('profile-dropdown');
            this.profileDropdownShow = !this.profileDropdownShow;

            if(this.profileDropdownShow) {
                e.style.cssText = "position: absolute; inset: 0px 0px auto auto; margin: 0px; transform: translate(0px, 72px);";
            }
            else {
                e.style.cssText = null;
            }
        },

        logout() {
            this.$inertia.post("/logout");
        },
        
        goToPage(page) {
            // this.$inertia.visit(this.route(page));
            this.$inertia.visit(page);

            if(this.width <= 1140) {
                this.toggleMenu();
            }
        },

        getDimensions() {
            this.width = window.innerWidth;
            this.height = window.innerHeight;
        },

        adjustSidebar() {
            let sidebarNavSize = "default";

            if(this.width <= 1140) {
                sidebarNavSize = "full";

                if(!this.html.classList.contains("sidebar-enable")) {
                    this.hideBackdrop();
                }
                else {
                    this.showBackdrop();
                }
            }
            else {
                this.hideBackdrop();

                if(this.html.classList.contains("sidebar-enable")) {
                    sidebarNavSize = "condensed";
                }
            }

            this.html.setAttribute("data-sidenav-size", sidebarNavSize);
        },

        showBackdrop() {
            if(document.getElementById("custom-backdrop")) return;

            var e = document.createElement("div"),
                t = (e.id = "custom-backdrop", e.classList = "offcanvas-backdrop fade show", document.body.appendChild(e), document.body.style.overflow = "hidden", 1140 < window.innerWidth && (document.body.style.paddingRight = "15px"), this);
                
                e.addEventListener("click", function(e) {
                    t.html.classList.remove("sidebar-enable"), t.hideBackdrop()
                });
        },

        hideBackdrop() {
            var e = document.getElementById("custom-backdrop");
                e && (document.body.removeChild(e), document.body.style.overflow = null, document.body.style.paddingRight = null)
        },

// new

        toggleFullScreen() {
            document.body.classList.toggle("fullscreen-enable"),
            document.fullscreenElement || document.mozFullScreenElement || document.webkitFullscreenElement ? document.cancelFullScreen ? document.cancelFullScreen() : document.mozCancelFullScreen ? document.mozCancelFullScreen() : document.webkitCancelFullScreen && document.webkitCancelFullScreen() : document.documentElement.requestFullscreen ? document.documentElement.requestFullscreen() : document.documentElement.mozRequestFullScreen ? document.documentElement.mozRequestFullScreen() : document.documentElement.webkitRequestFullscreen && document.documentElement.webkitRequestFullscreen(Element.ALLOW_KEYBOARD_INPUT)
        },

        isActive(route){
            let curr = window.location.pathname + window.location.search
            return (route == curr) ? true : false;
        },

        toggleSideNav(type) {
            if(type == 'click') document.body.classList.toggle("sidenav-toggled");
            else {
                if (document.body.classList.contains("sidenav-toggled")) {
                    console.log('contains');
                    document.body.classList.toggle("sidenav-toggled1");
                }
            }
        },

        toggleOverlayPanel(event, type) {
            if(this.$refs[type]) {  
                this.$refs[type].toggle(event);

                let button_overlay = document.querySelector(`.overlay-custom-override_${type}`).getBoundingClientRect();
                let top = button_overlay.y + button_overlay.height;
                
                setTimeout(() => {
                    let overlay = document.querySelectorAll(`.overlay-${this.removeReplaceUnderscore(type,1)}`);
                    overlay.forEach(element => {
                        element.style.top = `${top}px`;
                        element.style.display = 'block';
                    });
                },100);
            }
        },

        handlePageShow(event) {
            const navigationEntries = performance.getEntriesByType('navigation');
            const navigationType = navigationEntries.length ? navigationEntries[0].type : null;

            if (event.persisted || navigationType === 'back_forward') {
                window.location.reload();
            }
        },
    },
    
    mounted() {
        this.adjustSidebar();
        this.enforcePageAccess();
        window.addEventListener('resize', this.getDimensions);
        window.addEventListener('pageshow', this.handlePageShow);
        this.html.setAttribute("data-bs-theme", "light");
        document.body.classList.remove("dark-mode");
        document.body.classList.add("light-mode");
        localStorage.removeItem("night_mode");

    },

    unmounted() {
        window.removeEventListener('resize', this.getDimensions);
        window.removeEventListener('pageshow', this.handlePageShow);
    },

    beforeDestroy() {
        clearInterval(this.interval);
    },

    beforeUnmount() {
        clearInterval(this.interval);
        window.removeEventListener('pageshow', this.handlePageShow);
    } 
};
</script>
