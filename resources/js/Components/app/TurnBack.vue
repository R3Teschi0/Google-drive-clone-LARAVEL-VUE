<template>
    <button
        @click="onClick"
        class="inline-flex items-center px-4 py-2 text-sm font-medium text-gray-900 rounded-lg hover:text-blue-700 focus:z-10 mr-3"
    >
        <svg
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
                d="M9 15 3 9m0 0 6-6M3 9h12a6 6 0 0 1 0 12h-3"
            />
        </svg>
    </button>
</template>

<script setup>
//|---IMPORTS---|\\
import { router } from "@inertiajs/vue3";

//|---METHODS---|\\
function onClick() {
    var path = props.path;
    if (path === "shared-with-me") {
        router.visit(route("file.sharedWithMe"));
    } else {
        path = path.substring(0, path.lastIndexOf("/"));
        router.visit(route("myFiles", { folder: path }));
    }
}

const props = defineProps({
    path: {
        type: String,
        required: false,
        default: "",
    },
});
</script>
