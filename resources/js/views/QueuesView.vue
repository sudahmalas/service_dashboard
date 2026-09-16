<template>
  <div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
      <div>
        <h1 class="text-2xl font-extrabold text-white tracking-tight">Queue & Store-Forward Explorer</h1>
        <p class="text-slate-400 text-sm mt-1">
          Pemeriksaan antrian penyangga. Memastikan tidak ada dokumen atau formulir yang hilang saat PC client offline.
        </p>
      </div>

      <button
        @click="fetchQueues"
        :disabled="loading"
        class="px-4 py-2 rounded-xl bg-slate-900 border border-slate-800 hover:bg-slate-800 text-slate-300 transition-all flex items-center gap-2 text-sm cursor-pointer self-start sm:self-auto"
      >
        <RefreshCw class="w-4 h-4" :class="{ 'animate-spin': loading }" />
        <span>Refresh Antrian</span>
      </button>
    </div>

    <!-- Filter Bar -->
    <div class="glass-panel p-4 rounded-2xl border border-slate-800 flex flex-wrap items-center gap-4">
      <!-- Client filter -->
      <div class="flex-1 min-w-[200px]">
        <label class="block text-xs font-medium text-slate-400 mb-1">Filter Client</label>
        <select
          v-model="filters.client_id"
          @change="fetchQueues"
          class="w-full rounded-xl bg-slate-900 border border-slate-800 text-sm text-slate-200 p-2 focus:border-indigo-500"
        >
          <option value="">Semua Client</option>
          <option v-for="c in clients" :key="c.id" :value="c.id">
            {{ c.name }}
          </option>
        </select>
      </div>

      <!-- Status filter -->
      <div class="flex-1 min-w-[150px]">
        <label class="block text-xs font-medium text-slate-400 mb-1">Status Antrian</label>
        <select
          v-model="filters.status"
          @change="fetchQueues"
          class="w-full rounded-xl bg-slate-900 border border-slate-800 text-sm text-slate-200 p-2 focus:border-indigo-500"
        >
          <option value="">Semua Status</option>
          <option value="pending">Pending (Belum Diambil / Offline)</option>
          <option value="synced">Synced (Sukses Ter-ACK)</option>
          <option value="failed">Failed (Error Eksekusi)</option>
        </select>
      </div>

      <!-- Type filter -->
      <div class="flex-1 min-w-[150px]">
        <label class="block text-xs font-medium text-slate-400 mb-1">Tipe Payload</label>
        <select
          v-model="filters.type"
          @change="fetchQueues"
          class="w-full rounded-xl bg-slate-900 border border-slate-800 text-sm text-slate-200 p-2 focus:border-indigo-500"
        >
          <option value="">Semua Tipe</option>
          <option value="print_label">print_label (Label / Antrian)</option>
          <option value="form_submission">form_submission (Form Input)</option>
          <option value="sync_command">sync_command (Sinkronisasi)</option>
        </select>
      </div>

      <!-- Reset -->
      <div class="self-end">
        <button
          @click="resetFilters"
          class="px-3.5 py-2 text-xs text-slate-400 hover:text-white rounded-xl bg-slate-900 border border-slate-800 hover:bg-slate-800"
        >
          Reset
        </button>
      </div>
    </div>

    <!-- Queues Table -->
    <div class="glass-panel rounded-2xl border border-slate-800 overflow-hidden shadow-xl">
      <div class="overflow-x-auto">
        <table class="w-full text-left text-sm text-slate-300">
          <thead class="bg-slate-900/80 text-xs font-semibold uppercase text-slate-400 border-b border-slate-800">
            <tr>
              <th class="px-6 py-4">Queue ID</th>
              <th class="px-6 py-4">Target Client</th>
              <th class="px-6 py-4">Type</th>
              <th class="px-6 py-4">Status</th>
              <th class="px-6 py-4">Retries</th>
              <th class="px-6 py-4">Created / Synced At</th>
              <th class="px-6 py-4 text-right">Actions</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-800/60">
            <tr v-if="loading && queues.length === 0">
              <td colspan="7" class="px-6 py-12 text-center text-slate-400">
                <RefreshCw class="w-6 h-6 animate-spin mx-auto mb-2 text-indigo-400" />
                <span>Memuat data antrian...</span>
              </td>
            </tr>

            <tr v-else-if="queues.length === 0">
              <td colspan="7" class="px-6 py-12 text-center text-slate-400">
                <Layers class="w-8 h-8 mx-auto mb-2 text-slate-400" />
                <p>Tidak ada antrian yang cocok dengan filter saat ini.</p>
              </td>
            </tr>

            <tr
              v-for="q in queues"
              :key="q.id"
              class="hover:bg-slate-900/50 transition-colors duration-150"
            >
              <!-- ID -->
              <td class="px-6 py-4 font-mono text-xs text-slate-300">
                <div class="flex items-center gap-1.5">
                  <span class="text-indigo-400 font-semibold">{{ q.id.substring(0, 8) }}</span>
                  <span class="text-slate-400">...</span>
                </div>
              </td>

              <!-- Client -->
              <td class="px-6 py-4">
                <div class="font-semibold text-white">{{ q.client?.name || 'Unknown Client' }}</div>
                <div class="text-xs text-slate-400 font-mono">channel: printer.{{ q.client?.slug }}</div>
              </td>

              <!-- Type -->
              <td class="px-6 py-4">
                <span
                  class="px-2.5 py-1 rounded-lg text-xs font-mono font-medium"
                  :class="{
                    'bg-indigo-500/10 text-indigo-300 border border-indigo-500/20': q.type === 'print_label',
                    'bg-cyan-500/10 text-cyan-300 border border-cyan-500/20': q.type === 'form_submission',
                    'bg-amber-500/10 text-amber-300 border border-amber-500/20': q.type === 'sync_command',
                  }"
                >
                  {{ q.type }}
                </span>
              </td>

              <!-- Status -->
              <td class="px-6 py-4">
                <span
                  class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold uppercase tracking-wider"
                  :class="{
                    'bg-amber-500/10 text-amber-400 border border-amber-500/20': q.status === 'pending',
                    'bg-indigo-500/10 text-indigo-400 border border-indigo-500/20': q.status === 'dispatched',
                    'bg-emerald-500/10 text-emerald-400 border border-emerald-500/20': q.status === 'synced',
                    'bg-rose-500/10 text-rose-400 border border-rose-500/20': q.status === 'failed',
                  }"
                >
                  <span
                    class="w-1.5 h-1.5 rounded-full"
                    :class="{
                      'bg-amber-400 animate-ping': q.status === 'pending',
                      'bg-indigo-400': q.status === 'dispatched',
                      'bg-emerald-400': q.status === 'synced',
                      'bg-rose-400': q.status === 'failed',
                    }"
                  ></span>
                  <span>{{ q.status }}</span>
                </span>
                <div v-if="q.error_message" class="text-[11px] text-rose-400 mt-1 max-w-[200px] truncate" :title="q.error_message">
                  {{ q.error_message }}
                </div>
              </td>

              <!-- Retries -->
              <td class="px-6 py-4 font-mono text-xs text-slate-400">
                {{ q.retry_count }}
              </td>

              <!-- Created / Synced At -->
              <td class="px-6 py-4 text-xs text-slate-300 font-mono">
                <div>Created: {{ formatTimestamp(q.created_at) }}</div>
                <div v-if="q.synced_at" class="text-emerald-400 mt-0.5">
                  Synced: {{ formatTimestamp(q.synced_at) }}
                </div>
              </td>

              <!-- Actions -->
              <td class="px-6 py-4 text-right space-x-2">
                <button
                  @click="inspectPayload(q)"
                  class="px-2.5 py-1 rounded-lg bg-slate-900 hover:bg-slate-800 border border-slate-800 text-xs text-slate-200 transition-colors inline-flex items-center gap-1"
                >
                  <Eye class="w-3.5 h-3.5" />
                  <span>Inspect</span>
                </button>
                <button
                  @click="resendQueue(q)"
                  :disabled="resendingId === q.id"
                  class="px-2.5 py-1 rounded-lg bg-indigo-950/40 hover:bg-indigo-900/60 border border-indigo-800/40 text-xs text-indigo-300 transition-colors inline-flex items-center gap-1"
                  title="Kirim Ulang ke Reverb"
                >
                  <Send class="w-3.5 h-3.5" :class="{ 'animate-pulse': resendingId === q.id }" />
                  <span>Resend</span>
                </button>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Payload Inspector Modal -->
    <div v-if="selectedQueue" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/80 backdrop-blur-sm">
      <div class="glass-panel border border-slate-800 rounded-2xl max-w-2xl w-full p-6 shadow-2xl space-y-4 max-h-[85vh] flex flex-col">
        <div class="flex items-center justify-between pb-3 border-b border-slate-800">
          <div>
            <h3 class="text-base font-bold text-white flex items-center gap-2">
              <Eye class="w-5 h-5 text-indigo-400" />
              <span>Payload Inspector (ID: {{ selectedQueue.id }})</span>
            </h3>
            <p class="text-xs text-slate-400">Target Client: {{ selectedQueue.client?.name }} • Type: {{ selectedQueue.type }}</p>
          </div>
          <button @click="selectedQueue = null" class="text-slate-400 hover:text-white">✕</button>
        </div>

        <div class="flex-1 overflow-y-auto space-y-3">
          <div class="flex items-center justify-between text-xs text-slate-400">
            <span>JSON Structure:</span>
            <button
              @click="copyPayload(selectedQueue.payload)"
              class="text-indigo-400 hover:text-indigo-300 flex items-center gap-1"
            >
              <Copy class="w-3 h-3" />
              <span>Copy Raw JSON</span>
            </button>
          </div>

          <pre class="p-4 rounded-xl bg-slate-900/90 border border-slate-800/80 text-xs font-mono text-indigo-200 overflow-x-auto selection:bg-indigo-600 selection:text-white">{{ JSON.stringify(selectedQueue.payload, null, 2) }}</pre>
        </div>

        <div class="pt-3 border-t border-slate-800 flex items-center justify-between">
          <button
            @click="resendQueue(selectedQueue)"
            class="px-4 py-2 rounded-xl bg-indigo-600 hover:bg-indigo-500 text-white text-xs font-semibold flex items-center gap-2"
          >
            <Send class="w-3.5 h-3.5" />
            <span>Resend Job Now</span>
          </button>
          <button
            @click="selectedQueue = null"
            class="px-4 py-2 rounded-xl bg-slate-900 border border-slate-800 text-xs text-slate-300 hover:bg-slate-800"
          >
            Tutup
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue';
import axios from 'axios';
import { RefreshCw, Layers, Eye, Send, Copy } from 'lucide-vue-next';

const loading = ref(false);
const resendingId = ref(null);
const queues = ref([]);
const clients = ref([]);
const selectedQueue = ref(null);

const filters = reactive({
  client_id: '',
  status: '',
  type: '',
});

const formatTimestamp = (ts) => {
  if (!ts) return '-';
  try {
    const d = new Date(ts);
    return d.toLocaleDateString('id-ID', { day: '2-digit', month: 'short' }) + ' ' + d.toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit', second: '2-digit' });
  } catch {
    return ts;
  }
};

const fetchClients = async () => {
  try {
    const res = await axios.get('/api/v1/admin/clients');
    if (res.data.success) {
      clients.value = res.data.data;
    }
  } catch (e) {}
};

const fetchQueues = async () => {
  loading.value = true;
  try {
    const params = {};
    if (filters.client_id) params.client_id = filters.client_id;
    if (filters.status) params.status = filters.status;
    if (filters.type) params.type = filters.type;

    const res = await axios.get('/api/v1/admin/queues', { params });
    if (res.data.success) {
      queues.value = res.data.data;
    }
  } catch (err) {
    console.error('Failed to load queues', err);
  } finally {
    loading.value = false;
  }
};

const resetFilters = () => {
  filters.client_id = '';
  filters.status = '';
  filters.type = '';
  fetchQueues();
};

const inspectPayload = (q) => {
  selectedQueue.value = q;
};

const copyPayload = async (payload) => {
  try {
    await navigator.clipboard.writeText(JSON.stringify(payload, null, 2));
    alert('Payload disalin ke clipboard!');
  } catch (e) {}
};

const resendQueue = async (q) => {
  resendingId.value = q.id;
  try {
    const res = await axios.post(`/api/v1/admin/queues/${q.id}/resend`);
    if (res.data.success) {
      alert('Antrian berhasil dikirim ulang ke Reverb!');
      await fetchQueues();
      if (selectedQueue.value && selectedQueue.value.id === q.id) {
        selectedQueue.value.status = 'pending';
      }
    }
  } catch (err) {
    alert('Gagal resend: ' + (err.response?.data?.message || err.message));
  } finally {
    resendingId.value = null;
  }
};

onMounted(() => {
  fetchClients();
  fetchQueues();
});
</script>
