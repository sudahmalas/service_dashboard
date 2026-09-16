<template>
  <div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
      <div>
        <h1 class="text-2xl font-extrabold text-white tracking-tight flex items-center gap-2.5">
          <Layers class="w-6 h-6 text-indigo-400" />
          <span>Queue & Store-Forward Explorer</span>
        </h1>
        <p class="text-slate-400 text-sm mt-1">
          Pantau antrian penyangga dan lalu lintas relay cetak antar project tenant secara real-time.
        </p>
      </div>

      <div class="flex items-center gap-3">
        <button
          @click="fetchQueues"
          :disabled="loading"
          class="px-4 py-2 rounded-xl bg-slate-900 border border-slate-800 hover:bg-slate-800 text-slate-300 hover:text-white transition-all flex items-center gap-2 text-sm cursor-pointer shadow-sm"
        >
          <RefreshCw class="w-4 h-4" :class="{ 'animate-spin': loading }" />
          <span>Refresh Data</span>
        </button>
      </div>
    </div>

    <!-- Summary Metrics Cards -->
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
      <div class="glass-panel p-4 rounded-2xl border border-slate-800/80 bg-slate-900/40">
        <div class="flex items-center justify-between">
          <span class="text-xs font-medium text-slate-400">Total Antrian</span>
          <Layers class="w-4 h-4 text-slate-500" />
        </div>
        <div class="text-2xl font-black text-white mt-2">{{ stats.total }}</div>
        <div class="text-[11px] text-slate-500 mt-1">Semua riwayat relay</div>
      </div>

      <div class="glass-panel p-4 rounded-2xl border border-amber-500/20 bg-amber-500/5">
        <div class="flex items-center justify-between">
          <span class="text-xs font-medium text-amber-400">Pending (Buffer)</span>
          <Clock class="w-4 h-4 text-amber-400" />
        </div>
        <div class="text-2xl font-black text-amber-300 mt-2">{{ stats.pending }}</div>
        <div class="text-[11px] text-amber-400/70 mt-1">Menunggu PC Client online</div>
      </div>

      <div class="glass-panel p-4 rounded-2xl border border-indigo-500/20 bg-indigo-500/5">
        <div class="flex items-center justify-between">
          <span class="text-xs font-medium text-indigo-400">Dispatched (Reverb)</span>
          <Send class="w-4 h-4 text-indigo-400" />
        </div>
        <div class="text-2xl font-black text-indigo-300 mt-2">{{ stats.dispatched }}</div>
        <div class="text-[11px] text-indigo-400/70 mt-1">Tersiar ke WebSocket</div>
      </div>

      <div class="glass-panel p-4 rounded-2xl border border-emerald-500/20 bg-emerald-500/5">
        <div class="flex items-center justify-between">
          <span class="text-xs font-medium text-emerald-400">Synced / Selesai</span>
          <CheckCircle2 class="w-4 h-4 text-emerald-400" />
        </div>
        <div class="text-2xl font-black text-emerald-300 mt-2">{{ stats.synced }}</div>
        <div class="text-[11px] text-emerald-400/70 mt-1">Berhasil dicetak & di-ACK</div>
      </div>
    </div>

    <!-- Filter & Search Bar -->
    <div class="glass-panel p-4 sm:p-5 rounded-2xl border border-slate-800 space-y-3 shadow-md">
      <div class="grid grid-cols-1 md:grid-cols-12 gap-3 items-end">
        <!-- Search -->
        <div class="md:col-span-4">
          <label class="block text-xs font-medium text-slate-400 mb-1.5">Pencarian</label>
          <div class="relative">
            <Search class="w-4 h-4 text-slate-500 absolute left-3 top-1/2 -translate-y-1/2" />
            <input
              v-model="filters.search"
              @keyup.enter="fetchQueues"
              type="text"
              placeholder="Cari Queue ID, Client, atau Project..."
              class="w-full pl-9 pr-3 py-2 rounded-xl bg-slate-900/90 border border-slate-800 text-sm text-slate-200 placeholder-slate-500 focus:outline-none focus:border-indigo-500 transition-colors"
            />
          </div>
        </div>

        <!-- Project Filter -->
        <div class="md:col-span-3">
          <label class="block text-xs font-medium text-slate-400 mb-1.5 flex items-center justify-between">
            <span class="flex items-center gap-1.5">
              <FolderGit2 class="w-3.5 h-3.5 text-indigo-400" />
              <span>Filter Project / Tenant</span>
            </span>
          </label>
          <select
            v-model="filters.project_id"
            @change="handleProjectChange"
            class="w-full rounded-xl bg-slate-900/90 border border-slate-800 text-sm text-slate-200 p-2 focus:outline-none focus:border-indigo-500 transition-colors"
          >
            <option value="">Semua Project (Tenant)</option>
            <option v-for="p in projects" :key="p.id" :value="p.id">
              [{{ p.code }}] {{ p.name }}
            </option>
            <option value="none">Tanpa Project (Unassigned)</option>
          </select>
        </div>

        <!-- Target Client filter -->
        <div class="md:col-span-2">
          <label class="block text-xs font-medium text-slate-400 mb-1.5">Client / Node</label>
          <select
            v-model="filters.client_id"
            @change="fetchQueues"
            class="w-full rounded-xl bg-slate-900/90 border border-slate-800 text-sm text-slate-200 p-2 focus:outline-none focus:border-indigo-500 transition-colors"
          >
            <option value="">Semua Client</option>
            <option v-for="c in filteredClients" :key="c.id" :value="c.id">
              {{ c.name }}
            </option>
          </select>
        </div>

        <!-- Status filter -->
        <div class="md:col-span-2">
          <label class="block text-xs font-medium text-slate-400 mb-1.5">Status</label>
          <select
            v-model="filters.status"
            @change="fetchQueues"
            class="w-full rounded-xl bg-slate-900/90 border border-slate-800 text-sm text-slate-200 p-2 focus:outline-none focus:border-indigo-500 transition-colors"
          >
            <option value="">Semua Status</option>
            <option value="pending">Pending (Buffer / Offline)</option>
            <option value="dispatched">Dispatched (Reverb)</option>
            <option value="synced">Synced (Selesai ACK)</option>
            <option value="failed">Failed (Gagal Eksekusi)</option>
          </select>
        </div>

        <!-- Reset Button -->
        <div class="md:col-span-1">
          <button
            @click="resetFilters"
            class="w-full py-2 text-xs font-semibold text-slate-400 hover:text-white rounded-xl bg-slate-900 border border-slate-800 hover:bg-slate-800 transition-colors cursor-pointer"
            title="Reset Filter"
          >
            Reset
          </button>
        </div>
      </div>
    </div>

    <!-- Queues Table -->
    <div class="glass-panel rounded-2xl border border-slate-800 overflow-hidden shadow-xl">
      <div class="overflow-x-auto">
        <table class="w-full text-left text-sm text-slate-300 min-w-[1000px]">
          <thead class="bg-slate-900/90 text-xs font-bold uppercase tracking-wider text-slate-400 border-b border-slate-800 sticky top-0 z-10 backdrop-blur-md">
            <tr>
              <th class="px-5 py-4 w-[160px]">Queue ID</th>
              <th class="px-5 py-4 w-[220px]">Project / Tenant</th>
              <th class="px-5 py-4 w-[200px]">Target Client / Node</th>
              <th class="px-5 py-4 w-[130px]">Tipe</th>
              <th class="px-5 py-4 w-[140px]">Status</th>
              <th class="px-5 py-4 w-[90px] text-center">Retries</th>
              <th class="px-5 py-4">Waktu Dibuat / Disinkronkan</th>
              <th class="px-5 py-4 text-right w-[140px]">Aksi</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-800/60">
            <tr v-if="loading && queues.length === 0">
              <td colspan="8" class="px-6 py-16 text-center text-slate-400">
                <RefreshCw class="w-6 h-6 animate-spin mx-auto mb-2 text-indigo-400" />
                <span>Memuat data antrian...</span>
              </td>
            </tr>

            <tr v-else-if="queues.length === 0">
              <td colspan="8" class="px-6 py-16 text-center text-slate-400">
                <Layers class="w-10 h-10 mx-auto mb-3 text-slate-600" />
                <p class="font-medium text-slate-300">Tidak ada antrian yang cocok.</p>
                <p class="text-xs text-slate-500 mt-1">Coba sesuaikan filter atau lakukan tes cetak dari aplikasi Prima.</p>
              </td>
            </tr>

            <tr
              v-for="q in queues"
              :key="q.id"
              class="hover:bg-slate-900/50 transition-colors duration-150 group"
            >
              <!-- Queue ID -->
              <td class="px-5 py-4 align-top">
                <div class="flex items-center gap-1.5 font-mono text-xs">
                  <span class="text-indigo-400 font-bold">{{ q.id.substring(0, 8) }}</span>
                  <span class="text-slate-500">...</span>
                  <button
                    @click="copyText(q.id, 'Queue ID')"
                    class="opacity-0 group-hover:opacity-100 text-slate-400 hover:text-white transition-opacity p-0.5"
                    title="Copy Full UUID"
                  >
                    <Copy class="w-3 h-3" />
                  </button>
                </div>
                <span class="text-[10px] text-slate-500 block mt-0.5 font-mono">UUID v4</span>
              </td>

              <!-- Project / Tenant Column -->
              <td class="px-5 py-4 align-top">
                <div v-if="q.client?.project" class="space-y-1">
                  <div class="flex items-center gap-1.5 flex-wrap">
                    <span class="px-2 py-0.5 rounded text-[10px] font-black uppercase tracking-wider bg-indigo-500/15 text-indigo-300 border border-indigo-500/30 flex items-center gap-1">
                      <FolderGit2 class="w-2.5 h-2.5 text-indigo-400 shrink-0" />
                      {{ q.client.project.code }}
                    </span>
                  </div>
                  <p class="text-xs font-semibold text-white truncate max-w-[180px]" :title="q.client.project.name">
                    {{ q.client.project.name }}
                  </p>
                </div>
                <div v-else class="flex items-center gap-1.5 text-xs text-slate-500 italic">
                  <span>Tanpa Project</span>
                </div>
              </td>

              <!-- Target Client -->
              <td class="px-5 py-4 align-top">
                <div class="font-bold text-white text-xs truncate max-w-[180px]" :title="q.client?.name">
                  {{ q.client?.name || 'Unknown Client' }}
                </div>
                <div class="text-[11px] text-slate-400 mt-0.5 flex items-center gap-1 font-mono">
                  <span>{{ q.client?.machine_name || 'PC Client' }}</span>
                  <span v-if="q.client?.ip_address" class="text-slate-600">({{ q.client.ip_address }})</span>
                </div>
                <div class="text-[10px] text-indigo-400/80 font-mono mt-0.5">
                  printer.{{ q.client?.slug || 'unknown' }}
                </div>
              </td>

              <!-- Type -->
              <td class="px-5 py-4 align-top">
                <span
                  class="px-2.5 py-1 rounded-lg text-xs font-mono font-semibold inline-block"
                  :class="{
                    'bg-indigo-500/15 text-indigo-300 border border-indigo-500/30': q.type === 'print_label',
                    'bg-cyan-500/15 text-cyan-300 border border-cyan-500/30': q.type === 'form_submission',
                    'bg-amber-500/15 text-amber-300 border border-amber-500/30': q.type === 'sync_command',
                  }"
                >
                  {{ q.type }}
                </span>
              </td>

              <!-- Status -->
              <td class="px-5 py-4 align-top">
                <span
                  class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-bold uppercase tracking-wider border"
                  :class="{
                    'bg-amber-500/10 text-amber-400 border-amber-500/30': q.status === 'pending',
                    'bg-indigo-500/10 text-indigo-400 border-indigo-500/30': q.status === 'dispatched',
                    'bg-emerald-500/10 text-emerald-400 border-emerald-500/30': q.status === 'synced',
                    'bg-rose-500/10 text-rose-400 border-rose-500/30': q.status === 'failed',
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
                <div v-if="q.error_message" class="text-[11px] text-rose-400 mt-1 max-w-[160px] truncate" :title="q.error_message">
                  {{ q.error_message }}
                </div>
              </td>

              <!-- Retries -->
              <td class="px-5 py-4 align-top text-center font-mono text-xs">
                <span :class="q.retry_count > 0 ? 'text-amber-400 font-bold' : 'text-slate-500'">
                  {{ q.retry_count }}x
                </span>
              </td>

              <!-- Timestamp -->
              <td class="px-5 py-4 align-top text-xs text-slate-300 font-mono space-y-0.5">
                <div><span class="text-slate-500">In:</span> {{ formatTimestamp(q.created_at) }}</div>
                <div v-if="q.synced_at" class="text-emerald-400">
                  <span class="text-emerald-600">Out:</span> {{ formatTimestamp(q.synced_at) }}
                </div>
              </td>

              <!-- Actions -->
              <td class="px-5 py-4 align-top text-right">
                <div class="flex items-center justify-end gap-1.5">
                  <button
                    @click="inspectPayload(q)"
                    class="px-2.5 py-1.5 rounded-lg bg-slate-900 hover:bg-slate-800 border border-slate-800 text-xs text-slate-300 hover:text-white transition-colors inline-flex items-center gap-1 cursor-pointer"
                    title="Periksa Detail Payload"
                  >
                    <Eye class="w-3.5 h-3.5" />
                    <span>Detail</span>
                  </button>
                  <button
                    @click="resendQueue(q)"
                    :disabled="resendingId === q.id"
                    class="px-2.5 py-1.5 rounded-lg bg-indigo-950/40 hover:bg-indigo-900/60 border border-indigo-800/40 text-xs text-indigo-300 hover:text-indigo-200 transition-colors inline-flex items-center gap-1 cursor-pointer"
                    title="Kirim Ulang via Reverb"
                  >
                    <Send class="w-3.5 h-3.5" :class="{ 'animate-pulse': resendingId === q.id }" />
                    <span>Resend</span>
                  </button>
                  <button
                    @click="deleteQueue(q.id)"
                    class="p-1.5 rounded-lg bg-slate-900 hover:bg-rose-950/50 border border-slate-800 hover:border-rose-900/50 text-slate-400 hover:text-rose-400 transition-colors cursor-pointer"
                    title="Hapus Antrian"
                  >
                    <Trash2 class="w-3.5 h-3.5" />
                  </button>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- Pagination Bar -->
      <div v-if="meta.total > 0" class="p-4 bg-slate-900/60 border-t border-slate-800 flex items-center justify-between text-xs text-slate-400">
        <div>
          Menampilkan baris {{ (meta.current_page - 1) * meta.per_page + 1 }} -
          {{ Math.min(meta.current_page * meta.per_page, meta.total) }} dari
          <span class="text-white font-bold">{{ meta.total }}</span> antrian
        </div>
        <div class="flex items-center gap-2">
          <button
            :disabled="meta.current_page <= 1"
            @click="changePage(meta.current_page - 1)"
            class="px-3 py-1.5 rounded-lg bg-slate-900 border border-slate-800 text-slate-300 hover:text-white disabled:opacity-40 disabled:cursor-not-allowed cursor-pointer"
          >
            Sebelumnya
          </button>
          <span class="px-2 py-1 font-mono text-slate-300">
            {{ meta.current_page }} / {{ meta.last_page || 1 }}
          </span>
          <button
            :disabled="meta.current_page >= meta.last_page"
            @click="changePage(meta.current_page + 1)"
            class="px-3 py-1.5 rounded-lg bg-slate-900 border border-slate-800 text-slate-300 hover:text-white disabled:opacity-40 disabled:cursor-not-allowed cursor-pointer"
          >
            Berikutnya
          </button>
        </div>
      </div>
    </div>

    <!-- Payload Inspector Modal -->
    <div v-if="selectedQueue" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/80 backdrop-blur-sm animate-in fade-in duration-200">
      <div class="glass-panel border border-slate-800 rounded-2xl max-w-2xl w-full p-6 shadow-2xl space-y-4 max-h-[85vh] flex flex-col">
        <div class="flex items-center justify-between pb-3 border-b border-slate-800">
          <div>
            <h3 class="text-base font-bold text-white flex items-center gap-2">
              <Eye class="w-5 h-5 text-indigo-400" />
              <span>Detail Antrian & Payload</span>
            </h3>
            <p class="text-xs text-slate-400 font-mono mt-0.5">ID: {{ selectedQueue.id }}</p>
          </div>
          <button @click="selectedQueue = null" class="text-slate-400 hover:text-white p-1 rounded-lg hover:bg-slate-800 transition-colors">
            <X class="w-5 h-5" />
          </button>
        </div>

        <!-- Project & Client Metadata -->
        <div class="grid grid-cols-2 gap-3 p-3.5 rounded-xl bg-slate-900/70 border border-slate-800/80 text-xs">
          <div>
            <span class="text-slate-500 block mb-1">Project Tenant:</span>
            <div v-if="selectedQueue.client?.project" class="flex items-center gap-2">
              <span class="px-2 py-0.5 rounded font-black text-[10px] bg-indigo-500/20 text-indigo-300 border border-indigo-500/30">
                {{ selectedQueue.client.project.code }}
              </span>
              <span class="font-bold text-white">{{ selectedQueue.client.project.name }}</span>
            </div>
            <div v-else class="text-slate-400 italic">Tanpa Project</div>
          </div>

          <div>
            <span class="text-slate-500 block mb-1">Target Client Hardware:</span>
            <div class="font-bold text-white">{{ selectedQueue.client?.name }}</div>
            <div class="text-[11px] text-slate-400 font-mono mt-0.5">
              Machine: {{ selectedQueue.client?.machine_name || '-' }} ({{ selectedQueue.client?.ip_address || 'Local' }})
            </div>
          </div>
        </div>

        <!-- JSON Payload Viewer -->
        <div class="flex-1 overflow-y-auto space-y-2.5">
          <div class="flex items-center justify-between text-xs text-slate-400">
            <span class="font-mono">Payload Content ({{ selectedQueue.type }}):</span>
            <button
              @click="copyText(JSON.stringify(selectedQueue.payload, null, 2), 'Payload JSON')"
              class="text-indigo-400 hover:text-indigo-300 flex items-center gap-1 cursor-pointer"
            >
              <Copy class="w-3.5 h-3.5" />
              <span>Copy JSON</span>
            </button>
          </div>

          <pre class="p-4 rounded-xl bg-slate-900/90 border border-slate-800 text-xs font-mono text-indigo-200 overflow-x-auto selection:bg-indigo-600 selection:text-white max-h-[300px]">{{ JSON.stringify(selectedQueue.payload, null, 2) }}</pre>
        </div>

        <!-- Modal Footer -->
        <div class="pt-3 border-t border-slate-800 flex items-center justify-between">
          <button
            @click="resendQueue(selectedQueue)"
            :disabled="resendingId === selectedQueue.id"
            class="px-4 py-2 rounded-xl bg-indigo-600 hover:bg-indigo-500 text-white text-xs font-bold flex items-center gap-2 transition-colors cursor-pointer"
          >
            <Send class="w-3.5 h-3.5" />
            <span>Kirim Ulang Sekarang</span>
          </button>
          <button
            @click="selectedQueue = null"
            class="px-4 py-2 rounded-xl bg-slate-900 border border-slate-800 text-xs text-slate-300 hover:bg-slate-800 transition-colors cursor-pointer"
          >
            Tutup
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive, computed, onMounted } from 'vue';
import axios from 'axios';
import {
  RefreshCw,
  Layers,
  Eye,
  Send,
  Copy,
  FolderGit2,
  Search,
  Clock,
  CheckCircle2,
  Trash2,
  X
} from 'lucide-vue-next';

const loading = ref(false);
const resendingId = ref(null);
const queues = ref([]);
const clients = ref([]);
const projects = ref([]);
const selectedQueue = ref(null);

const stats = reactive({
  total: 0,
  pending: 0,
  dispatched: 0,
  synced: 0,
  failed: 0,
});

const meta = reactive({
  current_page: 1,
  last_page: 1,
  total: 0,
  per_page: 25,
});

const filters = reactive({
  search: '',
  project_id: '',
  client_id: '',
  status: '',
  type: '',
  page: 1,
});

const formatTimestamp = (ts) => {
  if (!ts) return '-';
  try {
    const d = new Date(ts);
    return d.toLocaleDateString('id-ID', { day: '2-digit', month: 'short' }) + ' ' +
      d.toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit', second: '2-digit' });
  } catch {
    return ts;
  }
};

// Filter clients based on selected project if any
const filteredClients = computed(() => {
  if (!filters.project_id) {
    return clients.value;
  }
  if (filters.project_id === 'none') {
    return clients.value.filter((c) => !c.project_client_id);
  }
  return clients.value.filter((c) => c.project_client_id === filters.project_id);
});

const handleProjectChange = () => {
  // If current client does not belong to new project filter, reset client filter
  if (filters.client_id) {
    const matched = filteredClients.value.find((c) => c.id === filters.client_id);
    if (!matched) filters.client_id = '';
  }
  filters.page = 1;
  fetchQueues();
};

const fetchProjects = async () => {
  try {
    const res = await axios.get('/api/v1/admin/projects');
    if (res.data.success) {
      projects.value = res.data.data;
    }
  } catch (e) {
    console.error('Failed to load projects for filter', e);
  }
};

const fetchClients = async () => {
  try {
    const res = await axios.get('/api/v1/admin/clients');
    if (res.data.success) {
      clients.value = res.data.data;
    }
  } catch (e) {
    console.error('Failed to load clients', e);
  }
};

const fetchQueues = async () => {
  loading.value = true;
  try {
    const params = {
      page: filters.page,
    };
    if (filters.search) params.search = filters.search;
    if (filters.project_id) params.project_id = filters.project_id;
    if (filters.client_id) params.client_id = filters.client_id;
    if (filters.status) params.status = filters.status;
    if (filters.type) params.type = filters.type;

    const res = await axios.get('/api/v1/admin/queues', { params });
    if (res.data.success) {
      queues.value = res.data.data || [];
      if (res.data.meta) {
        meta.current_page = res.data.meta.current_page || 1;
        meta.last_page = res.data.meta.last_page || 1;
        meta.total = res.data.meta.total || 0;
        meta.per_page = res.data.meta.per_page || 25;

        if (res.data.meta.stats) {
          stats.total = res.data.meta.stats.total || 0;
          stats.pending = res.data.meta.stats.pending || 0;
          stats.dispatched = res.data.meta.stats.dispatched || 0;
          stats.synced = res.data.meta.stats.synced || 0;
          stats.failed = res.data.meta.stats.failed || 0;
        }
      }
    }
  } catch (err) {
    console.error('Failed to load queues', err);
  } finally {
    loading.value = false;
  }
};

const changePage = (p) => {
  if (p < 1 || p > meta.last_page) return;
  filters.page = p;
  fetchQueues();
};

const resetFilters = () => {
  filters.search = '';
  filters.project_id = '';
  filters.client_id = '';
  filters.status = '';
  filters.type = '';
  filters.page = 1;
  fetchQueues();
};

const inspectPayload = (q) => {
  selectedQueue.value = q;
};

const copyText = async (text, label = 'Teks') => {
  try {
    await navigator.clipboard.writeText(text);
    alert(`${label} disalin ke clipboard!`);
  } catch (e) {}
};

const resendQueue = async (q) => {
  resendingId.value = q.id;
  try {
    const res = await axios.post(`/api/v1/admin/queues/${q.id}/resend`);
    if (res.data.success) {
      alert('Antrian berhasil dikirim ulang ke Reverb WebSocket!');
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

const deleteQueue = async (id) => {
  if (!confirm('Apakah Anda yakin ingin menghapus item antrian ini?')) return;

  try {
    const res = await axios.delete(`/api/v1/admin/queues/${id}`);
    if (res.data.success) {
      await fetchQueues();
    }
  } catch (err) {
    alert('Gagal menghapus antrian: ' + (err.response?.data?.message || err.message));
  }
};

onMounted(() => {
  fetchProjects();
  fetchClients();
  fetchQueues();
});
</script>
