<template>
    <div @click.stop.prevent="viewSharedVersions(file)">
        <svg
            v-if="!props.is_folder"
            xmlns="http://www.w3.org/2000/svg"
            fill="none"
            viewBox="0 0 24 24"
            stroke-width="1.5"
            stroke="currentColor"
            class="size-6"
        >
            <path
                stroke-linecap="round"
                stroke-linejoin="round"
                d="m11.25 11.25.041-.02a.75.75 0 0 1 1.063.852l-.708 2.836a.75.75 0 0 0 1.063.853l.041-.021M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9-3.75h.008v.008H12V8.25Z"
            />
        </svg>
    </div>
</template>

<script setup>
import { useForm } from "@inertiajs/vue3";

//|---METHODS---|\\
function viewSharedVersions(file) {
    if (!props.is_folder) {
        form.get(route("mySharedVersions", file));
    }
}

//|---PROPS---|\\
const props = defineProps({
    is_folder: Boolean,
    file: Object,
    folder: Object,
    parent_id: Number,
});

//|---FORM---|\\
const form = useForm({
    Files: Object,
});
</script>
