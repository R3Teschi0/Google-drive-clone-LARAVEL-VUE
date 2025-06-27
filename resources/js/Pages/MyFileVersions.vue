<script setup>
//imports
import DeleteFilesButton from "@/Components/app/DeleteFilesButton.vue";
import DownloadFilesButton from "@/Components/app/DownloadFilesButton.vue";
import Checkbox from "@/Components/Checkbox.vue";
import FileIcon from "@/Components/FileIcon.vue";
import { httpGet, httpPost } from "@/Helper/http-helper";
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import { Link, router, useForm, usePage } from "@inertiajs/vue3";
import { computed, onMounted, onUpdated, ref } from "vue";
import { emitter, ON_SEARCH, showSuccessNotification } from "@/event-bus";
import ShareFilesButton from "@/Components/app/ShareFilesButton.vue";
import { useShiftPressed } from "@/Composables/useShiftPressed";
import ViewVersions from "@/Components/app/ViewVersions.vue";
import TurnBack from "@/Components/app/TurnBack.vue";
import RestoreVersion from "@/Components/app/RestoreVersion.vue";
import ModifyFile from "@/Components/app/ModifyFile.vue";
import DownloadVersionsButton from "@/Components/app/DownloadVersionsButton.vue";

//Props & Emit
const props = defineProps({
    files: Object,
    file_path: String,
    file: Object,
    parent: Object,
});

const selectedIds = computed(() =>
    Object.entries(selected.value)
        .filter((a) => a[1])
        .map((a) => a[0])
);

let params = "";

//methods

function loadMore() {
    if (allFiles.value.next === null) {
        return;
    }

    httpGet(allFiles.value.next).then((res) => {
        allFiles.value.data = [...allFiles.value.data, ...res.data];
        allFiles.value.next = res.links.next;
    });
}

function onSelectAllChange() {
    allFiles.value.data.forEach((f) => {
        selected.value[f.id] = allSelected.value;
    });
}

function toggleFileSelected(file) {
    if (lastId.value && isShiftPressed.value) {
        const lastIndex = allFiles.value.data
            .map((f) => f.id)
            .findIndex((id) => id === lastId.value);
        const currIndex = allFiles.value.data
            .map((f) => f.id)
            .findIndex((id) => id === file.id);
        const start = Math.min(lastIndex, currIndex);
        const end = Math.max(lastIndex, currIndex);
        for (let i = start; i < end; i++) {
            selected.value[allFiles.value.data[i].id] = true;
        }
    }
    if (!selected.value[file.id] && !isShiftPressed.value) {
        lastId.value = file.id;
    }
    selected.value[file.id] = !selected.value[file.id];
    onSelectCheckboxChange(file);
}

function onSelectCheckboxChange(file) {
    if (!selected.value[file.id]) {
        allSelected.value = false;
    } else {
        let checked = true;

        for (let file of allFiles.value.data) {
            if (!selected.value[file.id]) {
                checked = false;
                break;
            }
        }

        allSelected.value = checked;
    }
}

function onDelete() {
    allSelected.value = false;
    selected.value = {};
}

//Hooks
onUpdated(() => {
    allFiles.value = {
        data: props.files.data,
        next: props.files.links.next,
    };
});

onMounted(() => {
    params = new URLSearchParams(window.location.search);
    onlyFavourites.value = params.get("favourites") === "1";
    search.value = params.get("search");
    emitter.on(ON_SEARCH, (value) => {
        search.value = value;
    });

    const observer = new IntersectionObserver(
        (entries) =>
            entries.forEach((entry) => entry.isIntersecting && loadMore()),
        {
            rootMargin: "-250px 0px 0px 0px",
        }
    );

    observer.observe(loadMoreIntersect.value);
});

//refs
const page = usePage();
const onlyFavourites = ref(false);
const allSelected = ref(false);
const selected = ref({});
const loadMoreIntersect = ref(null);
const allFiles = ref({
    data: props.files.data,
    next: props.files.links.next,
});
const search = ref("");
const { isShiftPressed } = useShiftPressed();
const lastId = ref(null);
</script>

<template>
    <AuthenticatedLayout>
        <nav class="flex items-center justify-between p-1 mb-3">
            <div>
                <TurnBack :path="file_path" />
            </div>
            <div>
                <ModifyFile :file="file"
                :parent="parent" />
                <DownloadVersionsButton
                    :all="allSelected"
                    :ids="selectedIds"
                    :file_base_id="file.file_id"
                    class="mr-2 ml-2"
                />
            </div>
        </nav>
        <div class="flex-1 overflow-auto">
            <table class="min-w-full">
                <thead class="bg-gray-100 border-b">
                    <tr>
                        <th
                            class="text-sm font-medium text-gray-900 px-6 py-2 text-left w-[30px] max-w-[30px] pr-0"
                        >
                            <Checkbox
                                @change="onSelectAllChange"
                                v-model:checked="allSelected"
                            />
                        </th>
                        <th></th>
                        <th
                            class="text-sm font-medium text-gray-900 px-6 py-2 text-left"
                        >
                            Name
                        </th>
                        <th
                            class="text-sm font-medium text-gray-900 px-6 py-2 text-left"
                        >
                            Version
                        </th>
                        <th
                            class="text-sm font-medium text-gray-900 px-6 py-0 text-left"
                        >
                            Last modified
                        </th>
                        <th
                            class="text-sm font-medium text-gray-900 px-6 py-0 text-left"
                        >
                            Size
                        </th>
                    </tr>
                </thead>
                <tbody>
                    <tr
                        v-for="file of allFiles.data"
                        :key="file.id"
                        @click="($event) => toggleFileSelected(file)"
                        @dblclick="openFolder(file)"
                        class="border-b transition duration-300 ease-in-out hover:bg-blue-100 cursor-pointer"
                        :class="
                            selected[file.id] || allSelected
                                ? 'bg-blue-50'
                                : 'bg-white'
                        "
                    >
                        <td
                            class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 w-[30px] max-w-[30px] pr-0"
                        >
                            <Checkbox
                                v-model="selected[file.id]"
                                :checked="selected[file.id] || allSelected"
                            />
                        </td>
                        <td
                            class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 items-center"
                        >
                            <RestoreVersion :file="file" />
                        </td>
                        <td
                            class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 flex items-center"
                        >
                            <FileIcon :file="file" />
                            {{ file.original_name }}
                        </td>
                        <td
                            class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 items-center"
                        >
                            {{ file.version }}
                        </td>
                        <td
                            class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900"
                        >
                            {{ file.updated_at }}
                        </td>
                        <td
                            class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900"
                        >
                            {{ file.size }}
                        </td>
                    </tr>
                </tbody>
            </table>
            <div
                v-if="!allFiles.data.length"
                class="py-8 text-center text-sm text-gray-400"
            >
                There is no data in this folder
            </div>
            <div ref="loadMoreIntersect"></div>
        </div>
    </AuthenticatedLayout>
</template>
