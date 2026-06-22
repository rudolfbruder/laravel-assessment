<template>
  <div class="max-w-2xl">
    <!-- Header -->
    <div class="mb-6">
      <div class="flex items-center space-x-2 text-sm text-gray-500 mb-2">
        <router-link to="/" class="hover:text-primary-600 transition-colors">Tasks</router-link>
        <span>/</span>
        <router-link
          v-if="task"
          :to="`/tasks/${route.params.id}`"
          class="hover:text-primary-600 transition-colors truncate max-w-xs"
        >
          {{ task.name }}
        </router-link>
        <span>/</span>
        <span class="text-gray-700 font-medium">Edit</span>
      </div>
      <h1 class="text-2xl font-bold text-gray-900">Edit Task</h1>
    </div>

    <!-- Loading task data -->
    <div v-if="fetching" class="flex justify-center py-16">
      <span class="text-gray-400 text-sm">Loading task data…</span>
    </div>

    <!-- Form card -->
    <div v-else class="bg-white shadow-sm rounded-xl border border-gray-200 p-6">
      <form @submit.prevent="handleUpdate" class="space-y-6">
        <!-- Name -->
        <div>
          <label for="name" class="block text-sm font-medium text-gray-700 mb-1">
            Task name <span class="text-red-500">*</span>
          </label>
          <input
            id="name"
            v-model="form.name"
            type="text"
            required
            class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm text-gray-900 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-primary-500 sm:text-sm"
          />
        </div>

        <!-- Description -->
        <div>
          <label for="description" class="block text-sm font-medium text-gray-700 mb-1">Description</label>
          <textarea
            id="description"
            v-model="form.description"
            rows="4"
            class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm text-gray-900 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-primary-500 sm:text-sm resize-none"
          ></textarea>
        </div>

        <!-- Status + Priority row -->
        <div class="grid grid-cols-2 gap-4">
          <div>
            <label for="status" class="block text-sm font-medium text-gray-700 mb-1">Status</label>
            <select
              id="status"
              v-model="form.status"
              class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm text-gray-900 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-primary-500 sm:text-sm bg-white"
            >
              <option value="todo">To Do</option>
              <option value="in_progress">In Progress</option>
              <option value="done">Done</option>
            </select>
          </div>
          <div>
            <label for="priority" class="block text-sm font-medium text-gray-700 mb-1">Priority</label>
            <select
              id="priority"
              v-model="form.priority"
              class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm text-gray-900 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-primary-500 sm:text-sm bg-white"
            >
              <option value="low">Low</option>
              <option value="medium">Medium</option>
              <option value="high">High</option>
            </select>
          </div>
        </div>

        <!-- Due date -->
        <div>
          <label for="due_date" class="block text-sm font-medium text-gray-700 mb-1">Due date</label>
          <input
            id="due_date"
            v-model="form.due_date"
            type="date"
            class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm text-gray-900 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-primary-500 sm:text-sm"
          />
        </div>

        <!-- Error -->
        <div v-if="error" class="rounded-md bg-red-50 p-4">
          <p class="text-sm text-red-700">{{ error }}</p>
        </div>

        <!-- Actions -->
        <div class="flex justify-end space-x-3 pt-2">
          <router-link
            :to="`/tasks/${route.params.id}`"
            class="px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500 transition-colors"
          >
            Cancel
          </router-link>
          <button
            type="submit"
            :disabled="loading"
            class="px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-primary-600 hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500 disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
          >
            <span v-if="loading">Saving…</span>
            <span v-else>Save changes</span>
          </button>
        </div>
      </form>
    </div>
  </div>
</template>

<script setup>
import { onMounted, reactive, ref } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import api from '../composables/useApi.js';

const route = useRoute();
const router = useRouter();

const task = ref(null);
const form = reactive({
  name: '',
  description: '',
  status: 'todo',
  priority: 'medium',
  due_date: '',
});
const error = ref('');
const loading = ref(false);
const fetching = ref(true);

const fetchTask = async () => {
  fetching.value = true;
  try {
    const response = await api.get(`/tasks/${route.params.id}`);
    task.value = response.data.data;
    form.name = task.value.name;
    form.description = task.value.description ?? '';
    form.priority = task.value.priority;
    form.status = task.value.status;
    form.due_date = task.value.due_date ? task.value.due_date.split('T')[0].split(' ')[0] : '';
  } catch (err) {
    error.value = 'Failed to load task details.';
  } finally {
    fetching.value = false;
  }
};

const handleUpdate = async () => {
  error.value = '';
  loading.value = true;
  try {
    await api.put(`/tasks/${route.params.id}`, form);
    router.push({ name: 'TaskShow', params: { id: route.params.id } });
  } catch (err) {
    error.value = err.response?.data?.message || 'Failed to update task. Please try again.';
  } finally {
    loading.value = false;
  }
};

onMounted(fetchTask);
</script>
