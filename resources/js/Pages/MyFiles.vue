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
import ModifyFile from "@/Components/app/ModifyFile.vue";

//Props & Emit
const props = defineProps({
    files: Object,
    folder: Object,
    ancestors: Object,
});

let params = null;

//computed
const selectedIds = computed(
    () =>
        Object.entries(selected.value)
            .filter((a) => a[1])
            .map((a) => a[0]) // qui sono file_id
);

//methods
function openFolder(file) {
    if (!file.is_folder) {
        return;
    }

    router.visit(route("myFiles", { folder: file.path }));
}

function showOnlyFavourites() {
    if (onlyFavourites.value) {
        params.set("favourites", 1);
    } else {
        params.delete("favourites");
    }
    router.get(window.location.pathname + "?" + params.toString());
}

function addRemoveFavourite(file) {
    httpPost(route("file.addToFavourites"), { id: file.file_id })
        .then(() => {
            file.is_favourite = !file.is_favourite;
            showSuccessNotification(
                "Selected files have been added to favourites"
            );
        })
        .catch(async (er) => {});
}

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
        selected.value[f.file_id] = allSelected.value;
    });
}

function toggleFileSelected(file) {
    if (lastId.value && isShiftPressed.value) {
        const lastIndex = allFiles.value.data
            .map((f) => f.file_id)
            .findIndex((id) => id === lastId.value);
        const currIndex = allFiles.value.data
            .map((f) => f.file_id)
            .findIndex((id) => id === file.file_id);
        const start = Math.min(lastIndex, currIndex);
        const end = Math.max(lastIndex, currIndex);
        for (let i = start; i < end; i++) {
            selected.value[allFiles.value.data[i].file_id] = true;
        }
    }
    if (!selected.value[file.file_id] && !isShiftPressed.value) {
        lastId.value = file.file_id;
    }
    selected.value[file.file_id] = !selected.value[file.file_id];
    onSelectCheckboxChange(file);
}

function onSelectCheckboxChange(file) {
    if (!selected.value[file.file_id]) {
        allSelected.value = false;
    } else {
        let checked = true;

        for (let file of allFiles.value.data) {
            if (!selected.value[file.file_id]) {
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
const parent_id = page.props.folder?.data?.id ?? page.props.folder?.id;
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
            <ol class="inline-flex items-center space-x-1 md:space-x-3">
                <li
                    v-for="ans of ancestors"
                    :key="ans.id"
                    class="inline-flex items-center"
                >
                    <Link
                        v-if="!ans.parent_id"
                        :href="route('myFiles')"
                        class="inline-flex items-center text-sm font-medium text-gray-700 hover:text-blue-600 dark:text-gray-800 dark:hover:text-white"
                    >
                        <svg
                            xmlns="http://www.w3.org/2000/svg"
                            viewBox="0 0 24 24"
                            fill="currentColor"
                            class="size-6 mr-3"
                        >
                            <path
                                d="M11.47 3.841a.75.75 0 0 1 1.06 0l8.69 8.69a.75.75 0 1 0 1.06-1.061l-8.689-8.69a2.25 2.25 0 0 0-3.182 0l-8.69 8.69a.75.75 0 1 0 1.061 1.06l8.69-8.689Z"
                            />
                            <path
                                d="m12 5.432 8.159 8.159c.03.03.06.058.091.086v6.198c0 1.035-.84 1.875-1.875 1.875H15a.75.75 0 0 1-.75-.75v-4.5a.75.75 0 0 0-.75-.75h-3a.75.75 0 0 0-.75.75V21a.75.75 0 0 1-.75.75H5.625a1.875 1.875 0 0 1-1.875-1.875v-6.198a2.29 2.29 0 0 0 .091-.086L12 5.432Z"
                            />
                        </svg>
                        My Files
                    </Link>
                    <div v-else class="flex items-center">
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
                                d="m8.25 4.5 7.5 7.5-7.5 7.5"
                            />
                        </svg>

                        <Link
                            :href="route('myFiles', { folder: ans.path })"
                            class="ml-1 text-sm font-medium text-gray-700 hover:text-blue-600 md:ml-2 dark:text-gray-400 dark:hover:text-white"
                        >
                            {{ ans.name }}
                        </Link>
                    </div>
                </li>
            </ol>
            <div class="flex">
                <label class="flex items-center mr-3">
                    Only favourites
                    <Checkbox
                        class="ml-2"
                        @change="showOnlyFavourites"
                        v-model:checked="onlyFavourites"
                    />
                </label>
                <ShareFilesButton
                    :all-selected="allSelected"
                    :selected-ids="selectedIds"
                />
                <DownloadFilesButton
                    :all="allSelected"
                    :ids="selectedIds"
                    :parent_id="parent_id"
                    class="mr-2 ml-2"
                />
                <DeleteFilesButton
                    :delete-all="allSelected"
                    :delete-ids="selectedIds"
                    :parent_id="parent_id"
                    @delete="onDelete"
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
                        <th
                            class="text-sm font-medium text-gray-900 px-6 py-2 text-left"
                        ></th>
                        <th
                            class="text-sm font-medium text-gray-900 px-6 py-2 text-left"
                        ></th>
                        <th
                            class="text-sm font-medium text-gray-900 px-6 py-2 text-left"
                        >
                            Name
                        </th>
                        <th
                            v-if="search"
                            class="text-sm font-medium text-gray-900 px-6 py-2 text-left"
                        >
                            Path
                        </th>
                        <th
                            class="text-sm font-medium text-gray-900 px-6 py-0 text-left"
                        >
                            Owner
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
                            selected[file.file_id] || allSelected
                                ? 'bg-blue-50'
                                : 'bg-white'
                        "
                    >
                        <td
                            class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 w-[30px] max-w-[30px] pr-0"
                        >
                            <Checkbox
                                v-model="selected[file.file_id]"
                                :checked="selected[file.file_id] || allSelected"
                            />
                        </td>
                        <td
                            class="px-6 py-4 max-w-[40px] text-sm font-medium text-yellow-500"
                        >
                            <div @click.stop.prevent="addRemoveFavourite(file)">
                                <svg
                                    v-if="!file.is_favourite"
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
                                        d="M11.48 3.499a.562.562 0 0 1 1.04 0l2.125 5.111a.563.563 0 0 0 .475.345l5.518.442c.499.04.701.663.321.988l-4.204 3.602a.563.563 0 0 0-.182.557l1.285 5.385a.562.562 0 0 1-.84.61l-4.725-2.885a.562.562 0 0 0-.586 0L6.982 20.54a.562.562 0 0 1-.84-.61l1.285-5.386a.562.562 0 0 0-.182-.557l-4.204-3.602a.562.562 0 0 1 .321-.988l5.518-.442a.563.563 0 0 0 .475-.345L11.48 3.5Z"
                                    />
                                </svg>
                                <svg
                                    v-else
                                    xmlns="http://www.w3.org/2000/svg"
                                    viewBox="0 0 24 24"
                                    fill="currentColor"
                                    class="size-6"
                                >
                                    <path
                                        fill-rule="evenodd"
                                        d="M10.788 3.21c.448-1.077 1.976-1.077 2.424 0l2.082 5.006 5.404.434c1.164.093 1.636 1.545.749 2.305l-4.117 3.527 1.257 5.273c.271 1.136-.964 2.033-1.96 1.425L12 18.354 7.373 21.18c-.996.608-2.231-.29-1.96-1.425l1.257-5.273-4.117-3.527c-.887-.76-.415-2.212.749-2.305l5.404-.434 2.082-5.005Z"
                                        clip-rule="evenodd"
                                    />
                                </svg>
                            </div>
                        </td>
                        <td
                            class="items-center px-4 py-2 max-w-[40px] first-line:text-sm font-medium text-gray-900 hover:text-blue-700 focus:z-10 mr-3"
                        >
                            <div>
                                <ViewVersions
                                    :is_folder="file.is_folder"
                                    :file="file"
                                    :folder="folder"
                                />
                            </div>
                        </td>
                        <td
                            class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 flex items-center"
                        >
                            <FileIcon :file="file" />
                            {{ file.original_name }}
                        </td>
                        <td
                            v-if="search"
                            class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900"
                        >
                            {{ file.path }}
                        </td>
                        <td
                            class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900"
                        >
                            {{ file.owner }}
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
