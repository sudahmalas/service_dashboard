<template>
  <div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
      <div>
        <h1 class="text-2xl font-extrabold text-white tracking-tight flex items-center gap-2">
          <span>Client Management & Provisioning</span>
        </h1>
        <p class="text-slate-400 text-sm mt-1">
          Daftar aplikasi klien yang terotorisasi untuk menerima perintah cetak dan input form secara aman.
        </p>
      </div>

      <div class="flex items-center gap-3">
        <button
          @click="openCreateModal"
          class="px-4 py-2 rounded-xl bg-indigo-600 hover:bg-indigo-500 text-white text-sm font-semibold shadow-lg shadow-indigo-600/30 transition-all duration-150 flex items-center gap-2 cursor-pointer"
        >
          <Plus class="w-4 h-4" />
          <span>Register New Client</span>
        </button>
        <button
          @click="fetchClients"
          :disabled="loading"
          class="p-2 rounded-xl bg-slate-900 border border-slate-800 hover:bg-slate-800 text-slate-300 transition-all cursor-pointer"
        >
          <RefreshCw class="w-4 h-4" :class="{ 'animate-spin': loading }" />
        </button>
      </div>
    </div>

    <!-- Client Table Container -->
    <div class="glass-panel rounded-2xl border border-slate-800 overflow-hidden shadow-xl">
      <div class="overflow-x-auto">
        <table class="w-full text-left text-sm text-slate-300">
          <thead class="bg-slate-900/80 text-xs font-semibold uppercase text-slate-400 border-b border-slate-800">
            <tr>
              <th class="px-6 py-4">Client Name & Channel</th>
              <th class="px-6 py-4">Project / Unit</th>
              <th class="px-6 py-4">Scope</th>
              <th class="px-6 py-4">Online Status</th>
              <th class="px-6 py-4">Hardware Info</th>
              <th class="px-6 py-4">API Key</th>
              <th class="px-6 py-4 text-right">Actions</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-800/60">
            <tr v-if="loading && clients.length === 0">
              <td colspan="7" class="px-6 py-12 text-center text-slate-400">
                <RefreshCw class="w-6 h-6 animate-spin mx-auto mb-2 text-indigo-400" />
                <span>Memuat data client...</span>
              </td>
            </tr>

            <tr v-else-if="clients.length === 0">
              <td colspan="7" class="px-6 py-12 text-center text-slate-400">
                <Cpu class="w-8 h-8 mx-auto mb-2 text-slate-400" />
                <p>Belum ada client terdaftar.</p>
                <button
                  @click="openCreateModal"
                  class="mt-3 text-xs text-indigo-400 hover:underline"
                >
                  + Tambah Client Baru Sekarang
                </button>
              </td>
            </tr>

            <tr
              v-for="c in clients"
              :key="c.id"
              class="hover:bg-slate-900/50 transition-colors duration-150"
            >
              <!-- Name & Slug -->
              <td class="px-6 py-4">
                <div class="font-bold text-white text-base">{{ c.name }}</div>
                <div class="text-xs text-slate-400 font-mono flex items-center gap-1 mt-0.5">
                  <span class="text-indigo-400">channel:</span>
                  <span>printer.{{ c.slug }}</span>
                </div>
              </td>

              <!-- Project / Tenant Badge -->
              <td class="px-6 py-4">
                <div v-if="c.project" class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg text-xs font-mono font-medium bg-indigo-500/10 text-indigo-300 border border-indigo-500/20">
                  <FolderGit2 class="w-3 h-3 text-indigo-400" />
                  <span>{{ c.project.code }}</span>
                </div>
                <span v-else class="text-xs text-slate-400 italic">
                  Belum Terhubung
                </span>
              </td>

              <!-- Scope Badge -->
              <td class="px-6 py-4">
                <span
                  class="px-2.5 py-1 rounded-lg text-xs font-semibold uppercase font-mono inline-block"
                  :class="{
                    'bg-purple-500/10 text-purple-300 border border-purple-500/20': c.scope === 'printer_service',
                    'bg-sky-500/10 text-sky-300 border border-sky-500/20': c.scope === 'prima',
                    'bg-emerald-500/10 text-emerald-300 border border-emerald-500/20': c.scope === 'all',
                  }"
                >
                  {{ c.scope }}
                </span>
              </td>

              <!-- Online Status & Last Seen -->
              <td class="px-6 py-4">
                <div class="flex items-center gap-2">
                  <span class="relative flex h-2.5 w-2.5">
                    <span
                      v-if="c.is_online"
                      class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"
                    ></span>
                    <span
                      class="relative inline-flex rounded-full h-2.5 w-2.5"
                      :class="c.is_online ? 'bg-emerald-500' : 'bg-slate-600'"
                    ></span>
                  </span>
                  <span
                    class="font-medium text-xs"
                    :class="c.is_online ? 'text-emerald-400' : 'text-slate-400'"
                  >
                    {{ c.is_online ? 'Online' : 'Offline' }}
                  </span>
                </div>
                <div class="text-[11px] text-slate-400 mt-1">
                  Last seen: {{ c.last_seen_at }}
                </div>
              </td>

              <!-- Hardware Info -->
              <td class="px-6 py-4">
                <div class="text-xs text-slate-300 font-mono">
                  <div>{{ c.machine_name || 'No Machine Name' }}</div>
                  <div class="text-slate-400">{{ c.ip_address || 'No IP' }}</div>
                </div>
                <button
                  v-if="c.printers && c.printers.length"
                  @click="openPrintersModal(c)"
                  class="text-[11px] text-indigo-400 hover:text-indigo-300 mt-1 flex items-center gap-1 cursor-pointer"
                >
                  <Printer class="w-3 h-3" />
                  <span>{{ c.printers.length }} Printer Fisik Terpetakan</span>
                </button>
              </td>

              <!-- API Key -->
              <td class="px-6 py-4">
                <div class="flex items-center gap-2">
                  <span class="font-mono text-xs bg-slate-900 px-2.5 py-1 rounded-md border border-slate-800 select-all max-w-[150px] truncate text-slate-300">
                    {{ visibleKeys[c.id] ? c.api_key : maskKey(c.api_key) }}
                  </span>
                  <button
                    @click="toggleKeyVisibility(c.id)"
                    class="text-slate-400 hover:text-slate-200 p-1"
                    title="Toggle Visibility"
                  >
                    <Eye v-if="!visibleKeys[c.id]" class="w-3.5 h-3.5" />
                    <EyeOff v-else class="w-3.5 h-3.5" />
                  </button>
                  <button
                    @click="copyToClipboard(c.api_key)"
                    class="text-slate-400 hover:text-indigo-300 p-1"
                    title="Copy API Key"
                  >
                    <Copy class="w-3.5 h-3.5" />
                  </button>
                </div>
              </td>

              <!-- Actions -->
              <td class="px-6 py-4 text-right space-x-2">
                <button
                  @click="confirmRegenerateKey(c)"
                  class="px-2.5 py-1 rounded-lg bg-slate-900 hover:bg-slate-800 border border-slate-800 text-xs text-amber-300 transition-colors"
                  title="Generate API Key Baru"
                >
                  New Key
                </button>
                <button
                  @click="deleteClient(c)"
                  class="px-2.5 py-1 rounded-lg bg-rose-950/40 hover:bg-rose-900/60 border border-rose-800/40 text-xs text-rose-300 transition-colors"
                  title="Hapus Client"
                >
                  Delete
                </button>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Create Client Modal -->
    <div v-if="showCreateModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/80 backdrop-blur-sm">
      <div class="glass-panel border border-slate-800 rounded-2xl max-w-md w-full p-6 shadow-2xl space-y-4">
        <div class="flex items-center justify-between pb-3 border-b border-slate-800">
          <h3 class="text-base font-bold text-white flex items-center gap-2">
            <Plus class="w-5 h-5 text-indigo-400" />
            <span>Register New Client</span>
          </h3>
          <button @click="showCreateModal = false" class="text-slate-400 hover:text-white">✕</button>
        </div>

        <form @submit.prevent="submitCreateClient" class="space-y-4">
          <div>
            <label class="block text-xs font-medium text-slate-300 mb-1">Nama Client</label>
            <input
              type="text"
              v-model="createForm.name"
              class="w-full rounded-xl bg-slate-900 border border-slate-800 text-sm text-slate-100 p-2.5 focus:border-indigo-500"
              placeholder="Contoh: Loket Pendaftaran 2 / Kasir Prima Utama"
              required
            />
          </div>

          <div>
            <label class="block text-xs font-medium text-slate-300 mb-1">Channel Slug (Opsional)</label>
            <input
              type="text"
              v-model="createForm.slug"
              class="w-full rounded-xl bg-slate-900 border border-slate-800 text-sm text-slate-100 p-2.5 focus:border-indigo-500"
              placeholder="Otomatis dari nama (contoh: Loket-Pendaftaran-2)"
            />
          </div>

          <div>
            <label class="block text-xs font-medium text-slate-300 mb-1">Assigned Scope</label>
            <select
              v-model="createForm.scope"
              class="w-full rounded-xl bg-slate-900 border border-slate-800 text-sm text-slate-100 p-2.5 focus:border-indigo-500"
            >
              <option value="printer_service">Printer Service (Hardware Printer Executor)</option>
              <option value="prima">Prima (Business / Input Application)</option>
              <option value="all">Universal / All Scopes</option>
            </select>
          </div>

          <div>
            <label class="block text-xs font-medium text-slate-300 mb-1">Alokasikan ke Project / Unit (Opsional)</label>
            <select
              v-model="createForm.project_client_id"
              class="w-full rounded-xl bg-slate-900 border border-slate-800 text-sm text-slate-100 p-2.5 focus:border-indigo-500"
            >
              <option value="">-- Tanpa Project (Bebas / Unassigned) --</option>
              <option v-for="p in projects" :key="p.id" :value="p.id">
                {{ p.name }} ({{ p.code }}) - Kuota: {{ p.printers_count }}/{{ p.max_printers }}
              </option>
            </select>
          </div>

          <p class="text-xs text-slate-400 leading-relaxed">
            API Key acak dengan prefix <code class="text-indigo-300">ps_</code> akan digenerate otomatis saat disimpan.
          </p>

          <div class="flex items-center justify-end gap-3 pt-3 border-t border-slate-800">
            <button
              type="button"
              @click="showCreateModal = false"
              class="px-4 py-2 rounded-xl text-sm text-slate-400 hover:text-slate-200"
            >
              Batal
            </button>
            <button
              type="submit"
              :disabled="saving"
              class="px-5 py-2 rounded-xl bg-indigo-600 hover:bg-indigo-500 text-white text-sm font-semibold shadow-lg shadow-indigo-600/30"
            >
              {{ saving ? 'Menyimpan...' : 'Daftarkan Client' }}
            </button>
          </div>
        </form>
      </div>
    </div>

    <!-- Printers Mapping Modal -->
    <div v-if="selectedClientForPrinters" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/80 backdrop-blur-sm">
      <div class="glass-panel border border-slate-800 rounded-2xl max-w-lg w-full p-6 shadow-2xl space-y-4">
        <div class="flex items-center justify-between pb-3 border-b border-slate-800">
          <div>
            <h3 class="text-base font-bold text-white flex items-center gap-2">
              <Printer class="w-5 h-5 text-indigo-400" />
              <span>Printer Fisik Terdaftar: {{ selectedClientForPrinters.name }}</span>
            </h3>
            <p class="text-xs text-slate-400">Sinkron dari OS Windows via Electron Print Service</p>
          </div>
          <button @click="selectedClientForPrinters = null" class="text-slate-400 hover:text-white">✕</button>
        </div>

        <div class="space-y-3 max-h-[350px] overflow-y-auto">
          <div
            v-for="(p, i) in selectedClientForPrinters.printers"
            :key="i"
            class="p-3 rounded-xl border border-slate-800 bg-slate-900/60"
          >
            <div class="font-semibold text-slate-200 text-sm">
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
            class="px-4 py-2 rounded-xl bg-slate-900 border border-slate-800 text-sm text-slate-300 hover:bg-slate-800"
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
import { Plus, RefreshCw, Cpu, Printer, Eye, EyeOff, Copy, FolderGit2 } from 'lucide-vue-next';

const loading = ref(false);
const saving = ref(false);
const clients = ref([]);
const projects = ref([]);
const visibleKeys = reactive({});
const showCreateModal = ref(false);
const selectedClientForPrinters = ref(null);

const createForm = reactive({
  name: '',
  slug: '',
  scope: 'printer_service',
  project_client_id: '',
});

const maskKey = (key) => {
  if (!key) return '••••••••';
  return key.substring(0, 7) + '••••••••';
};

const toggleKeyVisibility = (id) => {
  visibleKeys[id] = !visibleKeys[id];
};

const copyToClipboard = async (text) => {
  try {
    await navigator.clipboard.writeText(text);
    alert('API Key disalin ke clipboard!');
  } catch (err) {
    console.error('Failed to copy', err);
  }
};

const fetchClients = async () => {
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
  } catch (err) {
    console.error('Failed to fetch clients & projects', err);
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
      alert(`Client berhasil didaftarkan!\n\nAPI Key: ${res.data.data.api_key}\n\nHarap simpan API Key ini.`);
    }
  } catch (err) {
    alert('Gagal mendaftarkan client: ' + (err.response?.data?.message || err.message));
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
      alert(`API Key Baru: ${res.data.data.api_key}`);
    }
  } catch (err) {
    alert('Gagal regenerate key: ' + (err.response?.data?.message || err.message));
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
    }
  } catch (err) {
    alert('Gagal menghapus client: ' + (err.response?.data?.message || err.message));
  }
};

const openPrintersModal = (client) => {
  selectedClientForPrinters.value = client;
};

onMounted(() => {
  fetchClients();
});
</script>
