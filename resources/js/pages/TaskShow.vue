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

      <!-- Comments -->
      <div class="mt-6 bg-white rounded-xl shadow-sm border border-gray-200 p-6">
        <h2 class="text-lg font-semibold text-gray-800 mb-4">Comments</h2>

        <!-- New comment composer -->
        <form @submit.prevent="postComment" class="mb-6">
          <textarea
            v-model="newBody"
            rows="3"
            placeholder="Add a comment…"
            class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm text-sm text-gray-900 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-primary-500 resize-none"
          ></textarea>
          <p v-if="newError" class="mt-1 text-sm text-red-600">{{ newError }}</p>
          <div class="mt-2 flex justify-end">
            <button
              type="submit"
              :disabled="postingComment"
              class="px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-primary-600 hover:bg-primary-700 disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
            >
              {{ postingComment ? 'Posting…' : 'Comment' }}
            </button>
          </div>
        </form>

        <!-- Loading -->
        <div v-if="commentsLoading" class="text-sm text-gray-400">Loading comments…</div>

        <!-- Empty -->
        <div v-else-if="comments.length === 0" class="text-sm text-gray-400 italic">
          No comments yet. Be the first to comment.
        </div>

        <!-- List -->
        <ul v-else class="space-y-5">
          <li v-for="c in comments" :key="c.id" class="border-b border-gray-100 pb-4 last:border-0">
            <div class="flex justify-between items-start">
              <div>
                <span class="font-medium text-gray-900 text-sm">{{ c.author?.name ?? 'Unknown' }}</span>
                <span class="text-gray-400 text-xs ml-2">{{ formatDateTime(c.created_at) }}</span>
              </div>
              <button
                v-if="isAuthor(c)"
                @click="deleteComment(c)"
                class="text-xs text-red-500 hover:text-red-700 font-medium"
              >
                Delete
              </button>
            </div>
            <p class="text-gray-700 text-sm mt-1 whitespace-pre-wrap">{{ c.body }}</p>

            <div class="mt-2">
              <button
                @click="toggleReply(c)"
                class="text-xs text-primary-600 hover:text-primary-800 font-medium"
              >
                Reply
              </button>
            </div>

            <!-- Reply composer -->
            <form v-if="replyOpen === c.id" @submit.prevent="postReply(c)" class="mt-2 ml-4">
              <textarea
                v-model="replyBody"
                rows="2"
                placeholder="Write a reply…"
                class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm text-sm text-gray-900 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-primary-500 resize-none"
              ></textarea>
              <p v-if="replyError" class="mt-1 text-sm text-red-600">{{ replyError }}</p>
              <div class="mt-1 flex justify-end gap-2">
                <button type="button" @click="toggleReply(c)" class="text-xs text-gray-500 hover:text-gray-700">
                  Cancel
                </button>
                <button
                  type="submit"
                  :disabled="postingReply"
                  class="px-3 py-1 border border-transparent rounded-md text-xs font-medium text-white bg-primary-600 hover:bg-primary-700 disabled:opacity-50 transition-colors"
                >
                  {{ postingReply ? 'Posting…' : 'Reply' }}
                </button>
              </div>
            </form>

            <!-- Replies -->
            <ul v-if="c.replies.length" class="mt-3 ml-4 space-y-3 border-l-2 border-gray-100 pl-4">
              <li v-for="r in c.replies" :key="r.id">
                <div class="flex justify-between items-start">
                  <div>
                    <span class="font-medium text-gray-900 text-sm">{{ r.author?.name ?? 'Unknown' }}</span>
                    <span class="text-gray-400 text-xs ml-2">{{ formatDateTime(r.created_at) }}</span>
                  </div>
                  <button
                    v-if="isAuthor(r)"
                    @click="deleteReply(c, r)"
                    class="text-xs text-red-500 hover:text-red-700 font-medium"
                  >
                    Delete
                  </button>
                </div>
                <p class="text-gray-700 text-sm mt-1 whitespace-pre-wrap">{{ r.body }}</p>
              </li>
            </ul>

            <!-- Show more replies -->
            <button
              v-if="hasMoreReplies(c)"
              @click="loadMoreReplies(c)"
              :disabled="c.loadingReplies"
              class="mt-2 ml-4 text-xs text-primary-600 hover:text-primary-800 font-medium disabled:opacity-50"
            >
              {{ c.loadingReplies ? 'Loading…' : showMoreLabel(c) }}
            </button>
          </li>
        </ul>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { useEcho } from '@laravel/echo-vue';
import api from '../composables/useApi.js';
import { useAuth } from '../composables/useAuth.js';

const route = useRoute();
const router = useRouter();
const { user } = useAuth();

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

const formatDateTime = (dateStr) => {
  if (!dateStr) return '';
  return new Date(dateStr).toLocaleString(undefined, {
    year: 'numeric', month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit',
  });
};

/* ----------------------------- Comments ----------------------------- */

const comments = ref([]);
const commentsLoading = ref(true);
const newBody = ref('');
const newError = ref('');
const postingComment = ref(false);

const replyOpen = ref(null);
const replyBody = ref('');
const replyError = ref('');
const postingReply = ref(false);

// Normalize an API comment into a client-side shape with reply paging state.
const normalize = (c) => ({
  ...c,
  replies: c.replies ?? [],
  expanded: false,
  page: 0,
  lastPage: null,
  loadingReplies: false,
});

const isAuthor = (c) => !!user.value && c.author?.id === user.value.id;

const validationError = (err) =>
  err.response?.status === 422
    ? err.response.data.errors?.body?.[0] ?? err.response.data.message
    : 'Something went wrong. Please try again.';

const fetchComments = async () => {
  commentsLoading.value = true;
  try {
    const response = await api.get(`/tasks/${route.params.id}/comments`);
    comments.value = response.data.data.map(normalize);
  } catch (err) {
    console.error('Failed to fetch comments:', err);
  } finally {
    commentsLoading.value = false;
  }
};

const postComment = async () => {
  newError.value = '';
  postingComment.value = true;
  try {
    const response = await api.post(`/tasks/${route.params.id}/comments`, { body: newBody.value });
    comments.value.unshift(normalize(response.data.data));
    newBody.value = '';
  } catch (err) {
    newError.value = validationError(err);
  } finally {
    postingComment.value = false;
  }
};

const toggleReply = (c) => {
  replyError.value = '';
  replyBody.value = '';
  replyOpen.value = replyOpen.value === c.id ? null : c.id;
};

const postReply = async (c) => {
  replyError.value = '';
  postingReply.value = true;
  try {
    const response = await api.post(`/tasks/${route.params.id}/comments`, {
      body: replyBody.value,
      parent_id: c.id,
    });
    c.replies.push(response.data.data);
    c.replies_count = (c.replies_count ?? 0) + 1;
    replyBody.value = '';
    replyOpen.value = null;
  } catch (err) {
    replyError.value = validationError(err);
  } finally {
    postingReply.value = false;
  }
};

const hasMoreReplies = (c) => (c.replies_count ?? 0) > c.replies.length;

const showMoreLabel = (c) =>
  c.expanded ? 'Show more replies' : `Show all ${c.replies_count} replies`;

const loadMoreReplies = async (c) => {
  c.loadingReplies = true;
  try {
    const nextPage = c.expanded ? c.page + 1 : 1;
    const response = await api.get(`/comments/${c.id}/replies`, { params: { page: nextPage } });
    const { data, meta } = response.data;
    if (c.expanded) {
      c.replies.push(...data);
    } else {
      c.replies = data;
      c.expanded = true;
    }
    c.page = meta.current_page;
    c.lastPage = meta.last_page;
  } catch (err) {
    console.error('Failed to load replies:', err);
  } finally {
    c.loadingReplies = false;
  }
};

const deleteComment = async (c) => {
  if (!confirm('Delete this comment? Replies will also be removed.')) return;
  try {
    await api.delete(`/comments/${c.id}`);
    comments.value = comments.value.filter((x) => x.id !== c.id);
  } catch (err) {
    console.error('Failed to delete comment:', err);
  }
};

const deleteReply = async (c, r) => {
  if (!confirm('Delete this reply?')) return;
  try {
    await api.delete(`/comments/${r.id}`);
    c.replies = c.replies.filter((x) => x.id !== r.id);
    c.replies_count = Math.max(0, (c.replies_count ?? 1) - 1);
  } catch (err) {
    console.error('Failed to delete reply:', err);
  }
};

// Real-time: merge broadcast comments/replies, de-duped by id.
const upsertComment = (payload) => {
  if (comments.value.some((c) => c.id === payload.id)) return;
  comments.value.unshift(normalize(payload));
};

const upsertReply = (payload) => {
  const parent = comments.value.find((c) => c.id === payload.parent_id);
  if (!parent) return; // parent not in the current view; will appear on reload/expand
  if (parent.replies.some((r) => r.id === payload.id)) return;
  parent.replies.push(payload);
  parent.replies_count = (parent.replies_count ?? 0) + 1;
};

useEcho(`tasks.${route.params.id}.comments`, '.comment.created', upsertComment, [], 'private');
useEcho(`tasks.${route.params.id}.comments`, '.reply.created', upsertReply, [], 'private');

onMounted(() => {
  fetchTask();
  fetchComments();
});
</script>
