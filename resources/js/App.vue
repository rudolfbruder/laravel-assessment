<template>
  <div class="min-h-full">
    <!-- Navigation bar (only when authenticated) -->
    <nav v-if="isAuthenticated" class="bg-white border-b border-gray-200 shadow-sm">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between h-16">
          <!-- Brand + nav links -->
          <div class="flex items-center space-x-8">
            <router-link to="/" class="text-xl font-bold text-primary-600 tracking-tight">
              TaskManager
            </router-link>
            <router-link
              to="/"
              class="inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium transition-colors"
              :class="$route.path === '/' ? 'border-primary-500 text-gray-900' : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700'"
            >
              Tasks
            </router-link>
            <router-link
              to="/tasks/create"
              class="inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium transition-colors"
              :class="$route.path === '/tasks/create' ? 'border-primary-500 text-gray-900' : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700'"
            >
              New Task
            </router-link>
          </div>

          <!-- User info + notifications + logout -->
          <div class="flex items-center space-x-4">
            <!-- Notification bell -->
            <div class="relative">
              <button
                @click="toggleDropdown"
                class="relative p-2 text-gray-500 hover:text-gray-700 rounded-full hover:bg-gray-100 transition-colors"
                aria-label="Notifications"
              >
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M14.857 17.082a23.8 23.8 0 0 0 5.454-1.31A8.97 8.97 0 0 1 18 9.75V9A6 6 0 0 0 6 9v.75a8.97 8.97 0 0 1-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m6.714 0a24.2 24.2 0 0 1-6.714 0m6.714 0a3 3 0 1 1-6.714 0" />
                </svg>
                <span
                  v-if="unreadCount > 0"
                  class="absolute -top-0.5 -right-0.5 inline-flex items-center justify-center px-1.5 py-0.5 text-[10px] font-bold leading-none text-white bg-red-600 rounded-full"
                >
                  {{ unreadCount > 9 ? '9+' : unreadCount }}
                </span>
              </button>

              <!-- Dropdown -->
              <div
                v-if="dropdownOpen"
                class="absolute right-0 mt-2 w-80 bg-white rounded-lg shadow-lg ring-1 ring-black/5 z-20 overflow-hidden"
              >
                <div class="flex items-center justify-between px-4 py-2 border-b border-gray-100">
                  <span class="text-sm font-semibold text-gray-800">Notifications</span>
                  <button
                    v-if="unreadCount > 0"
                    @click="markAllRead"
                    class="text-xs text-primary-600 hover:text-primary-800 font-medium"
                  >
                    Mark all read
                  </button>
                </div>
                <div v-if="items.length === 0" class="px-4 py-6 text-center text-sm text-gray-400">
                  No notifications.
                </div>
                <ul v-else class="max-h-96 overflow-y-auto divide-y divide-gray-100">
                  <li
                    v-for="n in items"
                    :key="n.id"
                    @click="openNotification(n)"
                    class="px-4 py-3 hover:bg-gray-50 cursor-pointer"
                    :class="n.read_at ? 'opacity-60' : ''"
                  >
                    <p class="text-sm text-gray-800">
                      <span class="font-medium">{{ n.actor_name }}</span> commented on
                      <span class="font-medium">{{ n.task_name }}</span>
                    </p>
                    <p class="text-xs text-gray-500 mt-0.5 truncate">{{ n.excerpt }}</p>
                  </li>
                </ul>
              </div>
            </div>

            <span class="text-sm font-medium text-gray-700">{{ user?.name }}</span>
            <button
              @click="handleLogout"
              class="text-sm text-gray-500 hover:text-red-600 font-medium transition-colors px-3 py-1 rounded-md hover:bg-red-50"
            >
              Logout
            </button>
          </div>
        </div>
      </div>
    </nav>

    <!-- Guest nav (login/register links) -->
    <nav v-else class="bg-white border-b border-gray-200 shadow-sm">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between h-16 items-center">
          <span class="text-xl font-bold text-primary-600 tracking-tight">TaskManager</span>
          <div class="flex items-center space-x-4">
            <router-link
              to="/login"
              class="text-sm font-medium text-gray-600 hover:text-primary-600 transition-colors"
            >
              Login
            </router-link>
            <router-link
              to="/register"
              class="text-sm font-medium text-white bg-primary-600 hover:bg-primary-700 px-4 py-2 rounded-md transition-colors"
            >
              Register
            </router-link>
          </div>
        </div>
      </div>
    </nav>

    <!-- Main content -->
    <main>
      <div class="max-w-7xl mx-auto py-8 px-4 sm:px-6 lg:px-8">
        <router-view />
      </div>
    </main>

    <!-- Toasts -->
    <div class="fixed top-4 right-4 z-50 space-y-2 w-80">
      <div
        v-for="t in toasts"
        :key="t.id"
        @click="openNotification(t.payload, true)"
        class="bg-white shadow-lg ring-1 ring-black/5 rounded-lg p-4 cursor-pointer hover:bg-gray-50 transition-colors"
      >
        <p class="text-sm text-gray-800">
          <span class="font-medium">{{ t.payload.actor_name }}</span> commented on
          <span class="font-medium">{{ t.payload.task_name }}</span>
        </p>
        <p class="text-xs text-gray-500 mt-0.5 truncate">{{ t.payload.excerpt }}</p>
      </div>
    </div>
  </div>
</template>

<script setup>
import { onMounted, ref, watch } from 'vue';
import { useRouter } from 'vue-router';
import { useEcho } from '@laravel/echo-vue';
import { useAuth } from './composables/useAuth.js';
import { useNotifications } from './composables/useNotifications.js';

const router = useRouter();
const { isAuthenticated, user, logout } = useAuth();
const { items, unreadCount, fetchNotifications, markRead, markAllRead } = useNotifications();

const dropdownOpen = ref(false);
const toasts = ref([]);
let toastSeq = 0;

const toggleDropdown = () => {
  dropdownOpen.value = !dropdownOpen.value;
};

const openNotification = (payload, fromToast = false) => {
  if (!fromToast && payload.id) {
    markRead(payload.id);
  }
  dropdownOpen.value = false;
  if (payload.task_id) {
    router.push({ name: 'TaskShow', params: { id: payload.task_id } });
  }
};

const pushToast = (payload) => {
  const id = ++toastSeq;
  toasts.value.push({ id, payload });
  setTimeout(() => {
    toasts.value = toasts.value.filter((t) => t.id !== id);
  }, 6000);
};

// Subscribe to the shared notifications channel. Ignore our own actions.
useEcho(
  'comments.notifications',
  '.comment.notification',
  (payload) => {
    if (payload.actor_id === user.value?.id) return;
    pushToast(payload);
    fetchNotifications();
  },
  [],
  'private',
);

const loadIfAuthenticated = () => {
  if (isAuthenticated.value) {
    fetchNotifications();
  }
};

watch(isAuthenticated, loadIfAuthenticated);
onMounted(loadIfAuthenticated);

const handleLogout = async () => {
  await logout();
  router.push({ name: 'Login' });
};
</script>
