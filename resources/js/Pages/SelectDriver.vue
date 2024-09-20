<template>
    <Combobox v-model="selectedLlmDriver" as="div" @update:modelValue="query = ''">
        <ComboboxLabel
            class="block text-sm font-medium leading-6 text-gray-900"
        >
            LLM Driver
        </ComboboxLabel>
        <div class="relative mt-2">
            <ComboboxInput
                :display-value="(llm_driver) => llm_driver?.title"
                class="w-full rounded-md border-0 bg-white py-1.5 pl-3 pr-10 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-sm sm:leading-6"
                @blur="query = ''"
                @change="query = $event.target.value"
            />
            <ComboboxButton class="absolute inset-y-0 right-0 flex items-center rounded-r-md px-2 focus:outline-none">
                <ChevronUpDownIcon aria-hidden="true" class="h-5 w-5 text-gray-400"/>
            </ComboboxButton>

            <ComboboxOptions
                v-if="filteredLlmDriver.length > 0"
                class="absolute z-10 mt-1 max-h-60 w-full overflow-auto rounded-md bg-white py-1 text-base shadow-lg ring-1 ring-black ring-opacity-5 focus:outline-none sm:text-sm"
            >
                <ComboboxOption
                    v-for="llm_driver in active_llms"
                    :key="llm_driver.key"
                    v-slot="{ active, selected }"
                    :value="llm_driver"
                    as="template"
                >
                    <li
                        :class="[
                            'relative cursor-default select-none py-2 pl-3 pr-9',
                            active ? 'bg-indigo-600 text-white' : 'text-gray-900'
                        ]"
                    >
                        <span :class="['block truncate', selected && 'font-semibold']">
                            {{ llm_driver.title }}
                        </span>
                        <span
                            v-if="selected"
                            :class="['absolute inset-y-0 right-0 flex items-center pr-4', active ? 'text-white' : 'text-indigo-600']"
                        >
                            <CheckIcon aria-hidden="true" class="h-5 w-5"/>
                        </span>
                    </li>
                </ComboboxOption>
            </ComboboxOptions>
        </div>
    </Combobox>
</template>

<script setup>
import {computed, ref, watch} from 'vue'
import {CheckIcon, ChevronUpDownIcon} from '@heroicons/vue/20/solid'
import {Combobox, ComboboxButton, ComboboxInput, ComboboxLabel, ComboboxOption, ComboboxOptions,} from '@headlessui/vue'

const emit = defineEmits(["update:modelValue"]);
const Props = defineProps({
    modelValue: {
        type: String,
        default: ""
    },
    llm_driver: {
        type: String,
        default: ""
    },
    active_llms: Array
})
const query = ref('')
const selectedLlmDriver = ref(Props.active_llms.find((driver) => {
    return driver.title.toLowerCase().includes(Props.modelValue.toLowerCase())
}));
watch(
    () => selectedLlmDriver.value.key,
    (value) => {
        console.log("value", value);
        emit("update:modelValue", value);
    }
);
const filteredLlmDriver = computed(() =>
    query.value === ''
        ? Props.active_llms
        : Props.active_llms.filter((driver) => {
            return driver.title.toLowerCase().includes(query.value.toLowerCase())
        }),
)
</script>
