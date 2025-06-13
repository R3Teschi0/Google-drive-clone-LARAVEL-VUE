<template>
    <form class="w-[600px] h-[80px] flex items-center ">
        <TextInput
            type="text"
            class="block w-full mr-2"
            v-model="form.search"
            autocomplete
            placeholder="Seacrh for files and folders"
        />
    </form>
</template>

<script setup>
import { useForm } from '@inertiajs/vue3';
import TextInput from '../TextInput.vue';


//imports

//uses
const form = useForm({
    search: ''
})

</script>
