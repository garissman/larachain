<script setup>

defineProps({
    messages: Array
})
const toHtml = (body) => {
    return body.replace(/(?:\r\n|\r|\n)/g, "<br>");
}
</script>
<template>
    <div v-for="message in messages">
        <div
            v-if="message.role==='assistant'"
            class="flex mb-4"
        >
            <!-- Incoming Message -->
            <div class="w-9 h-9 rounded-full flex items-center justify-center mr-2">
                <img alt="User Avatar" class="w-8 h-8 rounded-full"
                     src="https://placehold.co/200x/4f46e5/ffffff.svg?text=TwAina&font=Lato">
            </div>
            <div class="max-w-96 bg-green-500 rounded-lg p-3 gap-3">
                <div class="text-white text-xl"
                     v-html="toHtml(message.body)"
                />
                <div
                    v-if="message.is_been_whisper"
                    class="wave"
                >
                    <span class="dot bg-white"></span>
                    <span class="dot bg-white"></span>
                    <span class="dot bg-white"></span>
                </div>
            </div>
        </div>
        <div
            v-if="message.role==='user'"
            class="flex justify-end mb-4"
        >
            <!-- Outgoing Message -->
            <div class="flex max-w-96 bg-indigo-500 text-xl text-white rounded-lg p-3 gap-3">
                <p>
                    {{ message.body }}
                </p>
            </div>
            <div class="w-9 h-9 rounded-full flex items-center justify-center ml-2">
                <img alt="My Avatar" class="w-8 h-8 rounded-full"
                     src="https://placehold.co/200x/b7a8ff/ffffff.svg?text=User&font=Lato">
            </div>
        </div>
    </div>
</template>
<style>
div.wave {
    .dot {
        display:inline-block;
        width:5px;
        height:5px;
        border-radius:50%;
        margin-right:3px;
        animation: wave 1.3s linear infinite;
        &:nth-child(2) {
            animation-delay: -1.1s;
        }
        &:nth-child(3) {
            animation-delay: -0.9s;
        }
    }
}

@keyframes wave {
    0%, 60%, 100% {
        transform: initial;
    }

    30% {
        transform: translateY(-15px);
    }
}
</style>
