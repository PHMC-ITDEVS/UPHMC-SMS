<template>
    <div class="upload-wrapper d-flex align-items-center relative">
        <div class="upload-container d-flex flex-column align-items-center justify-content-center" ref="upload_container">
            <input ref="browse" type="file" accept="image/png, image/jpeg" hidden @change="imageChanged($event)" />

            <img src="/images/img-icon.png" alt="Placeholder" class="img-placeholder-icon" />

            <div class="upload-label">
                <a href="javascript:void(0);" class="action" @click="toggleCamera">Capture</a> or <a href="javascript:void(0);" class="action" @click="browseFile">Upload</a>
            </div>

            <div class="loader-container d-flex align-items-center justify-content-center" v-if="cameraOpen && cameraLoading">
                <svg class="spinner" viewBox="0 0 16 18">
                    <path class="path" fill="none" stroke-width="2" d="M7.21487 1.2868C7.88431 0.9044 8.73031 0.9044 9.39974 1.2868L9.40283 1.28856L14.4613 4.20761C15.1684 4.598 15.5746 5.33558 15.5746 6.11465V8.99996V11.8853C15.5746 12.6507 15.1632 13.3848 14.4617 13.7721L9.37973 16.7132C8.71029 17.0956 7.86428 17.0956 7.19485 16.7132L7.19088 16.7109L2.11279 13.772C1.40602 13.3816 1 12.6441 1 11.8653V8.98995V6.11465C1 5.31458 1.44381 4.59039 2.10827 4.21051L7.21487 1.2868Z" />
                </svg>
            </div>

            <video ref="camera" v-show="cameraOpen && !cameraLoading && !photoCaptured" autoplay></video>
            <canvas v-show="hasImage" id="capturedImg" ref="captured_img" hidden></canvas>

            <div class="img-preview-cont" v-show="!!hasImage">
                <img ref="image_src" class="img-preview" alt="Preview" :src="src"/>
            </div>
        </div>
        
        <div class="camera-action-btn d-flex flex-column" v-if="(!!cameraOpen && !cameraLoading) || !!hasImage">
            <p-button
                v-if="!photoCaptured"
                class="btn btn-light btn-circle"
                icon="pi pi-camera"
                label="Capture"
                @click="takePhoto"
            />

            <p-button
                v-else
                class="btn btn-light btn-circle"
                icon="pi pi-sync"
                label="Retake"
                @click="takePhoto"
            />

            <p-button
                class="btn btn-info btn-circle"
                icon="pi pi-folder-open"
                label="Browse"
                @click="browseFile"
            />
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
                imageLink: ""
            }
        },

        mounted() {
            this.$refs.upload_container.style.width = this.containerWidth +"px";
            this.$refs.upload_container.style.height = this.containerHeight +"px";
        },
        methods: {
            toggleCamera() {
                if(this.cameraOpen) {
                    this.cameraOpen = !1;
                    this.photoCaptured = !1;
                    this.photoShot = !1;
                    this.stopCameraStream();
                }
                else
                {
                    this.cameraOpen = !0;
                    this.createCameraElement();
                }
            },

            createCameraElement() {
                this.cameraLoading = !0;
      
                const constraints = (window.constraints = {
                    audio: false,
                    video: !0
                });

                navigator.mediaDevices
                    .getUserMedia(constraints)
                    .then(stream => {
                        this.cameraLoading = !1;
                        this.$refs.camera.srcObject = stream;
                    })
                    .catch(error => {
                        this.cameraLoading = !1;
                        alert("May the browser didn't support or there is some errors.");
                    });
            },

            stopCameraStream() {
                let tracks = this.$refs.camera.srcObject.getTracks();

                tracks.forEach(track => {
                    track.stop();
                });
            },

            takePhoto() {
                if(!this.photoCaptured) {
                    this.photoShot = !0;

                    const FLASH_TIMEOUT = 50;

                    setTimeout(() => {
                        this.photoShot = !1;
                    }, FLASH_TIMEOUT);
                }
                
                if(!this.cameraOpen) {
                    this.hasImage = !1;
                    this.toggleCamera();
                    return;
                }

                this.photoCaptured = !this.photoCaptured;
                this.hasImage = !this.hasImage;
                
                const context = this.$refs.captured_img.getContext('2d');
                context.canvas.width = this.containerWidth;
                context.canvas.height = this.containerHeight;
                context.drawImage(this.$refs.camera, 0, 0, this.containerWidth, this.containerHeight);
                
                let dataUrl = context.canvas.toDataURL("image/jpeg");

                if(dataUrl) {
                    this.imageLink = dataUrl;
                    this.$emit("setImage", this.imageLink);
                }
            },

            browseFile() {
                if(this.cameraOpen) {
                    this.cameraOpen = !1;
                    this.photoCaptured = !1;
                    this.photoShot = !1;
                    this.stopCameraStream();
                }

                this.$refs.browse.click();
            },

            async imageChanged (event) {
                let file = event.target.files[0];

                if(!file)
                    return;

                let fileInfo = await this.fileToBase64(file);

                if(fileInfo) {
                    this.imageLink = fileInfo.data;
                    this.photoShot = !0;
                    this.hasImage = !0;
                    this.$emit("setImage", this.imageLink);
                }
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
            url: {
                handler(value) {
                    this.imageLink = "";
                },
                immediate: true
            }
        },
        beforeUnmount() {
            if(this.cameraOpen) {
                this.toggleCamera();
            }
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