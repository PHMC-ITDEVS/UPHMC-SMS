<template>
    <!-- <div class="loader" :class="customClass">
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
    </div> -->
    <div class="loadingio-spinner-spinner-nq4q5u6dq7r">
        <div class="ldio-x2uulkbinbj">
            <div></div>
            <div></div>
            <div></div>
            <div></div>
            <div></div>
            <div></div>
            <div></div>
            <div></div>
            <div></div>
            <div></div>
            <div></div>
            <div></div>
            <div></div>
            <div></div>
            <div></div>
            <div></div>
        </div>
    </div>
</template>
<script>
    export default {
        props: [
            "title", "message", "styleScale","customClass"
        ],
    }
</script>
<style scoped>
h2 {
    font-weight: 500;
}

@keyframes ldio-x2uulkbinbj {
  0% { opacity: 1 }
  100% { opacity: 0 }
}

.ldio-x2uulkbinbj div {
  left: 47px;
  top: 27.5px;
  position: absolute;
  animation: ldio-x2uulkbinbj linear 1s infinite;
  background: #29327f;
  width: 6px;
  height: 5px;
  border-radius: 1.44px / 1.44px;
  transform-origin: 3px 22.5px;
}.ldio-x2uulkbinbj div:nth-child(1) {
  transform: rotate(0deg);
  animation-delay: -0.9375s;
  background: #29327f;
}.ldio-x2uulkbinbj div:nth-child(2) {
  transform: rotate(22.5deg);
  animation-delay: -0.875s;
  background: #29327f;
}.ldio-x2uulkbinbj div:nth-child(3) {
  transform: rotate(45deg);
  animation-delay: -0.8125s;
  background: #29327f;
}.ldio-x2uulkbinbj div:nth-child(4) {
  transform: rotate(67.5deg);
  animation-delay: -0.75s;
  background: #29327f;
}.ldio-x2uulkbinbj div:nth-child(5) {
  transform: rotate(90deg);
  animation-delay: -0.6875s;
  background: #29327f;
}.ldio-x2uulkbinbj div:nth-child(6) {
  transform: rotate(112.5deg);
  animation-delay: -0.625s;
  background: #29327f;
}.ldio-x2uulkbinbj div:nth-child(7) {
  transform: rotate(135deg);
  animation-delay: -0.5625s;
  background: #29327f;
}.ldio-x2uulkbinbj div:nth-child(8) {
  transform: rotate(157.5deg);
  animation-delay: -0.5s;
  background: #29327f;
}.ldio-x2uulkbinbj div:nth-child(9) {
  transform: rotate(180deg);
  animation-delay: -0.4375s;
  background: #29327f;
}.ldio-x2uulkbinbj div:nth-child(10) {
  transform: rotate(202.5deg);
  animation-delay: -0.375s;
  background: #29327f;
}.ldio-x2uulkbinbj div:nth-child(11) {
  transform: rotate(225deg);
  animation-delay: -0.3125s;
  background: #29327f;
}.ldio-x2uulkbinbj div:nth-child(12) {
  transform: rotate(247.5deg);
  animation-delay: -0.25s;
  background: #29327f;
}.ldio-x2uulkbinbj div:nth-child(13) {
  transform: rotate(270deg);
  animation-delay: -0.1875s;
  background: #29327f;
}.ldio-x2uulkbinbj div:nth-child(14) {
  transform: rotate(292.5deg);
  animation-delay: -0.125s;
  background: #29327f;
}.ldio-x2uulkbinbj div:nth-child(15) {
  transform: rotate(315deg);
  animation-delay: -0.0625s;
  background: #29327f;
}.ldio-x2uulkbinbj div:nth-child(16) {
  transform: rotate(337.5deg);
  animation-delay: 0s;
  background: #29327f;
}
.loadingio-spinner-spinner-nq4q5u6dq7r {
  width: 100px;
  height: 100px;
  display: inline-block;
  overflow: hidden;
  background: rgba(255, 255, 255, 0);
}
.ldio-x2uulkbinbj {
  width: 100%;
  height: 100%;
  position: relative;
  transform: translateZ(0) scale(1);
  backface-visibility: hidden;
  transform-origin: 0 0; /* see note above */
}
.ldio-x2uulkbinbj div { box-sizing: content-box; }

</style>