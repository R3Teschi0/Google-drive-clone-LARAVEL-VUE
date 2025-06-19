<template>
    <form class="w-[600px] h-[80px] flex items-center ">
        <TextInput
            type="text"
            class="block w-full mr-2"
            v-model="search"
            autocomplete
            @keydown.enter.prevent = "onSearch"
            placeholder="Search for files and folders"
        />
    </form>
</template>

<script setup>
import { router, useForm } from '@inertiajs/vue3';
import TextInput from '../TextInput.vue';
import { onMounted, ref } from 'vue';
import { ON_SEARCH } from '@/event-bus';


//imports

//uses
let params = ''
//methods
function onSearch(){
    const params = new URLSearchParams(window.location.search)
    params.set('search', search.value)
    router.get(window.location.pathname+'?'+params.toString())

    emitter.emit(ON_SEARCH, search.value)
}

//onMounted
onMounted(() => {
    params = new URLSearchParams(window.location.search)
    search.value = params.get('search')
})

//refs
const search = ref('');


</script>

<style scoped>

</style>
