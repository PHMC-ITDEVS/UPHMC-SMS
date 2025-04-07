<template>
    <div class="upload-wrapper d-flex align-items-center relative">
       
            <input ref="browse" type="file" accept=".csv, .json, .txt, .jpg, .jpeg, .png" hidden @change="fileChanged($event)" />

            <div class="col-12 md:col-4">
                <div class="p-inputgroup">
                    <p-input-text placeholder="File Name" v-model="file_name" disabled :class="{ 'p-invalid': error }"/>
                    <p-button class="btn btn-info" label="Select" @click="browseFile"/>
                </div>
            </div>
    </div>
</template>
<script>
    import { defineComponent } from "vue";
    
    export default defineComponent({
        name: 'ImageUpload',
        inheritAttrs: false,
        emits: ['setImage'],
        props: {
            error:"",
            url : "",
            // style: "",
            containerWidth: {
                type: Number,
                default: 200
            },
            containerHeight: {
                type: Number,
                default: 150
            }

        },
        data() {
            return {
                onloadFlag: !0,
                cameraLoading: !1,
                cameraOpen: !1,
                photoCaptured: !1,
                photoShot: !1,
                hasImage: !1,
                file_name:""
            }
        },

        mounted() {
        },
        methods: {

            browseFile() {
                this.$refs.browse.click();
            },

            async fileChanged (event) {
                let file = event.target.files[0];
                

                if(!file)
                    return;

                let fileInfo = await this.fileToBase64(file);
                this.file_name = file.name;
                this.$emit("setFile", file);

                // if(fileInfo) {
                //     this.imageLink = fileInfo.data;
                //     this.$emit("setImage", this.imageLink);
                // }
            },

            fileToBase64(file) {
                return new Promise((resolve, reject) => {
                    let fileReader = new FileReader();
                    fileReader.onload = function(){
                        return resolve({data: fileReader.result, name:file.name, size: file.size, type: file.type});
                    }

                    fileReader.readAsDataURL(file);
                })
            }
        },
        computed: {
            src() {
                let rand = Math.random(),
                    src =  this.url ? `${this.url}` : "";

                if(!!src && this.onloadFlag) {
                    this.onloadFlag = !1;
                    this.hasImage = !0;
                }
                
                return this.imageLink ? this.imageLink : src;
            }
        },
        watch:{
        },
        beforeUnmount() {
            
        }
    });
</script>
<style scoped>
    .relative {
        position: relative;
    }

    .d-flex {
        display: -webkit-box;
        display: -ms-flexbox;
        display: flex;
    }

    .align-items-center {
        -webkit-box-align: center;
        -ms-flex-align: center;
        align-items: center;
    }

    .upload-container {
        position: relative;
        border: 2px dashed #bacbdb;
        border-radius: 3px;
        padding: 2rem 1.225rem;
        text-align: center;
        background-color: #f9fbfc;
        max-width: 100%;
        cursor: pointer;
    }

    .img-placeholder-icon {
        max-width: 50px;
        margin-bottom: 0.525rem;
        filter: opacity(0.7);
    }

    .upload-label {
        color: #043d75;
        font-weight: 500;
    }

    .upload-label > .action {
        text-decoration: underline;
        color: #0e7eff;
        text-transform: uppercase;
    }
    
    video, canvas, .img-preview-cont {
        position: absolute;
        top: -2px;
        left: -2px;
        width: calc(100% + 4px);
        height: calc(100% + 4px);
        margin: 0;
        background-color: #f9fbfc;
        border: 1px solid #f9fbfc;
        border-radius: 3px;
    }

    .img-preview-cont .img-preview {
        max-width: 100%;
        max-height: 100%;
    }

    .camera-action-btn {
        position: absolute;
        right: -0.5rem;
        transform: translate(100%);
    }

    .btn-circle {
        border-radius: 30px!important;
    }

    .flex-column .p-button+.p-button {
        margin-top: 0.5rem;
        margin-left: 0;
    }

    .loader-container {
        position: absolute;
        width: 100%;
        height: 100%;
        top: 0;
        left: 0;
        background: #f9fbfc;
    }

    .spinner {
        width: 32px;
        height: 32px;
    }

    .spinner .path {
        stroke: #0e7eff;
        stroke-linecap: round;
        -webkit-animation: dash 1.1s ease-in-out infinite;
        animation: dash 1.1s ease-in-out infinite;
    }

    @keyframes dash {
        0% {
            stroke-dasharray: 1, 160;
            stroke-dashoffset: 0;
        }
        50% {
            stroke-dasharray: 80, 160;
            stroke-dashoffset: -32;
        }
        100% {
            stroke-dasharray: 80, 160;
            stroke-dashoffset: -124;
        }
    }

    @media (max-width: 768px) {
        .upload-wrapper {
            flex-direction: column;
        }

        .camera-action-btn {
            position: relative;
            right: unset;
            transform: unset;
            flex-direction: row!important;
            align-items: center;
            justify-content: center;
            margin-top: 0.725rem;
        }
        
        .upload-container, .camera-action-btn, .upload-wrapper {
            width: 100%!important;
        }

        .upload-container {
            padding: 0 1.225rem;
        }

        .flex-column .p-button+.p-button {
            margin-top: 0;
            margin-left: 0.5rem;
        }

        video, canvas, .img-preview-cont {
            top: -3px;
        }
    }
</style>
<style>
    .camera-action-btn .p-button {
        width: 2.357rem;
        padding: 0.5rem 0;
        justify-content: center;
    }

    .camera-action-btn .p-button-label {
        display: none;
    }

    .camera-action-btn  .p-button .p-button-icon-left {
        margin-right: 0;
    }

    @media (max-width: 768px) {
        .camera-action-btn .p-button-label {
            display: block;
        }

        .camera-action-btn  .p-button .p-button-icon-left {
            margin-right: 0.5rem;
        }

        .camera-action-btn .p-button {
            width: unset;
            padding: 0.5rem 1rem;
            justify-content: unset;
        }
    }
</style>