<template>
    <div class="loader">
        <div
            class="dots"
            :style="{
                'transform': `scale(${styleScale})`
            }"
        >
            <span style="--i: 1;"></span>
            <span style="--i: 2;"></span>
            <span style="--i: 3;"></span>
            <span style="--i: 4;"></span>
            <span style="--i: 5;"></span>
            <span style="--i: 6;"></span>
            <span style="--i: 7;"></span>
            <span style="--i: 8;"></span>
            <span style="--i: 9;"></span>
            <span style="--i: 10;"></span>
            <span style="--i: 11;"></span>
            <span style="--i: 12;"></span>
            <span style="--i: 13;"></span>
            <span style="--i: 14;"></span>
            <span style="--i: 15;"></span>
        </div>
        <div class="message-container text-center">
            <h2 v-if="!!title">{{ title }}</h2>
            <p v-if="!!message">{{ message }}</p>
        </div>
    </div>
</template>
<script>
    export default {
        props: [
            "title", "message", "styleScale"],
    }
</script>
<style scoped>
h2 {
    font-weight: 500;
}

.loader {
    display: flex;
    align-items: center;
    justify-content: center;
    flex-direction: column;
    /* height: 100vh;
    width: 100%; */
}

.loader .dots {
    min-height: 100px;
    position: relative;
    display: flex;
    align-items: center;
    justify-content: center;
}

.loader .dots span {
    position: absolute;
    height: 13px;
    width: 4px;
    background: #6c757d;
    transform: rotate(calc(var(--i) * (360deg / 15))) translateY(25px);
    animation: loader 1.5s linear infinite;
    animation-delay: calc(var(--i) * 0.1s);
    opacity: 0;
}

@keyframes loader {
    0% {
        opacity: 1;
    }
    80%,
    100% {
        opacity: 0;
    }
}
</style>