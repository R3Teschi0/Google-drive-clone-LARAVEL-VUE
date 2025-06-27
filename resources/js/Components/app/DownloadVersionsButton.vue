<template>
    <PrimaryButton @click="download">
        <svg
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
                d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5M16.5 12L12 16.5m0 0L7.5 12m4.5 4.5V3"
            />
        </svg>
        Download
    </PrimaryButton>
</template>

<script setup>
//imports
import { useForm, usePage } from "@inertiajs/vue3";
import PrimaryButton from "../PrimaryButton.vue";
import { httpGet } from "@/Helper/http-helper";
import { showErrorDialog } from "@/event-bus";

//props & Emit
const props = defineProps({
    all: {
        type: Boolean,
        required: false,
        default: false,
    },
    ids: {
        type: Array,
        required: false,
    },
    file_base_id: Number,
    //sharedWithMe: { type: Boolean, default: false },
    //sharedByMe: { type: Boolean, default: false },
});

//uses
const page = usePage();

const form = useForm({
    all: null,
    ids: [],
    parent_id: null,
});

//methods

function download() {

    if (!props.all && props.ids.length === 0) {
        showErrorDialog("Please select at least one file to download");
        return;
    }

    const p = new URLSearchParams();
    if (props.parent_id) {
        p.append("parent_id", props.parent_id);
    }

    if (props.all) {
        p.append("all", props.all);
        p.append("file_base_id", props.file_base_id);
    } else {
        for (let id of props.ids) {
            p.append("ids[]", id);
        }
        p.append("file_base_id", props.file_base_id);
    }

    let url = route("file.downloadVersion");
    // if (props.sharedWithMe) {
    //     url = route("file.downloadSharedWithMe");
    // } else if (props.sharedByMe) {
    //     url = route("file.downloadSharedByMe");
    // }

    httpGet(url + "?" + p.toString()).then((res) => {
        if (res.file) {
            // Caso file base64
            const binary = window.atob(res.file);
            let blob = null;
            let link = document.createElement("a");

            var byteNumbers = new Array(binary.length);
            for (var i = 0; i < binary.length; i++) {
                byteNumbers[i] = binary.charCodeAt(i);
            }
            var byteArray = new Uint8Array(byteNumbers);

            if (res.extension === "xlsx") {
                blob = new Blob([byteArray], {
                    type: "application/vnd.openxmlformats-officedocument.spreadsheetml.sheet",
                });
            } else if (res.extension === "pdf") {
                blob = new Blob([byteArray], {
                    type: "application/pdf",
                });
            } else if (res.extension === "jpeg" || res.extension === "jpg") {
                blob = new Blob([byteArray], {
                    type: "image/jpeg",
                });
            } else if (res.extension === "png") {
                blob = new Blob([byteArray], {
                    type: "image/png",
                });
            } else if (res.extension === "gif") {
                blob = new Blob([byteArray], {
                    type: "image/gif",
                });
            } else if (res.extension === "bmp") {
                blob = new Blob([byteArray], {
                    type: "image/bmp",
                });
            } else if (res.extension === "svg") {
                blob = new Blob([byteArray], {
                    type: "image/svg+xml",
                });
            } else if (res.extension === "txt") {
                blob = new Blob([byteArray], {
                    type: "text/plain",
                });
            } else if (res.extension === "csv") {
                blob = new Blob([byteArray], {
                    type: "text/csv",
                });
            } else if (res.extension === "json") {
                blob = new Blob([byteArray], {
                    type: "application/json",
                });
            } else if (res.extension === "mp3") {
                blob = new Blob([byteArray], {
                    type: "audio/mpeg",
                });
            } else if (res.extension === "mp4") {
                blob = new Blob([byteArray], {
                    type: "video/mp4",
                });
            } else {
                blob = new Blob([byteArray]);
            }

            const blobUrl = URL.createObjectURL(blob);
            link.href = blobUrl;
            link.download = res.filename;

            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
        } else if (res.url) {
            // Caso URL (zip o cartella)
            const a = document.createElement("a");
            a.download = res.filename;
            a.href = res.url;
            a.click();
        }
    });
}

//refs
</script>
