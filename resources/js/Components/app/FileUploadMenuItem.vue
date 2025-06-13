<script setup>

    //imports
    import { emitter, FILE_UPLOAD_STARTED } from '@/event-bus';
    import { MenuItem } from '@headlessui/vue';

    //methods
    function onChange(ev){
        emitter.emit(FILE_UPLOAD_STARTED, ev.target.files)
    }

</script>

<template>
    <MenuItem v-slot="{active}">
        <a href="#" class="text-gray-700 block px-4 py-2 text-sm relative">
            Upload file
            <input
                @change="onChange"
                type="file"
                multiple
                class="absolute inset-0 left-0 top-0 bottom-0 right-0 cursor-pointer opacity-0">
        </a>
    </MenuItem>
</template>
