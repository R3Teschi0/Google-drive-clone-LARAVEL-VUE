<template>
    <div v-if="form.progress" class="absolute border-gray-9000 border right-8 bottom-4 bg-white shadow-lg rounded flex flex-col w-[240px] p-2">
        <div>Uploading {{ form.files.length }} files</div>
        <div class="h-3 bg-gray-100 rounded-md">
            <div class="h-full bg-gray-900 rounded-md transition-all" :stryle="{width: form.progress.percentage+'%'}">

            </div>
        </div>
    </div>
</template>

<script setup>

    //imports

    //Props & emit
    const props = defineProps({
        form: Object
    })

</script>
