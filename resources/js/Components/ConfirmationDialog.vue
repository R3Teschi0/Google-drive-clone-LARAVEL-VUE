<template>
    <Modal :show="show" max-width="md">
        <div class="p-6">
            <h2 class="text-2xl mb-2 text-red-600 font-semibold">
                Please Confirm
            </h2>
            <p>
                {{ message }}
            </p>
            <div class="mt-6 flex justify-end">
                <SecondaryButton @click="$event => emit('cancel')">
                    Cancel
                </SecondaryButton>

                <DangerButton class="ml-3" @click="$event => emit('confirm')">
                    Confirm
                </DangerButton>
            </div>
        </div>
    </Modal>
</template>

<script setup>

//imports
import DangerButton from './DangerButton.vue';
import Modal from './Modal.vue';
import SecondaryButton from './SecondaryButton.vue';

//props
const props = defineProps({
    show: Boolean,
    message: String
})
const emit = defineEmits(['cancel', 'confirm'])

</script>
