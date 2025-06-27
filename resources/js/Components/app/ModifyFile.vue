<template>
    <PrimaryButton @click="triggerFileSelect" class="bg-[#5E5D6D] hover:bg-[#777689] text-white">
        <input @change="onChange" ref="fileInput" type="file" class="hidden" />
        <svg
            v-if="!is_folder"
            @click="triggerFileSelect"
            xmlns="http://www.w3.org/2000/svg"
            fill="none"
            viewBox="0 0 24 24"
            stroke-width="1.5"
            stroke="currentColor"
            class="w-4 h-4 mr-2"
        >
            <path
                stroke-linecap="round"
                stroke-linejoin="round"
                d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0 1 15.75 21H5.25A2.25 2.25 0 0 1 3 18.75V8.25A2.25 2.25 0 0 1 5.25 6H10"
            />
        </svg>
        Modify
    </PrimaryButton>
</template>

<script setup>
import { showErrorDialog, showSuccessNotification } from "@/event-bus";
import { useForm } from "@inertiajs/vue3";
import { ref } from "vue";
import PrimaryButton from "../PrimaryButton.vue";

//|---METHODS---|\\
function onChange(ev) {

    const filePicked = ev.target.files[0];
    fileUploadForm.parent_id = props.parent.id;
    fileUploadForm.files = filePicked;
    fileUploadForm.fileModifiedId = props.file.file_id;

    fileUploadForm.post(route("file.modify"), {
        onSuccess: () => {
            showSuccessNotification(`${fileUploadForm.files.name} has been uploaded`);
        },
        onError: (errors) => {
            let message = "";

            if (Object.keys(errors).length > 0) {
                message = errors[Object.keys(errors)[0]];
            } else {
                message = "Error during file upload. Please try again later.";
            }

            showErrorDialog(message);
        },
        onFinish: () => {
            fileUploadForm.clearErrors();
            fileUploadForm.reset();
        },
    });
}

function triggerFileSelect() {
    fileInput.value?.click();
}

//|---PROPS---|\\
const props = defineProps({
    is_folder: Boolean,
    file: Object,
    parent: Object,
});

//|---CONSTS---|\\
const form = useForm({
    Files: Object,
});

const fileUploadForm = useForm({
    files: [],
    parent_id: null,
    fileModifiedId: null,
});

const fileInput = ref(null);
</script>
