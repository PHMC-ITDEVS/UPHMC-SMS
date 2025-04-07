<template>
    <div>
        <div class="wrapper" v-if="states.length > 1">
            <ol class="w-stepper">
                <li
                    v-for="(state, index) in states"
                    :key="index"
                    class="w-stepper__item"
                    :class="{
                        'active': (index === context.step_no),
                        'completed': (index < context.step_no),
                        'clickable_step':clickable_step
                    }"
                    @click="move(index)"
                >
                    <div class="w_stepper__item_content">
                        <span class="w_stepper__title">{{ state.title }}</span>
                    </div>
                </li>
            </ol>
        </div>

        <section class="step-view">
            <component :is="states[context.step_no].view" :service="context"></component>
        </section>

        <div class="vr-wizard--footer">
            <div class="vr-wizard--footer-left">
                <button
                    class="vr-wizard--prev-btn vr-wizard--btn"
                    @click="prev" type="button"
                    :disabled="context.step_no < 1"
                    v-if="states.length > 1"
                >
                    {{ (prevText || "Prev") }}
                </button>
            </div>
            <div class="vr-wizard--footer-right" v-if="!context.loading">
                <button
                    class="vr-wizard--done-btn vr-wizard--btn"
                    @click="complete" type="button"
                    :disabled="(typeof states[context.step_no].guard == 'boolean') ? states[context.step_no].guard : !1"
                    v-if="(states.length - 1) == context.step_no"
                >
                    {{ completeText || "Done" }}
                </button>
                <button
                    class="vr-wizard--next-btn vr-wizard--btn"
                    @click="next" type="button"
                    :disabled="(typeof states[context.step_no].guard == 'boolean') ? states[context.step_no].guard : !1"
                    v-else
                >
                    {{ (nextText || "Next") }}
                </button>
            </div>
            <div class="vr-wizard--footer-right" v-else>
                <button class="vr-wizard--done-btn vr-wizard--btn" disabled>
                    <span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span> 
                    Loading...
                </button>
            </div>
        </div>
    </div>
</template>
<script>
    import { defineComponent } from "vue";
    
    export default defineComponent({
        name: 'FormWizard',
        inheritAttrs: false,
        emits: ['onPrev', 'onNext', 'onComplete'],
        props: {
            prevText: "",
            nextText: "",
            completeText: "",
            clickable_step:false,
            customStep: {
                type: Boolean,
                default: !1
            },
            context : {
                type: Object,
                required: true
            },
            states: {
                type: Array,
                required: true
            }
        },
        data() {
            return {
                // step: 0,
                loader: !1
            }
        },
        methods: {
            prev () {
                if(this.context.step_no < 1) return;

                this.$emit('onPrev');

                if(!this.customStep) {
                    this.context.step_no--;
                }
            },
            move(index) {
                this.$emit('onMove',index);
            },
            next () {
                if(this.context.step_no > this.states.length - 1) return;
                this.$emit('onNext');

                if(!this.customStep) {
                    this.context.step_no++;
                }
            },
            complete() {
                if(this.context.step_no !== this.states.length - 1) return;
                
                this.$emit('onComplete', this.context);
            }
        }
    });
</script>
<style>
    :root {
        --wizard-stepper--active: rgba(101,113,255,.2);
        --wizard-stepper--active-font-color: #6571ff;
        --wizard-circle-border-color: #6571ff;
        --wizard-stepper--btn-bg: rgba(101,113,255,.2);
        --wizard-stepper--btn-color: #6571ff;
        --wizard-stepper--btn-border-color: transparent;
        --wizard-stepper--prev-btn-bg: rgba(101,113,255,.2);
        --wizard-stepper--prev-btn-color: #6571ff;
        --wizard-stepper--prev-btn-border-color: transparent;
        --wizard-line-color: #dee2e6;
        --wizard-circle-color: #dee2e6;
        --wizard-stepper--disabled: #dee2e6;
        --wizard-stepper--disabled-font: #dee2e6;
    }
</style>
<style scoped>
    .w-stepper {
        display: flex;
        --circle-size: clamp(1rem, 1vw, 2rem);
        --spacing: clamp(0.25rem, 1vw, 0.5rem);
        padding: 0;
        margin: 1rem;
    }

    .w-stepper__item.active, .w-stepper__item.completed {
        color: var(--wizard-stepper--active-font-color)!important;
    }

    .w-stepper__item {
        display: flex;
        gap: var(--spacing);
        align-items: center;
    }

    .w_stepper__item_content {
    display: flex;
    gap: var(--spacing);
    align-items: center;
}
    

    .w_stepper__item_content::before {
        --size: 3rem;
        content: "";
        display: block;
        width: var(--circle-size);
        height: var(--circle-size);
        border-radius: 50%;
        background-color: var(--wizard-circle-color);
    }


    .w-stepper__item.active .w_stepper__item_content::before, .w-stepper__item.completed .w_stepper__item_content::before {
        border-color: var(--wizard-circle-border-color);
    }

    .w-stepper__item.active .w_stepper__item_content::before, .w-stepper__item.completed .w_stepper__item_content::before {
        background-color: var(--wizard-stepper--active);
    }

    .w-stepper__item:not(:first-child)::before {
        content: "";
        position: relative;
        z-index: -1;
        height: 2px;
        background-color: var(--wizard-line-color);
        flex: 1;
        margin-right: 0.5rem;
    }

    .w-stepper__item:not(:first-child)::before {
        z-index: 0!important;
    }

    /* .w-stepper__item.active:not(:first-child)::before, .w-stepper__item.completed:not(:first-child)::before {
        background-color: var(--wizard-circle-border-color)!important;
    } */

    .w-stepper__item:not(:first-child)::before {
        content: "";
        position: relative;
        z-index: -1;
        height: 2px;
        background-color: var(--wizard-line-color);
        flex: 1;
        margin-right: 0.5rem;
    }

    .w-stepper__item:not(:first-child) {
        flex: 1;
        margin-inline-start: 0.5rem;
    }

    .step-view {
        /* margin-block: 2rem; */
        /* background: #f5f7f8; */
        padding: 1rem;
    }

    .vr-wizard--footer {
        display: grid;
        grid-template-columns: repeat(2, auto);
    }

    .vr-wizard--footer-left {
        text-align: start;
    }

    .vr-wizard--footer-right {
        text-align: end;
    }

    .vr-wizard--btn {
        background: var(--wizard-stepper--btn-bg);
        color: var(--wizard-stepper--btn-color);
        border: 1px solid var(--wizard-stepper--btn-border-color);
        cursor: pointer;
        text-transform: uppercase;
        font-weight: 600;
        padding: 0.5rem 1rem;
        margin: 0 0.5rem;
    }

    .vr-wizard--prev-btn {
        background: var(--wizard-stepper--prev-btn-bg);
        color: var(--wizard-stepper--prev-btn-color);
        border: 1px solid var(--wizard-stepper--prev-btn-border-color);
    }

    .vr-wizard--prev-btn[disabled] {
        opacity: 0.6;
    }

    @media(max-width: 768px) {
        .w-stepper__item:not(.active), .wrapper {
            display: none;
        }

        .step-view {
            margin-block: 1.25rem;
            background: #f5f7f8;
            padding: .725rem;
        }
    }

    .clickable_step{
        cursor: pointer;
    }
</style>