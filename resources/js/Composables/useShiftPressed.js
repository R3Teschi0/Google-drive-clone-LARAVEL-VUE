import { ref, onMounted, onUnmounted } from 'vue'

const isShiftPressed = ref(false)

export function useShiftPressed() {
  const downHandler = (e) => {
    if (e.key === 'Shift') isShiftPressed.value = true
  }

  const upHandler = (e) => {
    if (e.key === 'Shift') isShiftPressed.value = false
  }

  onMounted(() => {
    window.addEventListener('keydown', downHandler)
    window.addEventListener('keyup', upHandler)
  })

  onUnmounted(() => {
    window.removeEventListener('keydown', downHandler)
    window.removeEventListener('keyup', upHandler)
  })

  return {
    isShiftPressed,
  }
}
