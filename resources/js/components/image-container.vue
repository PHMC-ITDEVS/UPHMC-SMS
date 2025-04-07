<template>
    <div class="image-container" style="text-align:center;" :style="{'height':(height) ? height : '130px'}">
        <img :src="url" style="height:100%"/>
        <div class="action">
            <v-btn v-if="btn=='xs'" x-small color="primary" @click="selectFile"><i class="mdi mdi-file-image"></i></v-btn>
            <v-btn v-if="btn=='md'" x-medium color="primary" @click="selectFile"><i class="mdi mdi-file-image"></i></v-btn>
            <v-btn v-if="btn=='lg'" x-large color="primary" @click="selectFile"><i class="mdi mdi-file-image"></i></v-btn>
            <input ref="file" type="file"  accept="image/png, image/jpeg" hidden @change="Image_onFileChanged($event)">
        </div>
    </div>
</template>
<script>
export default {
    props: ["src","height","btn"],
    computed: {
        url:function(){
            let rand =Math.random();
            let url =  this.src ?  `${this.src}?${rand}` : `/images/default.png`;
            return this.new_url ? this.new_url : url;
        }
    },
    data(){
        return {
            new_url:""
        };
    },
    methods: {
        selectFile(){
            this.$refs.file.click();
        },
        Image_onFileChanged (event) {
            let self = this;
            let file = event.target.files[0];
            if (!file)
                return;

            let new_url = URL.createObjectURL(file);
            if (new_url)
            {
                self.new_url = new_url;
                self.$emit('setSelectedImage', file);
            }
        },
    },

    watch:{
        src: {
            handler(value) {                 
               this.new_url = null;
            },
            immediate: true
        },    
    }
};    
</script>
<style scoped>
.image-container{
    width:100%;
    position:relative;
    text-align: center;
    border: 1px solid lightgray;
    border-radius: 5px;
    object-fit: contain;
}

.image-container img{
    max-width:100%;
    object-fit: contain;
}

.action{
    position: absolute;
    text-align: center;
    bottom: 5px;
    width: 100%;
}
</style>