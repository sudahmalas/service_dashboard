import { ref } from 'vue';

const toasts = ref([]);
let nextId = 1;

export function useToast() {
  const showToast = (message, type = 'info', duration = 3000) => {
    const id = nextId++;
    const toast = { id, message, type };
    toasts.value.push(toast);

    if (duration > 0) {
      setTimeout(() => {
        removeToast(id);
      }, duration);
    }
    return id;
  };

  const removeToast = (id) => {
    const idx = toasts.value.findIndex((t) => t.id === id);
    if (idx !== -1) {
      toasts.value.splice(idx, 1);
    }
  };

  const success = (message, duration = 3000) => showToast(message, 'success', duration);
  const error = (message, duration = 4000) => showToast(message, 'error', duration);
  const info = (message, duration = 3000) => showToast(message, 'info', duration);
  const warning = (message, duration = 3500) => showToast(message, 'warning', duration);

  return {
    toasts,
    showToast,
    removeToast,
    success,
    error,
    info,
    warning,
  };
}
