<script setup>
import {onMounted, reactive, ref, watch} from "vue";
import {Link, router} from '@inertiajs/vue3'
import SelectDriver from "@/Pages/SelectDriver.vue";
import ChatMessages from "@/Pages/ChatMessages.vue";

const Props = defineProps({
    chats: Object,
    chat: Object,
    messages: Array
})

let scroller = ref();
const form = reactive({
    input: "",
    chat: Props.chat,
    messages: Props.messages,
    streamed_messages: [],
    show_typing:true
});

watch(
    () => form.chat?.chat_driver,
    (value) => {
        console.log("form.chat", form.chat);
        updateChat(form.chat)
    }
);

watch(
    () => form.chat?.id,
    (value) => {
        stopListening();
    }
);
watch(
    () => form.messages,
    (value) => {
        console.log("form.messages", form.messages);
        scrollMessage()
    },
    {
        deep: true
    }
);

const keyDown = (event) => {
    if (event.key === 'Enter') {
        sendMessage();
    }
}

const deleteChat = (chat) => {
    router.delete(route('guest.delete.chat', {chat: chat.id}))
}

const updateChat = (chat) => {
    router.patch(route('guest.update.chat', {chat: chat.id}), chat)
}

const sendMessage = () => {
    if (form.input !== "") {
        userMessage(form.input);
        scrollMessage();
        router
            .post(
                route(
                    'guest.chats.messages',
                    {
                        chat: Props.chat.id
                    }
                ),
                {
                    input: form.input
                }, {
                    preserveState: true,
                    onBefore: () => {
                        form.input = "";
                        scrollMessage();
                    },
                    onFinish: () => {
                        form.input = "";
                        scrollMessage();
                    }
                }
            )
    }

}
const scrollMessage = () => {
    if (
        typeof scroller != 'undefined'
        && typeof scroller.value != 'undefined'
    ) {
        scroller.value.scroll({top: scroller.value.scrollHeight});
    }
}
const stopListening = () => {
    let channel = 'chat.' + Props.chat.id;
    Echo
        .channel(channel)
        .stopListening('.update');
    Echo
        .leaveChannel(channel);
}
const startListening = () => {
    let channel = 'chat.' + Props.chat.id;
    Echo
        .channel(channel)
        .listen('.update', (notification) => {
            console.log('update', notification);
            updateMessage(notification.message);
            form.show_typing=true;
        })
        .listen('.AiStreaming', (incoming_message) => {
            console.log('AiStreaming', incoming_message);
            form.show_typing=false;
            updateStreamedMessage(incoming_message)
        })
        .listen('.AiWhispering', (event) => {
            console.log('AiWhispering', event);
            form.show_typing=true;
        });
}
const userMessage = (message) => {
    let imessage = {
        id: "User" + window.crypto.randomUUID(),
        body: message,
        role: 'user'
    };
    console.log('UserMessage', imessage);
    form.streamed_messages.push(imessage);
    updateMessage(imessage, true);
}
const updateStreamedMessage = (incoming_message) => {
    let imessage = {
        id: "AiStreaming" + incoming_message.last_message,
        body: incoming_message.stream.message.content,
        role: incoming_message.stream.message.role
    };
    let message = form.streamed_messages.find((message) => message.id === imessage.id);
    if (message) {
        form.streamed_messages = form.streamed_messages.map((message) => {
            if (message.id === imessage.id) {
                message.body += imessage.body;
            }
            return message;
        });
    } else {
        form.streamed_messages.push(imessage);
    }
    updateMessage(form.streamed_messages.find((message) => message.id === imessage.id), true);
}
const updateMessage = (incomingMessage, is_streaming = false) => {
    let message = form.messages.find((message) => message.id === incomingMessage.id);
    if (message) {
        form.messages = form.messages.map((message) => {
            if (message.id === incomingMessage.id) {
                message.body = incomingMessage.body;
            }
            return message;
        });
    } else {
        let push = false;
        if (is_streaming) {
            push = true;
        } else {
            let is_streamed_message = form
                .streamed_messages
                .filter((message) => message.body === incomingMessage.body)
                .length > 0;
            if (!is_streamed_message) {
                push = true;
            }
        }
        if (push) {
            form.messages.push(incomingMessage);
        }
    }
    scrollMessage();
}
onMounted(() => {
    scrollMessage();
    if (Props.chat) {
        startListening()
    }
    setInterval(()=>{
        // scrollMessage();
    },2)
})

</script>
<template>
    <!-- component -->
    <div class="flex h-screen overflow-hidden ">
        <!-- Sidebar -->
        <div class="w-1/4 bg-white border-r border-gray-300">
            <!-- Sidebar Header -->
            <header
                class="p-4 border-b border-gray-300 flex justify-between items-center bg-indigo-600 text-white"
            >
                <h1 class="text-2xl justify-center font-semibold">TwAina</h1>
                <Link :href="route('guest.chats.new')">New Chat</Link>
            </header>
            <!-- Contact List -->
            <div
                class="overflow-y-auto h-screen p-3 mb-9 pb-20"
            >
                <div
                    v-for="chat in chats.data"
                    class="flex items-center mb-4 cursor-pointer overflow-hidden hover:bg-gray-100 p-2 rounded-md"

                >
                    <Link
                        :href="route('guest.chats.index',{chat:chat.id})"
                        class="flex-1 flex"
                    >
                        <div class="w-12 h-12 bg-gray-300 rounded-full mr-3">
                            <img
                                :src="'https://placehold.co/200x/2e83ad/ffffff.svg?text='+chat.chat_driver"
                                alt="User Avatar"
                                class="w-12 h-12 rounded-full"
                            >
                        </div>
                        <div class="flex-2">
                            <h2 class="text-lg font-semibold">
                                {{ chat.title }}
                            </h2>
                            <p
                                v-if="chat.messages.length>0"
                                class="text-gray-600 truncate w-1/2"
                            >
                                {{ chat.messages[0]?.role === 'assistant' ? 'TwAi' : 'User' }}:
                                {{ chat.messages[0]?.body }}
                            </p>
                        </div>
                    </Link>
                </div>
            </div>
        </div>
        <!-- Main Chat Area -->
        <div v-if="chat" class="flex-1">
            <!-- Chat Header -->
            <header class="flex bg-white p-4 text-gray-700 border-b border-gray-300">
                <h1 class="flex-1 text-2xl font-semibold">{{ chat.title }}</h1>
                <div
                    class="flex-2"
                >
                    <SelectDriver v-model="form.chat.chat_driver"/>
                </div>
                <div
                    class="flex-3"
                    v-on:click="deleteChat(chat)"
                >
                    <h2 class="text-lg font-semibold">
                        Delete
                    </h2>
                </div>
            </header>
            <!-- Chat Messages -->
            <div
                ref="scroller"
                class="h-screen justify-end overflow-y-auto p-4 pb-44 scroll-smooth"
            >
                <ChatMessages :messages="form.messages"/>
                <div
                    v-if="form.show_typing"
                    class="flex mb-4 cursor-pointer"
                >
                    <div class="w-10 h-10 rounded-full flex items-center justify-center mr-2">
                        <img alt="User Avatar" class="w-10 h-10 rounded-full"
                             src="https://placehold.co/200x/4f46e5/ffffff.svg?text=TwAina&font=Lato">
                    </div>
                    <div class="max-w-96 bg-white rounded-lg justify-center items-center px-3 gap-0">
                        <div class="animate-typing overflow-hidden text-2xl text-gray-700">...</div>
                    </div>
                </div>
                <div id="anchor"></div>
            </div>
            <!-- Chat Input -->
            <footer class="bg-white border-t border-gray-300 p-4 absolute bottom-0 w-3/4">
                <div class="flex items-center">
                    <input
                        v-model="form.input"
                        class="w-full p-2 text-2xl text-black rounded-md border border-gray-400 focus:outline-none focus:border-blue-500"
                        placeholder="Type a message..."
                        type="text"
                        v-on:keydown="keyDown"
                    >
                    <button
                        class="bg-indigo-500 text-2xl text-white px-4 py-2 rounded-md ml-2"
                        v-on:click="sendMessage()"
                    >
                        Send
                    </button>
                </div>
            </footer>
        </div>
    </div>
</template>
<style>
#scroller * {
    overflow-anchor: none;
}

#anchor {
    overflow-anchor: auto;
    height: 1px;
}
</style>
