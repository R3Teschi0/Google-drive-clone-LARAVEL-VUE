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
import { BaseTransitionPropsValidators } from "vue";
import { showErrorDialog } from "@/event-bus";

//props & Emit
const props = defineProps({
    all: {
        type: Boolean,
        required: false,
        default: false
    },
    ids: {
        type: Array,
        required: false
    },
    sharedWithMe: false,
    sharedByMe: false,
})

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
    console.log(page.props);
    const p = new URLSearchParams();
    if(page.props.folder?.data?.id ?? page.props.folder?.id){
        p.append('parent_id',page.props.folder?.data?.id ?? page.props.folder?.id)
    }

    if (props.all) {
        p.append("all", props.all);
    } else {
        for (let id of props.ids) {
            p.append("ids[]", id);
        }
    }

    console.log(props.all);
    console.log(props.ids);
    console.log(p.toString());

    let url = route('file.download');
    if (props.sharedWithMe) {
        url = route('file.downloadSharedWithMe')
    } else if (props.sharedByMe) {
        url = route('file.downloadSharedByMe')
    }

    httpGet(url + "?" + p.toString()).then((res) => {
        console.log(res)
        if (!res.url) return;

        const a = document.createElement("a");
        a.download = res.filename;
        a.href = res.url;
        a.click();
    });
}

//refs
</script>
