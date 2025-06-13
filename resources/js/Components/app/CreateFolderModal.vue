<script setup>

//imports
import { useForm, usePage } from '@inertiajs/vue3';
import InputLabel from '../InputLabel.vue';
import InputError from '../InputError.vue';
import Modal from '../Modal.vue';
import TextInput from '../TextInput.vue';
import SecondaryButton from '../SecondaryButton.vue';
import PrimaryButton from '../PrimaryButton.vue';
import { nextTick, ref} from 'vue';

//Props & Emit
const {modelValue} = defineProps({
    modelValue: Boolean
})

const emit = defineEmits(['update:modelValue'])
const page = usePage();

//methods
function createFolder() {
    form.parent_id = page.props.folder?.data?.id ?? page.props.folder?.id;
    form.post(route('folder.create'), {
        preserveScroll: true,
        onSuccess: () => {
            closeModal()
            // Show success notification
            form.reset();
        },
        onError: (errors) => {
            console.error(errors);  // Verifica quali errori vengono restituiti
            folderNameInput.value.focus();  // Per posizionare il cursore se c'è un errore
        }
    })
}

function closeModal(){
    emit('update:modelValue', false)
    form.clearErrors()
    form.reset()
}

function onShow() {
  nextTick(() => folderNameInput.value.focus())
}

//Uses

const form = useForm({
    name: '',
    parent_id: null
})

//refs
const folderNameInput = ref(null)

</script>

<template>
    <modal :show="modelValue" @show="onShow" max-width="sm">
        <div class="p-6">
            <h2 class="text-lg font-medium text-gray-900">
                Create New Folder
            </h2>
            <div class="mt-6">
                <InputLabel for="folderName"
                            value="Folder Name"
                            class="sr-only"
                />a
                <TextInput  type="text"
                            ref="folderNameInput"
                            id="folderName" v-model="form.name"
                            class="mt-1 block w-full"
                            :class="form.errors.name ? 'border-red-500 focus:border-red-500 focus:ring-red-500' : ''"
                            placeholder="Folder Name"
                            @keyup.enter="createFolder"
                />
                <InputError :message="form.errors.name" class="mt-2" />
            </div>
            <div class="mt-6 flex justify-end">
                <SecondaryButton @click="closeModal">Cancel</SecondaryButton>
                <PrimaryButton class="ml-3" :class="{ 'opacity-25': form.processing }" @click="createFolder" :disabled="form.processing">Submit</PrimaryButton>
            </div>
        </div>
    </modal>
</template>
