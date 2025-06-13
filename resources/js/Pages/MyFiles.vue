<script setup>
    //imports
    import FileIcon from '@/Components/FileIcon.vue';
    import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
    import {Link, router} from '@inertiajs/vue3'
    import { onMounted, onUpdated, ref } from 'vue';

    //Props & Emit
    const props = defineProps({
        files: Object,
        folder: Object,
        ancestors: Array
    })

    //methods
    function openFolder(file){
        if(!file.is_folder){
            return;
        }

        router.visit(route('myFiles', {folder: file.path}))
    }

    function loadMore(){
        console.log("load more");
        console.log(allFiles.value.next)
    }

    //Hooks
    onUpdated(() => {
        allFiles.value = {
            data: props.files.data,
            next: props.files.links.next
        }
    })
    onMounted( () =>{
        const observer = new IntersectionObserver((entries) => entries.forEach(entry => entry.isIntersecting && loadMore() ),{
            rootMargin: '-250px 0px 0px 0px'
        })

        observer.observe(loadMoreIntersect.value)
    })

    //refs
    const loadMoreIntersect = ref(null);
    const allFiles = ref({
        data: [],
        next: null
    })

</script>

<template>
    <AuthenticatedLayout>
        <nav class="flex items-center justify-between p-1 mb-3">
            <ol class="inline-flex items-center space-x-1 md:space-x-3">
                <li v-for="ans of ancestors" :key="ans.id" class="inline-flex items-center">
                    <Link v-if="!ans.parent_id" :href="route('myFiles')"
                          class="inline-flex items-center text-sm font-medium text-gray-700 hover:text-blue-600 dark:text-gray-800 dark:hover:text-white">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="size-6 mr-3">
                            <path d="M11.47 3.841a.75.75 0 0 1 1.06 0l8.69 8.69a.75.75 0 1 0 1.06-1.061l-8.689-8.69a2.25 2.25 0 0 0-3.182 0l-8.69 8.69a.75.75 0 1 0 1.061 1.06l8.69-8.689Z" />
                            <path d="m12 5.432 8.159 8.159c.03.03.06.058.091.086v6.198c0 1.035-.84 1.875-1.875 1.875H15a.75.75 0 0 1-.75-.75v-4.5a.75.75 0 0 0-.75-.75h-3a.75.75 0 0 0-.75.75V21a.75.75 0 0 1-.75.75H5.625a1.875 1.875 0 0 1-1.875-1.875v-6.198a2.29 2.29 0 0 0 .091-.086L12 5.432Z" />
                        </svg>
                        My Files
                    </Link>
                    <div v-else class="flex items-center">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-6">
                            <path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5" />
                        </svg>

                        <Link :href="route('myFiles', {folder: ans.path})"
                                class="ml-1 text-sm font-medium text-gray-700 hover:text-blue-600 md:ml-2 dark:text-gray-400 dark:hover:text-white">
                                {{ ans.name }}
                        </Link>
                    </div>
                </li>
            </ol>
        </nav>
        <div class="flex-1 overflow-auto">
            <table class="min-w-full">
                <thead class="bg-gray-100 border-b">
                    <tr>
                        <th class="text-sm font-medium text-gray-900 px-6 py-2 text-left">
                            Name
                        </th>
                        <th class="text-sm font-medium text-gray-900 px-6 py-0 text-left">
                            Owner
                        </th>
                        <th class="text-sm font-medium text-gray-900 px-6 py-0 text-left">
                            Last modified
                        </th>
                        <th class="text-sm font-medium text-gray-900 px-6 py-0 text-left">
                            Size
                        </th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="file of files.data" :key="file.id"
                        @dblclick="openFolder(file)"
                        class="bg-white border-b transition duration-300 ease-in-out hover:bg-gray-100 cursor-pointer">
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 flex items-center">
                            <FileIcon :file="file" />
                            {{ file.name }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                            {{ file.owner }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                            {{ file.updated_at }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                            {{ file.size }}
                        </td>
                    </tr>
                </tbody>
            </table>
            <div v-if="!files.data.length" class="py-8 text-center text-sm text-gray-400">
                 There is no data in this folder
            </div>
            <div ref="loaMoreIntersect">

            </div>
        </div>
    </AuthenticatedLayout>
</template>

