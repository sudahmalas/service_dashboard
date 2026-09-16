<template>
  <div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
      <div>
        <h1 class="text-2xl font-extrabold text-white tracking-tight flex items-center gap-2">
          <span>Client Management & Provisioning</span>
        </h1>
        <p class="text-slate-400 text-xs sm:text-sm mt-1">
          Daftar aplikasi klien dan PC hardware yang terotorisasi untuk menerima perintah cetak secara aman.
        </p>
      </div>

      <div class="flex items-center gap-2.5">
        <button
          @click="openCreateModal"
          class="btn-primary"
        >
          <Plus class="w-4 h-4" />
          <span>Register New Client</span>
        </button>
        <button
          @click="fetchClients(true)"
          :disabled="loading"
          class="btn-secondary !p-2"
          title="Refresh Data Client"
        >
          <RefreshCw class="w-4 h-4" :class="{ 'animate-spin': loading }" />
        </button>
      </div>
    </div>

    <!-- Search & Scope Filter Toolbar -->
    <div class="card-panel p-4 rounded-2xl flex flex-col sm:flex-row items-center justify-between gap-3">
      <!-- Search Input -->
      <div class="relative w-full sm:w-80">
        <Search class="w-4 h-4 text-slate-500 absolute left-3 top-1/2 -translate-y-1/2" />
        <input
          v-model="searchQuery"
          type="text"
          placeholder="Cari client, node, IP, atau project..."
          class="w-full pl-9 pr-3 py-1.5 rounded-xl bg-slate-900/90 border border-slate-800 text-xs text-slate-200 placeholder-slate-500 focus:outline-none focus:border-indigo-500 transition-colors"
        />
      </div>

      <!-- Scope Filter Chips -->
      <div class="flex items-center gap-1.5 w-full sm:w-auto overflow-x-auto">
        <button
          @click="scopeFilter = 'all'"
          class="px-3 py-1 rounded-lg text-xs font-semibold transition-colors cursor-pointer"
          :class="scopeFilter === 'all' ? 'bg-indigo-600 text-white' : 'bg-slate-900 border border-slate-800 text-slate-400 hover:text-white'"
        >
          Semua Scope
        </button>
        <button
          @click="scopeFilter = 'printer_service'"
          class="px-3 py-1 rounded-lg text-xs font-semibold transition-colors cursor-pointer"
          :class="scopeFilter === 'printer_service' ? 'bg-purple-600 text-white' : 'bg-slate-900 border border-slate-800 text-slate-400 hover:text-white'"
        >
          Printer Service
        </button>
        <button
          @click="scopeFilter = 'prima'"
          class="px-3 py-1 rounded-lg text-xs font-semibold transition-colors cursor-pointer"
          :class="scopeFilter === 'prima' ? 'bg-sky-600 text-white' : 'bg-slate-900 border border-slate-800 text-slate-400 hover:text-white'"
        >
          Prima
        </button>
      </div>
    </div>

    <!-- Client Table Container (Zero Horizontal Scroll, 100% Responsive) -->
    <div class="card-panel rounded-2xl overflow-hidden shadow-xl">
      <!-- Desktop & Tablet Table (md and up) -->
      <div class="hidden md:block">
        <table class="w-full text-left text-xs sm:text-sm text-slate-300">
          <thead class="bg-slate-900/90 text-[11px] font-bold uppercase tracking-wider text-slate-400 border-b border-slate-800">
            <tr>
              <th class="px-4 py-3.5 w-[22%]">Client & Channel</th>
              <th class="px-4 py-3.5 w-[16%]">Project / Unit</th>
              <th class="px-4 py-3.5 w-[12%]">Scope</th>
              <th class="px-4 py-3.5 w-[15%]">Status Online</th>
              <th class="px-4 py-3.5 w-[17%]">Hardware Info</th>
              <th class="px-4 py-3.5 w-[10%]">API Key</th>
              <th class="px-4 py-3.5 text-right w-[8%]">Aksi</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-800/60 font-sans">
            <tr v-if="loading && clients.length === 0">
              <td colspan="7" class="px-6 py-12 text-center text-slate-400">
                <RefreshCw class="w-6 h-6 animate-spin mx-auto mb-2 text-indigo-400" />
                <span class="text-xs font-medium">Memuat data client...</span>
              </td>
            </tr>

            <tr v-else-if="filteredClients.length === 0">
              <td colspan="7" class="px-6 py-12 text-center text-slate-400">
                <Cpu class="w-8 h-8 mx-auto mb-2 text-slate-500" />
                <p class="font-medium text-slate-300 text-xs sm:text-sm">Tidak ada client yang cocok.</p>
                <button
                  @click="openCreateModal"
                  class="mt-3 text-xs text-indigo-400 hover:underline cursor-pointer"
                >
                  + Tambah Client Baru Sekarang
                </button>
              </td>
            </tr>

            <tr
              v-for="c in filteredClients"
              :key="c.id"
              class="hover:bg-slate-900/50 transition-colors duration-150 group"
            >
              <!-- Name & Slug -->
              <td class="px-4 py-3.5">
                <div class="font-bold text-white text-sm truncate" :title="c.name">{{ c.name }}</div>
                <div class="text-[11px] text-slate-400 font-mono mt-0.5 truncate">
                  <span class="text-indigo-400 font-semibold">channel:</span>
                  <span>printer.{{ c.slug }}</span>
                </div>
              </td>

              <!-- Project / Tenant Badge -->
              <td class="px-4 py-3.5">
                <div v-if="c.project" class="badge-indigo font-mono text-[11px]">
                  <FolderGit2 class="w-3 h-3 text-indigo-400 shrink-0" />
                  <span class="truncate">{{ c.project.code }}</span>
                </div>
                <span v-else class="text-xs text-slate-500 italic">
                  Belum Terhubung
                </span>
              </td>

              <!-- Scope Badge -->
              <td class="px-4 py-3.5">
                <span
                  class="text-[10px] font-semibold uppercase font-mono px-2 py-0.5 rounded-md inline-block"
                  :class="{
                    'bg-purple-500/10 text-purple-300 border border-purple-500/25': c.scope === 'printer_service',
                    'bg-sky-500/10 text-sky-300 border border-sky-500/25': c.scope === 'prima',
                    'bg-emerald-500/10 text-emerald-300 border border-emerald-500/25': c.scope === 'all',
                  }"
                >
                  {{ c.scope }}
                </span>
              </td>

              <!-- Online Status & Last Seen -->
              <td class="px-4 py-3.5">
                <div class="flex items-center gap-2">
                  <span class="relative flex h-2 w-2 shrink-0">
                    <span
                      v-if="c.is_online"
                      class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"
                    ></span>
                    <span
                      class="relative inline-flex rounded-full h-2 w-2"
                      :class="c.is_online ? 'bg-emerald-400' : 'bg-slate-600'"
                    ></span>
                  </span>
                  <span
                    class="font-semibold text-xs"
                    :class="c.is_online ? 'text-emerald-400' : 'text-slate-500'"
                  >
                    {{ c.is_online ? 'Online' : 'Offline' }}
                  </span>
                </div>
                <div class="text-[10px] text-slate-500 mt-0.5 font-mono truncate">
                  {{ c.last_seen_at ? c.last_seen_at : 'Belum pernah ping' }}
                </div>
              </td>

              <!-- Hardware Info -->
              <td class="px-4 py-3.5">
                <div class="text-xs text-slate-300 font-mono truncate">
                  <div>{{ c.machine_name || 'No Machine' }}</div>
                  <div class="text-slate-500 text-[11px]">{{ c.ip_address || 'No IP' }}</div>
                </div>
                <button
                  v-if="c.printers && c.printers.length"
                  @click="openPrintersModal(c)"
                  class="text-[11px] text-indigo-400 hover:text-indigo-300 mt-1 flex items-center gap-1 cursor-pointer font-medium"
                >
                  <Printer class="w-3 h-3" />
                  <span>{{ c.printers.length }} Printer Fisik</span>
                </button>
              </td>

              <!-- API Key -->
              <td class="px-4 py-3.5">
                <div class="flex items-center gap-1">
                  <button
                    @click="copyKey(c.api_key)"
                    class="p-1.5 rounded-lg bg-slate-900 border border-slate-800 text-slate-300 hover:text-indigo-300 cursor-pointer"
                    title="Copy API Key"
                  >
                    <Copy class="w-3.5 h-3.5" />
                  </button>
                  <button
                    @click="toggleKeyVisibility(c.id)"
                    class="p-1.5 rounded-lg bg-slate-900 border border-slate-800 text-slate-400 hover:text-white cursor-pointer"
                    :title="visibleKeys[c.id] ? 'Sembunyikan' : 'Intip Key'"
                  >
                    <EyeOff v-if="visibleKeys[c.id]" class="w-3.5 h-3.5" />
                    <Eye v-else class="w-3.5 h-3.5" />
                  </button>
                </div>
                <div v-if="visibleKeys[c.id]" class="text-[10px] font-mono text-slate-300 mt-1 truncate select-all">
                  {{ c.api_key }}
                </div>
              </td>

              <!-- Actions -->
              <td class="px-4 py-3.5 text-right whitespace-nowrap">
                <div class="flex items-center justify-end gap-1.5">
                  <button
                    v-if="c.printers && c.printers.length"
                    @click="openPrintersModal(c)"
                    class="px-2 py-1 rounded-lg bg-slate-900 hover:bg-slate-800 border border-slate-800 text-xs text-slate-300 hover:text-white transition-colors inline-flex items-center gap-1 cursor-pointer font-medium"
                    title="Periksa Printer Fisik"
                  >
                    <Printer class="w-3.5 h-3.5 text-indigo-400" />
                    <span>Printer</span>
                  </button>
                  <button
                    @click="confirmRegenerateKey(c)"
                    class="px-2 py-1 rounded-lg bg-slate-900 hover:bg-slate-800 border border-slate-800 text-xs text-amber-400 hover:text-amber-300 transition-colors inline-flex items-center gap-1 cursor-pointer font-medium"
                    title="Generate API Key Baru"
                  >
                    <Key class="w-3.5 h-3.5 text-amber-400" />
                    <span>New Key</span>
                  </button>
                  <button
                    @click="deleteClient(c)"
                    class="p-1.5 rounded-lg bg-slate-900 hover:bg-rose-950/50 border border-slate-800 hover:border-rose-900/50 text-slate-400 hover:text-rose-400 transition-colors cursor-pointer"
                    title="Hapus Client"
                  >
                    <Trash2 class="w-3.5 h-3.5" />
                  </button>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- Mobile Client Cards (< md) -->
      <div class="md:hidden divide-y divide-slate-800/60">
        <div
          v-for="c in filteredClients"
          :key="c.id"
          class="p-4 space-y-3 bg-slate-900/40"
        >
          <div class="flex items-center justify-between">
            <div>
              <div class="font-bold text-white text-sm">{{ c.name }}</div>
              <div class="text-[11px] font-mono text-indigo-400">printer.{{ c.slug }}</div>
            </div>
            <span
              class="px-2 py-0.5 rounded text-[10px] font-mono uppercase font-bold"
              :class="c.is_online ? 'bg-emerald-500/10 text-emerald-400 border border-emerald-500/20' : 'bg-slate-800 text-slate-400'"
            >
              {{ c.is_online ? 'Online' : 'Offline' }}
            </span>
          </div>

          <div class="flex items-center justify-between text-xs text-slate-400">
            <span v-if="c.project" class="badge-indigo font-mono text-[10px]">{{ c.project.code }}</span>
            <span v-else class="italic">Unassigned</span>
            <span class="font-mono text-[11px]">{{ c.machine_name || 'No machine' }}</span>
          </div>

          <div class="flex items-center justify-end gap-2 pt-2 border-t border-slate-800">
            <button
              @click="copyKey(c.api_key)"
              class="px-3 py-1 rounded-lg bg-slate-800 text-xs text-slate-200"
            >
              Copy Key
            </button>
            <button
              @click="confirmRegenerateKey(c)"
              class="px-3 py-1 rounded-lg bg-slate-800 text-xs text-amber-400"
            >
              New Key
            </button>
          </div>
        </div>
      </div>
    </div>


    <!-- Create Client Modal -->
    <div
      v-if="showCreateModal"
      class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/80 backdrop-blur-sm"
      @click.self="showCreateModal = false"
    >
      <div class="card-panel border border-slate-700/80 rounded-2xl max-w-md w-full p-6 shadow-2xl space-y-4 bg-[#0d1424]">
        <div class="flex items-center justify-between pb-3 border-b border-slate-800">
          <h3 class="text-sm sm:text-base font-bold text-white flex items-center gap-2">
            <Plus class="w-4 h-4 text-indigo-400" />
            <span>Register New Client</span>
          </h3>
          <button @click="showCreateModal = false" class="text-slate-400 hover:text-white cursor-pointer">✕</button>
        </div>

        <form @submit.prevent="submitCreateClient" class="space-y-4">
          <div>
            <label class="block text-xs font-semibold text-slate-300 mb-1">Nama Client</label>
            <input
              type="text"
              v-model="createForm.name"
              class="input-field"
              placeholder="Contoh: Loket Pendaftaran 2 / Kasir Prima Utama"
              required
            />
          </div>

          <div>
            <label class="block text-xs font-semibold text-slate-300 mb-1">Channel Slug (Opsional)</label>
            <input
              type="text"
              v-model="createForm.slug"
              class="input-field font-mono"
              placeholder="Otomatis dari nama (contoh: Loket-Pendaftaran-2)"
            />
          </div>

          <div>
            <label class="block text-xs font-semibold text-slate-300 mb-1">Assigned Scope</label>
            <select
              v-model="createForm.scope"
              class="input-field"
            >
              <option value="printer_service">Printer Service (Hardware Printer Executor)</option>
              <option value="prima">Prima (Business / Input Application)</option>
              <option value="all">Universal / All Scopes</option>
            </select>
          </div>

          <div>
            <label class="block text-xs font-semibold text-slate-300 mb-1">Alokasikan ke Project / Unit (Opsional)</label>
            <select
              v-model="createForm.project_client_id"
              class="input-field"
            >
              <option value="">-- Tanpa Project (Bebas / Unassigned) --</option>
              <option v-for="p in projects" :key="p.id" :value="p.id">
                {{ p.name }} ({{ p.code }}) - Kuota: {{ p.printers_count }}/{{ p.max_printers }}
              </option>
            </select>
          </div>

          <p class="text-[11px] text-slate-400 leading-relaxed font-mono">
            API Key acak dengan prefix <code class="text-indigo-300 font-bold">ps_</code> akan digenerate otomatis saat disimpan.
          </p>

          <div class="flex items-center justify-end gap-2.5 pt-3 border-t border-slate-800">
            <button
              type="button"
              @click="showCreateModal = false"
              class="btn-ghost"
            >
              Batal
            </button>
            <button
              type="submit"
              :disabled="saving"
              class="btn-primary"
            >
              {{ saving ? 'Menyimpan...' : 'Daftarkan Client' }}
            </button>
          </div>
        </form>
      </div>
    </div>

    <!-- Printers Mapping Modal -->
    <div
      v-if="selectedClientForPrinters"
      class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/80 backdrop-blur-sm"
      @click.self="selectedClientForPrinters = null"
    >
      <div class="card-panel border border-slate-700/80 rounded-2xl max-w-lg w-full p-6 shadow-2xl space-y-4 bg-[#0d1424]">
        <div class="flex items-center justify-between pb-3 border-b border-slate-800">
          <div>
            <h3 class="text-sm sm:text-base font-bold text-white flex items-center gap-2">
              <Printer class="w-4 h-4 text-indigo-400" />
              <span>Printer Fisik: {{ selectedClientForPrinters.name }}</span>
            </h3>
            <p class="text-xs text-slate-400 mt-0.5">Sinkron dari OS Windows via Electron Print Service</p>
          </div>
          <button @click="selectedClientForPrinters = null" class="text-slate-400 hover:text-white cursor-pointer">✕</button>
        </div>

        <div class="space-y-2.5 max-h-[350px] overflow-y-auto pr-1">
          <div
            v-for="(p, i) in selectedClientForPrinters.printers"
            :key="i"
            class="p-3 rounded-xl border border-slate-800 bg-slate-900/60"
          >
            <div class="font-semibold text-slate-200 text-xs sm:text-sm">
              {{ p.printer_name || p.os_printer_name }}
            </div>
            <div class="mt-2 flex flex-wrap gap-1">
              <span class="text-[11px] text-slate-400 mr-1">Target Labels:</span>
              <span
                v-for="(t, idx) in (p.target_labels || [])"
                :key="idx"
                class="px-2 py-0.5 rounded text-[10px] font-mono bg-indigo-500/10 text-indigo-300 border border-indigo-500/20"
              >
                {{ t }}
              </span>
              <span v-if="!p.target_labels || p.target_labels.length === 0" class="text-xs text-slate-500 italic">
                Semua kategori (default)
              </span>
            </div>
          </div>
        </div>

        <div class="pt-3 border-t border-slate-800 text-right">
          <button
            @click="selectedClientForPrinters = null"
            class="btn-secondary"
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
import { Plus, RefreshCw, Cpu, Printer, Eye, EyeOff, Copy, FolderGit2, Search, Key, Trash2 } from 'lucide-vue-next';
import { useToast } from '../composables/useToast';

const toast = useToast();
const loading = ref(false);
const saving = ref(false);
const clients = ref([]);
const projects = ref([]);
const visibleKeys = reactive({});
const showCreateModal = ref(false);
const selectedClientForPrinters = ref(null);
const searchQuery = ref('');
const scopeFilter = ref('all');

const createForm = reactive({
  name: '',
  slug: '',
  scope: 'printer_service',
  project_client_id: '',
});

const filteredClients = computed(() => {
  return clients.value.filter((c) => {
    // Filter scope
    if (scopeFilter.value !== 'all' && c.scope !== scopeFilter.value) {
      return false;
    }
    // Filter search query
    if (searchQuery.value.trim()) {
      const q = searchQuery.value.toLowerCase();
      const matchName = c.name?.toLowerCase().includes(q);
      const matchSlug = c.slug?.toLowerCase().includes(q);
      const matchMachine = c.machine_name?.toLowerCase().includes(q);
      const matchIp = c.ip_address?.toLowerCase().includes(q);
      const matchProject = c.project?.name?.toLowerCase().includes(q) || c.project?.code?.toLowerCase().includes(q);
      return matchName || matchSlug || matchMachine || matchIp || matchProject;
    }
    return true;
  });
});

const maskKey = (key) => {
  if (!key) return '••••••••';
  return key.substring(0, 7) + '••••••••';
};

const toggleKeyVisibility = (id) => {
  visibleKeys[id] = !visibleKeys[id];
};

const copyKey = async (text) => {
  try {
    await navigator.clipboard.writeText(text);
    toast.success('API Key berhasil disalin ke clipboard!');
  } catch (err) {
    toast.error('Gagal menyalin API Key.');
  }
};

const fetchClients = async (isManual = false) => {
  loading.value = true;
  try {
    const [resClients, resProj] = await Promise.all([
      axios.get('/api/v1/admin/clients'),
      axios.get('/api/v1/admin/projects'),
    ]);
    if (resClients.data.success) {
      clients.value = resClients.data.data;
    }
    if (resProj.data.success) {
      projects.value = resProj.data.data;
    }
    if (isManual) {
      toast.success('Data client berhasil diperbarui.');
    }
  } catch (err) {
    console.error('Failed to fetch clients & projects', err);
    if (isManual) {
      toast.error('Gagal memuat data client.');
    }
  } finally {
    loading.value = false;
  }
};

const openCreateModal = () => {
  createForm.name = '';
  createForm.slug = '';
  createForm.scope = 'printer_service';
  createForm.project_client_id = '';
  showCreateModal.value = true;
};

const submitCreateClient = async () => {
  saving.value = true;
  try {
    const payload = {
      name: createForm.name,
      slug: createForm.slug || undefined,
      scope: createForm.scope,
      project_client_id: createForm.project_client_id || undefined,
    };
    const res = await axios.post('/api/v1/admin/clients', payload);
    if (res.data.success) {
      showCreateModal.value = false;
      await fetchClients();
      toast.success(`Client "${res.data.data.name}" berhasil didaftarkan!`);
    }
  } catch (err) {
    toast.error('Gagal mendaftarkan client: ' + (err.response?.data?.message || err.message));
  } finally {
    saving.value = false;
  }
};

const confirmRegenerateKey = async (client) => {
  if (!confirm(`Generate API Key baru untuk "${client.name}"?\n\nKoneksi lama client ini akan terputus sampai API Key diupdate di aplikasi client.`)) {
    return;
  }
  try {
    const res = await axios.post(`/api/v1/admin/clients/${client.id}/regenerate-key`);
    if (res.data.success) {
      await fetchClients();
      visibleKeys[client.id] = true;
      toast.success('API Key baru berhasil digenerate!');
    }
  } catch (err) {
    toast.error('Gagal regenerate key: ' + (err.response?.data?.message || err.message));
  }
};

const deleteClient = async (client) => {
  if (!confirm(`Hapus client "${client.name}"?\nSemua data antrian terkait juga akan dihapus.`)) {
    return;
  }
  try {
    const res = await axios.delete(`/api/v1/admin/clients/${client.id}`);
    if (res.data.success) {
      await fetchClients();
      toast.success(`Client "${client.name}" berhasil dihapus.`);
    }
  } catch (err) {
    toast.error('Gagal menghapus client: ' + (err.response?.data?.message || err.message));
  }
};

const openPrintersModal = (client) => {
  selectedClientForPrinters.value = client;
};

onMounted(() => {
  fetchClients();
});
</script>
