import { ref } from 'vue';
import api from './useApi.js';

// Shared singleton state for the authenticated user's notifications.
const items = ref([]);
const unreadCount = ref(0);

const fetchNotifications = async () => {
  try {
    const response = await api.get('/notifications');
    items.value = response.data.data;
    unreadCount.value = response.data.unread_count;
  } catch (err) {
    console.error('Failed to fetch notifications:', err);
  }
};

const markRead = async (id) => {
  try {
    await api.post(`/notifications/${id}/read`);
    const item = items.value.find((n) => n.id === id);
    if (item && !item.read_at) {
      item.read_at = new Date().toISOString();
      unreadCount.value = Math.max(0, unreadCount.value - 1);
    }
  } catch (err) {
    console.error('Failed to mark notification read:', err);
  }
};

const markAllRead = async () => {
  try {
    await api.post('/notifications/read-all');
    items.value.forEach((n) => {
      n.read_at = n.read_at ?? new Date().toISOString();
    });
    unreadCount.value = 0;
  } catch (err) {
    console.error('Failed to mark all read:', err);
  }
};

export function useNotifications() {
  return { items, unreadCount, fetchNotifications, markRead, markAllRead };
}
