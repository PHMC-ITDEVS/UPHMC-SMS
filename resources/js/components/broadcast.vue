<template>
  
</template>

<script>
export default {
    methods: {
        socket_listener() {
            this.$socket.off('connect');
            this.$socket.off('connect_error');
            this.$socket.off('sms_status');

            this.$socket.on("connect", () => {
                // console.log('[broadcast] connected', this.$socket.id);
            });

            this.$socket.on("connect_error", (error) => {
                console.error('[broadcast] connect_error', error?.message || error);
            });

            this.$socket.on('sms_status', (data) => {
                // console.log('[broadcast] sms_status', data);
                this.emitter.emit("sms_status", data);
            });
        },
    },

    mounted() {
        // console.log('[broadcast] mounted');
        this.$socket.close();
        this.socket_listener();
        this.$socket.open();
    },

    beforeUnmount() {
        // console.log('[broadcast] beforeUnmount');
        this.$socket.close();
    },
}
</script>
