<template>
    <div class="page-single guest-layout flex-column">
        <div class="container">
            <div class="col mx-auto">
                <div class="row justify-content-center">
                    <div class="col-md-7 col-lg-4">
                        <slot />
                    </div>
                </div>
            </div>
        </div>
        <p-toast position="top-center"/>
        <footer class="footer footer-alt px-0">
            2025 &copy; Template by&nbsp;<span href="#!" class="text-dark text-decoration-underline">Sixpent Inc.</span>
        </footer>
    </div>
</template>

<script>
export default {
  watch: {
    $page: {
      handler() {
        const message = this.$page.props.flash.message;
        if (message != null) {
          switch (message.type) {
            case "success":
              this.$toast.success(message.text);
              break;
            case "error":
              this.$toast.error(message.text);
              break;
          }
        }
      },
    },
  },
};
</script>
