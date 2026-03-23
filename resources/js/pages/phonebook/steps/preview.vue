<template>
    <div>
        <!-- note for the preview -->
        <div class="alert alert-info mb-4" role="alert">
            <i class="pi pi-info-circle mr-2"></i>
            This is a preview of the contact information you entered. Please review the details before saving.
        </div>
        <div class="phonebook-preview d-flex flex-column gap-3">
            <div class="preview-avatar d-flex justify-content-center">
                <img
                    v-if="avatarSrc"
                    :src="avatarSrc"
                    alt="Contact Avatar"
                    class="avatar-image"
                />
                <div v-else class="avatar-placeholder d-flex align-items-center justify-content-center no-image-width">
                    <i class="pi pi-user text-2xl"></i>
                </div>
            </div>

            <div class="preview-item">
                <label>Name</label>
                <p>{{ data.name || '-' }}</p>
            </div>

            <div class="preview-item">
                <label>Phone Number</label>
                <p>{{ formattedPhoneNumber }}</p>
            </div>

            <div class="preview-item">
                <label>Notes</label>
                <p class="notes">{{ data.notes || '-' }}</p>
            </div>
        </div>
    </div>
</template>
<script>

    export default {
        props: ['service'],

        data() {
            return {
                data:{
                    avatar: null,
                    new_avatar: null,
                    image: null,
                    name: null,
                    phone_number: null,
                    notes: null,
                }
            }
        },

        methods: {
            fillInfo() {
                Object.entries(this.data).forEach((item) => {
                    let label = item[0];
                    this.data[label] = (label.includes('date') && this.service.data[label]) ? this.formatDate(this.parseDate(this.service.data[label])) : this.service.data[label];
                    
                });
            },
        },

        computed: {
            avatarSrc() {
                return this.data.new_avatar || this.data.image || this.data.avatar || '';
            },

            formattedPhoneNumber() {
                if (!this.data.phone_number) return '-';
                return `+63${this.data.phone_number}`;
            },
        },

        mounted() {
            this.fillInfo();
        },

        watch: {
            data: {
                handler: function () {
                    Object.entries(this.data).forEach((item) => {
                        let label = item[0];
                        this.service.data[label] = label.includes('date') ? this.formatDate(this.parseDate(this.service.data[label])) : this.data[label];
                    });
                },
                deep: true,
                immediate: false
            }
        }
    }
</script>
<style scoped>
    .phonebook-preview {
        gap: 1rem;
    }

    .preview-avatar {
        margin-bottom: 0.5rem;
    }

    .avatar-image,
    .avatar-placeholder {
        /* width: 140px; */
        height: 150px;
        /* border-radius: 50%; */
        object-fit: cover;
        border: 1px solid #d8e2ec;
        background: #f5f8fb;
    }

    .avatar-placeholder {
        color: #7a8b99;
    }

    .no-image-width {
        width: 140px;
    }

    .preview-item label {
        display: block;
        margin-bottom: 0.25rem;
        font-size: 0.85rem;
        color: #6b7280;
        font-weight: 600;
    }

    .preview-item p {
        margin: 0;
        font-size: 0.95rem;
        color: #111827;
    }

    .preview-item .notes {
        white-space: pre-wrap;
        word-break: break-word;
    }
</style>
