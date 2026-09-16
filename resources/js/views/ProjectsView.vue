<template>
  <div class="space-y-6">
    <!-- Header Section -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
      <div>
        <h1 class="text-2xl font-extrabold text-white tracking-tight flex items-center gap-2">
          <span>Project & Tenant Management</span>
        </h1>
        <p class="text-slate-400 text-xs sm:text-sm mt-1">
          Kelola aplikasi klien (Prima Inventaris, CSSD, dll), kuota printer per unit, serta isolasi API Key antar rumah sakit.
        </p>
      </div>

      <div class="flex items-center gap-2.5">
        <button
          @click="openCreateModal"
          class="btn-primary"
        >
          <Plus class="w-4 h-4" />
          <span>Tambah Project Baru</span>
        </button>
        <button
          @click="fetchProjects(true)"
          :disabled="loading"
          class="btn-secondary !p-2"
          title="Refresh Data Project"
        >
          <RefreshCw class="w-4 h-4" :class="{ 'animate-spin': loading }" />
        </button>
      </div>
    </div>

    <!-- Quick Stats Cards (Anti-Slop Grid) -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
      <div class="card-panel p-5 rounded-2xl flex items-center gap-4">
        <div class="w-11 h-11 rounded-xl bg-indigo-500/10 border border-indigo-500/20 flex items-center justify-center text-indigo-400 shrink-0">
          <FolderGit2 class="w-5 h-5" />
        </div>
        <div>
          <div class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Total Projects</div>
          <div class="text-2xl font-bold text-white font-mono mt-1">{{ projects.length }}</div>
        </div>
      </div>

      <div class="card-panel p-5 rounded-2xl flex items-center gap-4">
        <div class="w-11 h-11 rounded-xl bg-emerald-500/10 border border-emerald-500/20 flex items-center justify-center text-emerald-400 shrink-0">
          <Printer class="w-5 h-5" />
        </div>
        <div>
          <div class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Printer Teralokasi</div>
          <div class="text-2xl font-bold text-emerald-400 font-mono mt-1">{{ totalAllocatedPrinters }}</div>
        </div>
      </div>

      <div class="card-panel p-5 rounded-2xl flex items-center gap-4">
        <div class="w-11 h-11 rounded-xl bg-cyan-500/10 border border-cyan-500/20 flex items-center justify-center text-cyan-400 shrink-0">
          <Layers class="w-5 h-5" />
        </div>
        <div>
          <div class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Kapasitas Kuota</div>
          <div class="text-2xl font-bold text-cyan-300 font-mono mt-1">{{ totalMaxPrinters }}</div>
        </div>
      </div>

      <div class="card-panel p-5 rounded-2xl flex items-center gap-4">
        <div class="w-11 h-11 rounded-xl bg-purple-500/10 border border-purple-500/20 flex items-center justify-center text-purple-400 shrink-0">
          <ShieldCheck class="w-5 h-5" />
        </div>
        <div>
          <div class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Isolasi Keamanan</div>
          <div class="text-xs font-bold text-purple-300 mt-1 font-mono uppercase">Multi-Tenant Aktif</div>
        </div>
      </div>
    </div>

    <!-- Projects List Cards -->
    <div v-if="loading && projects.length === 0" class="card-panel rounded-2xl p-16 text-center text-slate-400">
      <RefreshCw class="w-7 h-7 animate-spin mx-auto mb-3 text-indigo-400" />
      <span class="text-sm font-medium">Memuat data project & tenant...</span>
    </div>

    <div v-else-if="projects.length === 0" class="card-panel rounded-2xl p-16 text-center text-slate-400">
      <div class="w-12 h-12 rounded-2xl bg-slate-900 border border-slate-800 flex items-center justify-center mx-auto mb-3 text-slate-500">
        <FolderGit2 class="w-6 h-6 text-indigo-400" />
      </div>
      <h3 class="text-base font-bold text-slate-200">Belum ada project client terdaftar</h3>
      <p class="text-xs text-slate-400 max-w-md mx-auto mt-1 leading-relaxed">
        Daftarkan aplikasi klien (seperti Prima Inventaris atau Prima CSSD) agar dapat menggunakan printer fisik yang dialokasikan.
      </p>
      <button
        @click="openCreateModal"
        class="btn-primary mt-4 text-xs"
      >
        <Plus class="w-3.5 h-3.5" />
        <span>Daftarkan Project Pertama</span>
      </button>
    </div>

    <div v-else class="grid grid-cols-1 lg:grid-cols-2 gap-5">
      <div
        v-for="project in projects"
        :key="project.id"
        class="card-panel rounded-2xl p-5 sm:p-6 flex flex-col justify-between transition-all duration-200 hover:border-slate-700"
      >
        <div>
          <!-- Card Header: Title, Code, & Actions -->
          <div class="flex items-start justify-between gap-3">
            <div class="space-y-1 min-w-0">
              <div class="flex items-center gap-2 flex-wrap">
                <h3 class="text-base sm:text-lg font-bold text-white tracking-tight truncate">{{ project.name }}</h3>
                <span class="badge-indigo font-mono text-[11px]">
                  {{ project.code }}
                </span>
                <span
                  class="text-[10px] font-mono font-bold uppercase tracking-wider px-2 py-0.5 rounded-full"
                  :class="project.status === 'active' ? 'bg-emerald-500/10 text-emerald-400 border border-emerald-500/25' : 'bg-rose-500/10 text-rose-400 border border-rose-500/25'"
                >
                  {{ project.status }}
                </span>
              </div>
              <p class="text-xs text-slate-400 leading-relaxed line-clamp-2">
                {{ project.description || 'Tidak ada deskripsi khusus.' }}
              </p>
            </div>

            <!-- Project Actions -->
            <div class="flex items-center gap-1.5 shrink-0">
              <button
                @click="openEditModal(project)"
                class="p-1.5 rounded-lg bg-slate-900 border border-slate-800 text-slate-300 hover:text-white hover:bg-slate-800 transition-colors cursor-pointer"
                title="Edit Project"
              >
                <Edit3 class="w-3.5 h-3.5" />
              </button>
              <button
                @click="deleteProject(project)"
                class="p-1.5 rounded-lg bg-rose-950/30 border border-rose-800/40 text-rose-400 hover:bg-rose-900/50 transition-colors cursor-pointer"
                title="Hapus Project"
              >
                <Trash2 class="w-3.5 h-3.5" />
              </button>
            </div>
          </div>

          <!-- Project API Key Display -->
          <div class="mt-4 p-3 rounded-xl bg-slate-900/90 border border-slate-800 space-y-1.5">
            <div class="flex items-center justify-between text-[11px] text-slate-400">
              <span class="font-medium flex items-center gap-1.5">
                <Key class="w-3.5 h-3.5 text-amber-400" />
                <span>Project API Key (<code class="text-indigo-300 font-mono">X-Project-Key</code>)</span>
              </span>
              <button
                @click="confirmRegenerateKey(project)"
                class="text-[10px] text-amber-400 hover:text-amber-300 underline cursor-pointer font-mono"
              >
                Regenerate
              </button>
            </div>
            <div class="flex items-center gap-2">
              <span class="font-mono text-xs text-slate-200 select-all flex-1 truncate bg-[#080c14] px-2 py-1 rounded border border-slate-800">
                {{ visibleKeys[project.id] ? project.api_key : maskKey(project.api_key) }}
              </span>
              <button
                @click="toggleKeyVisibility(project.id)"
                class="text-slate-400 hover:text-slate-200 p-1 cursor-pointer"
                :title="visibleKeys[project.id] ? 'Sembunyikan' : 'Tampilkan'"
              >
                <EyeOff v-if="visibleKeys[project.id]" class="w-3.5 h-3.5" />
                <Eye v-else class="w-3.5 h-3.5" />
              </button>
              <button
                @click="copyKey(project.api_key)"
                class="text-slate-400 hover:text-indigo-300 p-1 cursor-pointer"
                title="Salin Key"
              >
                <Copy class="w-3.5 h-3.5" />
              </button>
            </div>
          </div>

          <!-- Quota Progress Indicator -->
          <div class="mt-4 space-y-1.5">
            <div class="flex items-center justify-between text-xs">
              <span class="font-medium text-slate-300 flex items-center gap-1">
                <Printer class="w-3.5 h-3.5 text-indigo-400" />
                <span>Alokasi Printer Fisik</span>
              </span>
              <span class="font-bold font-mono text-xs" :class="getQuotaTextColor(project)">
                {{ project.printers_count }} / {{ project.max_printers }} Printer
                ({{ Math.round((project.printers_count / project.max_printers) * 100) }}%)
              </span>
            </div>
            <!-- Progress Bar -->
            <div class="w-full h-2 rounded-full bg-slate-900 border border-slate-800 overflow-hidden">
              <div
                class="h-full rounded-full transition-all duration-300"
                :class="getQuotaProgressColor(project)"
                :style="{ width: `${Math.min(100, Math.round((project.printers_count / project.max_printers) * 100))}%` }"
              ></div>
            </div>
          </div>

          <!-- Allocated Printers List -->
          <div class="mt-4 space-y-2">
            <div class="flex items-center justify-between">
              <span class="text-[11px] font-semibold uppercase tracking-wider text-slate-400">
                Hardware Terpetakan ({{ project.printers.length }})
              </span>
              <button
                v-if="project.printers_count < project.max_printers"
                @click="openAssignModal(project)"
                class="text-[11px] text-indigo-400 hover:text-indigo-300 flex items-center gap-1 font-semibold cursor-pointer"
              >
                <Plus class="w-3 h-3" />
                <span>Alokasikan Printer</span>
              </button>
              <span v-else class="text-[10px] text-amber-400 font-mono font-medium">
                Kuota Penuh
              </span>
            </div>

            <!-- Printer list inside card -->
            <div v-if="project.printers.length === 0" class="p-3 rounded-xl border border-dashed border-slate-800 text-center text-xs text-slate-500">
              Belum ada printer yang dialokasikan untuk project ini.
            </div>

            <div v-else class="space-y-1.5 max-h-[140px] overflow-y-auto pr-1">
              <div
                v-for="p in project.printers"
                :key="p.id"
                class="p-2.5 rounded-xl bg-slate-900/60 border border-slate-800/80 flex items-center justify-between gap-3 text-xs"
              >
                <div class="flex items-center gap-2 min-w-0">
                  <span class="relative flex h-2 w-2 shrink-0">
                    <span
                      v-if="p.is_online"
                      class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"
                    ></span>
                    <span
                      class="relative inline-flex rounded-full h-2 w-2"
                      :class="p.is_online ? 'bg-emerald-500' : 'bg-slate-600'"
                    ></span>
                  </span>
                  <div class="truncate">
                    <span class="font-semibold text-slate-200">{{ p.name }}</span>
                    <span class="text-slate-400 ml-1 font-mono text-[10px]">({{ p.slug }})</span>
                  </div>
                </div>

                <div class="flex items-center gap-2 shrink-0">
                  <span class="text-[10px] text-slate-400 font-mono hidden sm:inline">
                    {{ p.ip_address || 'No IP' }}
                  </span>
                  <button
                    @click="removePrinterFromProject(project, p)"
                    class="p-1 rounded text-slate-400 hover:text-rose-400 transition-colors cursor-pointer"
                    title="Lepaskan printer dari project ini"
                  >
                    <X class="w-3.5 h-3.5" />
                  </button>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Card Footer -->
        <div class="mt-5 pt-3 border-t border-slate-800/60 flex items-center justify-between text-[11px] text-slate-500">
          <span>Dibuat: {{ formatDate(project.created_at) }}</span>
          <span class="text-slate-400 font-mono">ID: {{ project.id.substring(0, 8) }}...</span>
        </div>
      </div>
    </div>

    <!-- Create Project Modal -->
    <div
      v-if="showCreateModal"
      class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/80 backdrop-blur-sm"
      @click.self="showCreateModal = false"
    >
      <div class="card-panel border border-slate-700/80 rounded-2xl max-w-md w-full p-6 shadow-2xl space-y-4 bg-[#0d1424]">
        <div class="flex items-center justify-between pb-3 border-b border-slate-800">
          <h3 class="text-sm sm:text-base font-bold text-white flex items-center gap-2">
            <FolderGit2 class="w-4 h-4 text-indigo-400" />
            <span>Tambah Project / Tenant Baru</span>
          </h3>
          <button @click="showCreateModal = false" class="text-slate-400 hover:text-white cursor-pointer">✕</button>
        </div>

        <form @submit.prevent="submitCreateProject" class="space-y-4">
          <div>
            <label class="block text-xs font-semibold text-slate-300 mb-1">Nama Project / Unit</label>
            <input
              type="text"
              v-model="createForm.name"
              class="input-field"
              placeholder="Contoh: RS Permata Hati - Prima CSSD"
              required
            />
          </div>

          <div class="grid grid-cols-2 gap-3">
            <div>
              <label class="block text-xs font-semibold text-slate-300 mb-1">Kode Unik</label>
              <input
                type="text"
                v-model="createForm.code"
                class="input-field font-mono uppercase"
                placeholder="RSPH-CSSD"
                required
              />
            </div>
            <div>
              <label class="block text-xs font-semibold text-slate-300 mb-1">Maks. Printer (Kuota)</label>
              <input
                type="number"
                min="1"
                max="100"
                v-model.number="createForm.max_printers"
                class="input-field"
                required
              />
            </div>
          </div>

          <div>
            <label class="block text-xs font-semibold text-slate-300 mb-1">Deskripsi / Catatan (Opsional)</label>
            <textarea
              v-model="createForm.description"
              rows="2"
              class="input-field"
              placeholder="Contoh: Digunakan untuk mencetak label instrumen steril CSSD lantai 2."
            ></textarea>
          </div>

          <p class="text-[11px] text-slate-400 leading-relaxed font-mono">
            Project API Key dengan prefix <code class="text-indigo-300 font-bold">proj_</code> akan digenerate otomatis saat disimpan.
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
              {{ saving ? 'Menyimpan...' : 'Daftarkan Project' }}
            </button>
          </div>
        </form>
      </div>
    </div>

    <!-- Edit Project Modal -->
    <div
      v-if="showEditModal"
      class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/80 backdrop-blur-sm"
      @click.self="showEditModal = false"
    >
      <div class="card-panel border border-slate-700/80 rounded-2xl max-w-md w-full p-6 shadow-2xl space-y-4 bg-[#0d1424]">
        <div class="flex items-center justify-between pb-3 border-b border-slate-800">
          <h3 class="text-sm sm:text-base font-bold text-white flex items-center gap-2">
            <Edit3 class="w-4 h-4 text-indigo-400" />
            <span>Edit Project: {{ selectedProject?.code }}</span>
          </h3>
          <button @click="showEditModal = false" class="text-slate-400 hover:text-white cursor-pointer">✕</button>
        </div>

        <form @submit.prevent="submitEditProject" class="space-y-4">
          <div>
            <label class="block text-xs font-semibold text-slate-300 mb-1">Nama Project</label>
            <input
              type="text"
              v-model="editForm.name"
              class="input-field"
              required
            />
          </div>

          <div class="grid grid-cols-2 gap-3">
            <div>
              <label class="block text-xs font-semibold text-slate-300 mb-1">Maks. Printer (Kuota)</label>
              <input
                type="number"
                min="1"
                max="100"
                v-model.number="editForm.max_printers"
                class="input-field"
                required
              />
            </div>
            <div>
              <label class="block text-xs font-semibold text-slate-300 mb-1">Status Project</label>
              <select
                v-model="editForm.status"
                class="input-field"
              >
                <option value="active">Active</option>
                <option value="inactive">Inactive</option>
              </select>
            </div>
          </div>

          <div>
            <label class="block text-xs font-semibold text-slate-300 mb-1">Deskripsi</label>
            <textarea
              v-model="editForm.description"
              rows="2"
              class="input-field"
            ></textarea>
          </div>

          <div class="flex items-center justify-end gap-2.5 pt-3 border-t border-slate-800">
            <button
              type="button"
              @click="showEditModal = false"
              class="btn-ghost"
            >
              Batal
            </button>
            <button
              type="submit"
              :disabled="saving"
              class="btn-primary"
            >
              {{ saving ? 'Menyimpan...' : 'Simpan Perubahan' }}
            </button>
          </div>
        </form>
      </div>
    </div>

    <!-- Assign Printer Modal -->
    <div
      v-if="showAssignModal"
      class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/80 backdrop-blur-sm"
      @click.self="showAssignModal = false"
    >
      <div class="card-panel border border-slate-700/80 rounded-2xl max-w-md w-full p-6 shadow-2xl space-y-4 bg-[#0d1424]">
        <div class="flex items-center justify-between pb-3 border-b border-slate-800">
          <div>
            <h3 class="text-sm sm:text-base font-bold text-white flex items-center gap-2">
              <Printer class="w-4 h-4 text-indigo-400" />
              <span>Alokasikan Printer ke {{ selectedProject?.code }}</span>
            </h3>
            <p class="text-xs text-slate-400 mt-0.5">
              Sisa Kuota: {{ selectedProject ? selectedProject.max_printers - selectedProject.printers_count : 0 }} Printer
            </p>
          </div>
          <button @click="showAssignModal = false" class="text-slate-400 hover:text-white cursor-pointer">✕</button>
        </div>

        <div class="space-y-3">
          <label class="block text-xs font-semibold text-slate-300">Pilih Printer Tersedia:</label>
          <div v-if="availableClients.length === 0" class="p-4 rounded-xl border border-dashed border-slate-800 text-center text-xs text-slate-400">
            Tidak ada printer client yang tersedia untuk dialokasikan. Silakan daftarkan client baru di menu Clients.
          </div>
          <div v-else class="space-y-2 max-h-[250px] overflow-y-auto pr-1">
            <div
              v-for="c in availableClients"
              :key="c.id"
              class="p-3 rounded-xl border border-slate-800 bg-slate-900/70 hover:border-indigo-500/50 flex items-center justify-between gap-3 cursor-pointer transition-all"
              @click="assignClientToProject(c.id)"
            >
              <div>
                <div class="font-semibold text-slate-100 text-xs">{{ c.name }}</div>
                <div class="text-[11px] text-slate-400 font-mono">
                  {{ c.machine_name || 'No Machine' }} • {{ c.ip_address || 'No IP' }}
                </div>
                <div v-if="c.project" class="text-[10px] text-amber-400 font-mono mt-0.5">
                  Saat ini di: {{ c.project.name }} (akan dipindahkan)
                </div>
                <div v-else class="text-[10px] text-emerald-400 font-mono mt-0.5">
                  Belum teralokasi (bebas)
                </div>
              </div>
              <button
                class="px-2.5 py-1 rounded-lg bg-indigo-600 hover:bg-indigo-500 text-white text-xs font-medium shrink-0 cursor-pointer"
              >
                Pilih
              </button>
            </div>
          </div>
        </div>

        <div class="pt-3 border-t border-slate-800 text-right">
          <button
            @click="showAssignModal = false"
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
import {
  FolderGit2,
  Printer,
  Layers,
  ShieldCheck,
  Plus,
  RefreshCw,
  Edit3,
  Trash2,
  Key,
  Eye,
  EyeOff,
  Copy,
  X,
} from 'lucide-vue-next';
import { useToast } from '../composables/useToast';

const toast = useToast();
const loading = ref(false);
const saving = ref(false);
const projects = ref([]);
const allClients = ref([]);
const visibleKeys = reactive({});

const showCreateModal = ref(false);
const showEditModal = ref(false);
const showAssignModal = ref(false);
const selectedProject = ref(null);

const createForm = reactive({
  name: '',
  code: '',
  max_printers: 2,
  description: '',
});

const editForm = reactive({
  name: '',
  max_printers: 2,
  status: 'active',
  description: '',
});

const totalAllocatedPrinters = computed(() => {
  return projects.value.reduce((sum, p) => sum + (p.printers_count || 0), 0);
});

const totalMaxPrinters = computed(() => {
  return projects.value.reduce((sum, p) => sum + (p.max_printers || 0), 0);
});

const availableClients = computed(() => {
  if (!selectedProject.value) return [];
  return allClients.value.filter(
    (c) => c.project_client_id !== selectedProject.value.id
  );
});

const maskKey = (key) => {
  if (!key) return '••••••••';
  return key.substring(0, 9) + '••••••••';
};

const toggleKeyVisibility = (id) => {
  visibleKeys[id] = !visibleKeys[id];
};

const copyKey = async (text) => {
  try {
    await navigator.clipboard.writeText(text);
    toast.success('Project API Key berhasil disalin!');
  } catch (err) {
    toast.error('Gagal menyalin API Key.');
  }
};

const formatDate = (isoString) => {
  if (!isoString) return '-';
  const d = new Date(isoString);
  return d.toLocaleDateString('id-ID', {
    day: 'numeric',
    month: 'short',
    year: 'numeric',
  });
};

const getQuotaTextColor = (project) => {
  const percent = (project.printers_count / project.max_printers) * 100;
  if (percent >= 100) return 'text-rose-400';
  if (percent >= 75) return 'text-amber-400';
  return 'text-emerald-400';
};

const getQuotaProgressColor = (project) => {
  const percent = (project.printers_count / project.max_printers) * 100;
  if (percent >= 100) return 'bg-rose-500';
  if (percent >= 75) return 'bg-amber-500';
  return 'bg-emerald-500';
};

const fetchProjects = async (isManual = false) => {
  loading.value = true;
  try {
    const [resProj, resClients] = await Promise.all([
      axios.get('/api/v1/admin/projects'),
      axios.get('/api/v1/admin/clients'),
    ]);
    if (resProj.data.success) {
      projects.value = resProj.data.data;
    }
    if (resClients.data.success) {
      allClients.value = resClients.data.data;
    }
    if (isManual) {
      toast.success('Data project berhasil diperbarui.');
    }
  } catch (err) {
    console.error('Failed to fetch projects', err);
    if (isManual) {
      toast.error('Gagal memuat data project.');
    }
  } finally {
    loading.value = false;
  }
};

const openCreateModal = () => {
  createForm.name = '';
  createForm.code = '';
  createForm.max_printers = 2;
  createForm.description = '';
  showCreateModal.value = true;
};

const submitCreateProject = async () => {
  saving.value = true;
  try {
    const res = await axios.post('/api/v1/admin/projects', createForm);
    if (res.data.success) {
      showCreateModal.value = false;
      await fetchProjects();
      toast.success(`Project "${res.data.data.name}" berhasil didaftarkan!`);
    }
  } catch (err) {
    toast.error('Gagal membuat project: ' + (err.response?.data?.message || err.message));
  } finally {
    saving.value = false;
  }
};

const openEditModal = (project) => {
  selectedProject.value = project;
  editForm.name = project.name;
  editForm.max_printers = project.max_printers;
  editForm.status = project.status;
  editForm.description = project.description || '';
  showEditModal.value = true;
};

const submitEditProject = async () => {
  if (!selectedProject.value) return;
  saving.value = true;
  try {
    const res = await axios.put(`/api/v1/admin/projects/${selectedProject.value.id}`, editForm);
    if (res.data.success) {
      showEditModal.value = false;
      await fetchProjects();
      toast.success(`Project "${selectedProject.value.code}" diperbarui.`);
    }
  } catch (err) {
    toast.error('Gagal mengupdate project: ' + (err.response?.data?.message || err.message));
  } finally {
    saving.value = false;
  }
};

const confirmRegenerateKey = async (project) => {
  if (!confirm(`Generate Project API Key baru untuk "${project.name}"?\n\nAplikasi Prima yang terhubung harus memperbarui nilai X-Project-Key agar tetap bisa mengirim print job.`)) {
    return;
  }
  try {
    const res = await axios.post(`/api/v1/admin/projects/${project.id}/regenerate-key`);
    if (res.data.success) {
      await fetchProjects();
      visibleKeys[project.id] = true;
      toast.success('Project Key berhasil di-regenerate!');
    }
  } catch (err) {
    toast.error('Gagal regenerate key: ' + (err.response?.data?.message || err.message));
  }
};

const deleteProject = async (project) => {
  if (!confirm(`Hapus project "${project.name}" (${project.code})?\n\nPrinter yang sebelumnya dialokasikan ke project ini akan menjadi tidak terhubung (unassigned).`)) {
    return;
  }
  try {
    const res = await axios.delete(`/api/v1/admin/projects/${project.id}`);
    if (res.data.success) {
      await fetchProjects();
      toast.success(`Project "${project.name}" berhasil dihapus.`);
    }
  } catch (err) {
    toast.error('Gagal menghapus project: ' + (err.response?.data?.message || err.message));
  }
};

const openAssignModal = (project) => {
  selectedProject.value = project;
  showAssignModal.value = true;
};

const assignClientToProject = async (clientId) => {
  if (!selectedProject.value) return;
  try {
    const res = await axios.post(`/api/v1/admin/projects/${selectedProject.value.id}/assign-client`, {
      client_id: clientId,
    });
    if (res.data.success) {
      showAssignModal.value = false;
      await fetchProjects();
      toast.success('Printer berhasil dialokasikan.');
    }
  } catch (err) {
    toast.error('Gagal mengalokasikan printer: ' + (err.response?.data?.message || err.message));
  }
};

const removePrinterFromProject = async (project, printer) => {
  if (!confirm(`Lepaskan printer "${printer.name}" dari project "${project.name}"?`)) {
    return;
  }
  try {
    const res = await axios.post(`/api/v1/admin/projects/${project.id}/remove-client`, {
      client_id: printer.id,
    });
    if (res.data.success) {
      await fetchProjects();
      toast.info(`Printer "${printer.name}" dilepas dari project.`);
    }
  } catch (err) {
    toast.error('Gagal melepas printer: ' + (err.response?.data?.message || err.message));
  }
};

onMounted(() => {
  fetchProjects();
});
</script>
