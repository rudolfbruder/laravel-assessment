<template>
  <div>
    <!-- Loading -->
    <div v-if="loading" class="flex justify-center py-16">
      <span class="text-gray-400 text-sm">Loading task…</span>
    </div>

    <!-- Not found -->
    <div v-else-if="!task" class="text-center py-16">
      <p class="text-gray-500 font-medium">Task not found.</p>
      <router-link to="/" class="mt-4 inline-block text-primary-600 hover:text-primary-800 text-sm font-medium">
        Back to tasks
      </router-link>
    </div>

    <!-- Task detail -->
    <div v-else>
      <!-- Breadcrumb -->
      <div class="flex items-center space-x-2 text-sm text-gray-500 mb-4">
        <router-link to="/" class="hover:text-primary-600 transition-colors">Tasks</router-link>
        <span>/</span>
        <span class="text-gray-700 font-medium truncate max-w-xs">{{ task.name }}</span>
      </div>

      <!-- Header -->
      <div class="flex items-start justify-between gap-4 mb-6">
        <div>
          <h1 class="text-2xl font-bold text-gray-900">{{ task.name }}</h1>
          <div class="flex flex-wrap items-center gap-2 mt-2">
            <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-semibold" :class="statusClass(task.status)">
              {{ statusLabel(task.status) }}
            </span>
            <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-semibold" :class="priorityClass(task.priority)">
              {{ task.priority }}
            </span>
            <span v-if="task.due_date" class="text-sm text-gray-500">
              Due {{ formatDate(task.due_date) }}
            </span>
          </div>
        </div>
        <div class="flex items-center space-x-3 flex-shrink-0">
          <router-link
            :to="`/tasks/${task.id}/edit`"
            class="px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 transition-colors"
          >
            Edit
          </router-link>
          <button
            @click="deleteTask"
            class="px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-red-600 hover:bg-red-700 transition-colors"
          >
            Delete
          </button>
        </div>
      </div>

      <!-- Details card -->
      <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 space-y-4">
        <div>
          <h2 class="text-sm font-semibold text-gray-500 uppercase tracking-wide mb-2">Description</h2>
          <p v-if="task.description" class="text-gray-700 whitespace-pre-wrap text-sm leading-relaxed">
            {{ task.description }}
          </p>
          <p v-else class="text-gray-400 text-sm italic">No description provided.</p>
        </div>

        <div class="border-t border-gray-100 pt-4 grid grid-cols-2 sm:grid-cols-3 gap-4 text-sm">
          <div>
            <span class="text-gray-500 font-medium">Created</span>
            <p class="text-gray-800 mt-1">{{ formatDate(task.created_at) }}</p>
          </div>
          <div>
            <span class="text-gray-500 font-medium">Last updated</span>
            <p class="text-gray-800 mt-1">{{ formatDate(task.updated_at) }}</p>
          </div>
          <div v-if="task.due_date">
            <span class="text-gray-500 font-medium">Due date</span>
            <p class="text-gray-800 mt-1" :class="isOverdue ? 'text-red-600 font-semibold' : ''">
              {{ formatDate(task.due_date) }}
            </p>
          </div>
        </div>
      </div>

      <!-- Comments placeholder -->
      <div class="mt-6 bg-white rounded-xl shadow-sm border border-dashed border-gray-300 p-6">
        <h2 class="text-lg font-semibold text-gray-800 mb-2">Comments</h2>
        <p class="text-gray-400 text-sm italic">
          💬 Comments feature is not implemented yet — this is one of the assessment tasks for you to build!
        </p>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import api from '../composables/useApi.js';

const route = useRoute();
const router = useRouter();

const task = ref(null);
const loading = ref(true);

const fetchTask = async () => {
  loading.value = true;
  try {
    const response = await api.get(`/tasks/${route.params.id}`);
    task.value = response.data.data;
  } catch (err) {
    console.error('Failed to fetch task:', err);
    task.value = null;
  } finally {
    loading.value = false;
  }
};

const deleteTask = async () => {
  if (!confirm('Delete this task? This cannot be undone.')) return;
  try {
    await api.delete(`/tasks/${route.params.id}`);
    router.push({ name: 'TaskList' });
  } catch (err) {
    console.error('Failed to delete task:', err);
  }
};

const isOverdue = computed(() => {
  if (!task.value?.due_date || task.value.status === 'done') return false;
  return new Date(task.value.due_date) < new Date();
});

const statusClass = (status) => {
  const map = {
    todo: 'bg-gray-100 text-gray-700',
    in_progress: 'bg-yellow-100 text-yellow-800',
    done: 'bg-green-100 text-green-800',
  };
  return map[status] ?? 'bg-gray-100 text-gray-700';
};

const statusLabel = (status) => {
  const map = { todo: 'To Do', in_progress: 'In Progress', done: 'Done' };
  return map[status] ?? status;
};

const priorityClass = (priority) => {
  const map = {
    low: 'bg-blue-100 text-blue-800',
    medium: 'bg-orange-100 text-orange-800',
    high: 'bg-red-100 text-red-800',
  };
  return map[priority] ?? 'bg-gray-100 text-gray-700';
};

const formatDate = (dateStr) => {
  if (!dateStr) return '';
  return new Date(dateStr).toLocaleDateString(undefined, { year: 'numeric', month: 'short', day: 'numeric' });
};

onMounted(fetchTask);
</script>
