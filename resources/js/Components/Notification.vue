<template>
     <transition
        enter-active-class="ease-out duration-300"
        enter-from-class="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
        enter-to-class="opacity-100 translate-y-0 sm:scale-100"
        leave-active-class="ease-in duration-200"
        leave-from-class="opacity-100 translate-y-0 sm:scale-100"
        leave-to-class="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
    >
        <div v-if="show" class="fixed bottom-4 left-4 text-white py-2 px-4 rounded-lg shadow-md w-[200px]"
             :class="{
                'bg-emerald-500': Type === 'success',
                'bg-red-500': Type === 'error'
            }">
            {{ message }}
        </div>
    </transition>
</template>

<script setup>
import { emitter, SHOW_NOTIFICATION } from '@/event-bus';
import { onMounted, ref } from 'vue';


//imports

//Methods
function close(){
    show.value = false;
    Type.value = '';
    message.value = '';
}

//Hooks
onMounted(() => {
    let timeout;
    emitter.on(SHOW_NOTIFICATION, ({Type: t, message: msg}) => {
        show.value = true;
        Type.value = t;
        message.value = msg;

        if(timeout) clearTimeout(timeout);
        timeout = setTimeout(() => {
            close()
        }, 5000)
    })
})

//refs
const show = ref(false);
const Type = ref('success');
const message = ref('');

</script>
