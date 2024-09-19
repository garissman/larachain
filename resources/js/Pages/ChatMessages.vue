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
        <!-- Incoming Message -->
        <div
            v-if="message.role==='assistant'"
            class="flex mb-4 cursor-pointer"
        >
            <div class="w-9 h-9 rounded-full flex items-center justify-center mr-2">
                <img alt="User Avatar" class="w-8 h-8 rounded-full"
                     src="https://placehold.co/300x/ffa8e4/ffffff.svg?text=TwAina&font=Lato">
            </div>
            <div class="flex max-w-96 bg-white rounded-lg p-3 gap-3">
                <div class="text-gray-700"
                     v-html="toHtml(message.body)"
                ></div>
            </div>
        </div>
        <!-- Outgoing Message -->
        <div
            v-if="message.role==='user'"
            class="flex justify-end mb-4 cursor-pointer"
        >
            <div class="flex max-w-96 bg-indigo-500 text-white rounded-lg p-3 gap-3">
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

</style>
