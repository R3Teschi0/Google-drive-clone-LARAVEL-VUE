<template>
    <Modal :show="show" max-width="md">
        <div class="p-6">
            <h2 class="text-2xl mb-2 text-red-600 font-semibold">Error</h2>
            <p> {{ message }} </p>
            <div class="mt-6 flex justify-end">
                <PrimaryButton @click="close">OK</PrimaryButton>
            </div>
        </div>
    </Modal>
</template>

<script setup>

//imports

import { emitter, SHOW_ERROR_DIALOG } from '@/event-bus';
import Modal from './Modal.vue';
import PrimaryButton from './PrimaryButton.vue';
import { onMounted, ref } from 'vue';

//refs
const show = ref(false);
const message = ref('');

//method
function close(){
    show.value = false;
    message.value = ''
}

//Hooks
onMounted(() => {
    emitter.on(SHOW_ERROR_DIALOG, ({message: msg}) => {
        show.value = true;
        message.value = msg;
    })
})

//Props & Emit
const emit = defineEmits(['close'])
</script>
