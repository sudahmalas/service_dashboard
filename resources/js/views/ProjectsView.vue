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

    <!-- Search & Filter Controls -->
    <div class="card-panel p-3.5 sm:p-4 rounded-2xl">
      <div class="flex flex-col sm:flex-row items-center justify-between gap-3">
        <!-- Search Input -->
        <div class="relative w-full sm:flex-1">
          <Search class="w-4 h-4 text-slate-400 absolute left-3.5 top-1/2 -translate-y-1/2 pointer-events-none" />
          <input
            v-model="searchQuery"
            type="text"
            class="input-field pl-10 pr-4 w-full text-xs"
            placeholder="Cari nama project, kode tenant, deskripsi, atau API key..."
          />
        </div>

        <!-- Status Filter Pills -->
        <div class="flex items-center gap-1.5 w-full sm:w-auto overflow-x-auto pb-1 sm:pb-0 shrink-0">
          <button
            v-for="s in [
              { key: 'all', label: 'Semua Status' },
              { key: 'active', label: 'Active' },
              { key: 'inactive', label: 'Inactive' },
            ]"
            :key="s.key"
            @click="statusFilter = s.key"
            class="px-3 py-1.5 rounded-xl text-xs font-semibold transition-all cursor-pointer whitespace-nowrap"
            :class="statusFilter === s.key ? 'bg-indigo-600 text-white shadow-md' : 'bg-slate-900/80 text-slate-400 hover:text-white border border-slate-800'"
          >
            {{ s.label }}
          </button>
        </div>
      </div>
    </div>

    <!-- Projects List Table Container (Zero Horizontal Scroll, 100% Fluid) -->
    <div class="card-panel rounded-2xl overflow-hidden shadow-xl">
      <!-- Desktop & Tablet View (md and up): Fluid Table -->
      <div class="hidden md:block">
        <table class="w-full text-left text-xs text-slate-300">
          <thead class="bg-slate-900/90 text-[11px] font-bold uppercase tracking-wider text-slate-400 border-b border-slate-800">
            <tr>
              <th class="px-4 py-3.5 w-[26%]">Project / Tenant</th>
              <th class="px-4 py-3.5 w-[14%]">Status</th>
              <th class="px-4 py-3.5 w-[24%]">Alokasi Hardware & Kuota</th>
              <th class="px-4 py-3.5 w-[18%]">Project API Key</th>
              <th class="px-4 py-3.5 w-[18%] text-right">Aksi</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-800/60 font-sans">
            <!-- Loading State -->
            <tr v-if="loading && projects.length === 0">
              <td colspan="5" class="px-6 py-16 text-center text-slate-400">
                <RefreshCw class="w-6 h-6 animate-spin mx-auto mb-2 text-indigo-400" />
                <span class="text-xs font-medium">Memuat data project & tenant...</span>
              </td>
            </tr>

            <!-- Empty State -->
            <tr v-else-if="filteredProjects.length === 0">
              <td colspan="5" class="px-6 py-16 text-center text-slate-400">
                <FolderGit2 class="w-8 h-8 mx-auto mb-3 text-slate-500" />
                <p class="font-medium text-slate-300 text-sm">Tidak ada project yang cocok.</p>
                <p class="text-xs text-slate-500 mt-1">Coba sesuaikan kata kunci pencarian atau daftarkan project baru.</p>
                <button
                  v-if="projects.length === 0"
                  @click="openCreateModal"
                  class="btn-primary mt-4 text-xs"
                >
                  <Plus class="w-3.5 h-3.5" />
                  <span>Daftarkan Project Pertama</span>
                </button>
              </td>
            </tr>

            <!-- Table Rows -->
            <tr
              v-for="project in filteredProjects"
              :key="project.id"
              class="hover:bg-slate-900/50 transition-colors duration-150 group"
            >
              <!-- Project & Code -->
              <td class="px-4 py-3.5 align-top">
                <div class="flex items-center gap-2 flex-wrap">
                  <span class="font-bold text-white text-sm truncate" :title="project.name">
                    {{ project.name }}
                  </span>
                  <span class="badge-indigo font-mono text-[10px]">
                    {{ project.code }}
                  </span>
                </div>
                <div class="text-[11px] text-slate-400 mt-0.5 line-clamp-1" :title="project.description">
                  {{ project.description || 'Tidak ada deskripsi khusus.' }}
                </div>
                <div class="text-[10px] text-slate-500 font-mono mt-1">
                  Dibuat: {{ formatDate(project.created_at) }}
                </div>
              </td>

              <!-- Status & Keamanan -->
              <td class="px-4 py-3.5 align-top">
                <div
                  class="inline-flex items-center gap-1.5 px-2 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wider border font-mono"
                  :class="project.status === 'active' ? 'bg-emerald-500/10 text-emerald-400 border-emerald-500/25' : 'bg-rose-500/10 text-rose-400 border-rose-500/25'"
                >
                  <span class="w-1.5 h-1.5 rounded-full" :class="project.status === 'active' ? 'bg-emerald-400' : 'bg-rose-400'"></span>
                  <span>{{ project.status }}</span>
                </div>
                <div class="flex items-center gap-1 text-[10px] text-slate-400 mt-1 font-mono">
                  <ShieldCheck class="w-3 h-3 text-purple-400 shrink-0" />
                  <span>Multi-Tenant</span>
                </div>
              </td>

              <!-- Alokasi Hardware & Kuota -->
              <td class="px-4 py-3.5 align-top">
                <div class="flex items-center justify-between text-xs mb-1">
                  <span class="font-mono text-xs font-semibold" :class="getQuotaTextColor(project)">
                    {{ project.printers_count }} / {{ project.max_printers }} Printer
                  </span>
                  <span class="text-[10px] font-mono text-slate-400">
                    {{ Math.round((project.printers_count / project.max_printers) * 100) }}%
                  </span>
                </div>
                <!-- Mini Progress Bar -->
                <div class="w-full h-1.5 rounded-full bg-slate-900 border border-slate-800 overflow-hidden">
                  <div
                    class="h-full rounded-full transition-all duration-300"
                    :class="getQuotaProgressColor(project)"
                    :style="{ width: `${Math.min(100, Math.round((project.printers_count / project.max_printers) * 100))}%` }"
                  ></div>
                </div>

                <!-- Hardware links -->
                <div class="mt-1.5 flex items-center gap-2 flex-wrap">
                  <button
                    v-if="project.printers && project.printers.length > 0"
                    @click="openPrintersDetailModal(project)"
                    class="text-[11px] text-indigo-400 hover:text-indigo-300 flex items-center gap-1 cursor-pointer font-medium"
                    title="Klik untuk detail printer fisik yang teralokasi"
                  >
                    <Printer class="w-3 h-3" />
                    <span>{{ project.printers.length }} Printer Fisik</span>
                  </button>
                  <span v-else class="text-[10px] text-slate-500 italic">
                    Belum ada printer
                  </span>
                </div>
              </td>

              <!-- Project API Key -->
              <td class="px-4 py-3.5 align-top">
                <div class="flex items-center gap-1">
                  <span class="font-mono text-xs text-slate-200 select-all truncate bg-[#080c14] px-2 py-1 rounded border border-slate-800 max-w-[130px]">
                    {{ visibleKeys[project.id] ? project.api_key : maskKey(project.api_key) }}
                  </span>
                  <button
                    @click="toggleKeyVisibility(project.id)"
                    class="p-1 rounded-lg bg-slate-900 border border-slate-800 text-slate-400 hover:text-white cursor-pointer"
                    :title="visibleKeys[project.id] ? 'Sembunyikan' : 'Intip Key'"
                  >
                    <EyeOff v-if="visibleKeys[project.id]" class="w-3.5 h-3.5" />
                    <Eye v-else class="w-3.5 h-3.5" />
                  </button>
                  <button
                    @click="copyKey(project.api_key)"
                    class="p-1 rounded-lg bg-slate-900 border border-slate-800 text-slate-300 hover:text-indigo-300 cursor-pointer"
                    title="Salin Key"
                  >
                    <Copy class="w-3.5 h-3.5" />
                  </button>
                </div>
                <div class="text-[10px] text-slate-500 font-mono mt-0.5">
                  header: <code class="text-indigo-400 font-semibold">X-Project-Key</code>
                </div>
              </td>

              <!-- Aksi (Matched with Queues and Clients) -->
              <td class="px-4 py-3.5 align-top text-right whitespace-nowrap">
                <div class="flex items-center justify-end gap-1.5">
                  <button
                    v-if="project.printers_count < project.max_printers"
                    @click="openAssignModal(project)"
                    class="px-2 py-1 rounded-lg bg-indigo-950/40 hover:bg-indigo-900/60 border border-indigo-800/40 text-xs text-indigo-300 hover:text-indigo-200 transition-colors inline-flex items-center gap-1 cursor-pointer font-medium"
                    title="Alokasikan Printer Fisik"
                  >
                    <Plus class="w-3.5 h-3.5" />
                    <span>Alokasi</span>
                  </button>
                  <button
                    @click="openEditModal(project)"
                    class="px-2 py-1 rounded-lg bg-slate-900 hover:bg-slate-800 border border-slate-800 text-xs text-slate-300 hover:text-white transition-colors inline-flex items-center gap-1 cursor-pointer font-medium"
                    title="Edit Project"
                  >
                    <Edit3 class="w-3.5 h-3.5 text-indigo-400" />
                    <span>Edit</span>
                  </button>
                  <button
                    @click="confirmRegenerateKey(project)"
                    class="px-2 py-1 rounded-lg bg-slate-900 hover:bg-slate-800 border border-slate-800 text-xs text-amber-400 hover:text-amber-300 transition-colors inline-flex items-center gap-1 cursor-pointer font-medium"
                    title="Generate API Key Baru"
                  >
                    <Key class="w-3.5 h-3.5 text-amber-400" />
                    <span>New Key</span>
                  </button>
                  <button
                    @click="deleteProject(project)"
                    class="p-1.5 rounded-lg bg-slate-900 hover:bg-rose-950/50 border border-slate-800 hover:border-rose-900/50 text-slate-400 hover:text-rose-400 transition-colors cursor-pointer"
                    title="Hapus Project"
                  >
                    <Trash2 class="w-3.5 h-3.5" />
                  </button>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- Mobile Project Cards (< md) -->
      <div class="md:hidden divide-y divide-slate-800/60">
        <div v-if="loading && projects.length === 0" class="p-8 text-center text-slate-400">
          <RefreshCw class="w-6 h-6 animate-spin mx-auto mb-2 text-indigo-400" />
          <span class="text-xs">Memuat data project...</span>
        </div>

        <div v-else-if="filteredProjects.length === 0" class="p-8 text-center text-slate-400">
          <FolderGit2 class="w-8 h-8 mx-auto mb-2 text-slate-500" />
          <p class="font-medium text-slate-300 text-xs">Tidak ada project yang cocok.</p>
        </div>

        <div
          v-for="project in filteredProjects"
          :key="project.id"
          class="p-4 space-y-3 bg-slate-900/40"
        >
          <div class="flex items-start justify-between gap-2">
            <div>
              <div class="font-bold text-white text-sm">{{ project.name }}</div>
              <div class="flex items-center gap-1.5 mt-0.5">
                <span class="badge-indigo font-mono text-[10px]">{{ project.code }}</span>
                <span
                  class="text-[9px] font-mono font-bold uppercase px-1.5 py-0.2 rounded"
                  :class="project.status === 'active' ? 'bg-emerald-500/10 text-emerald-400 border border-emerald-500/20' : 'bg-rose-500/10 text-rose-400 border border-rose-500/20'"
                >
                  {{ project.status }}
                </span>
              </div>
            </div>
            <div class="flex items-center gap-1">
              <button
                @click="openEditModal(project)"
                class="p-1 rounded-lg bg-slate-800 text-slate-300"
              >
                <Edit3 class="w-3.5 h-3.5" />
              </button>
              <button
                @click="deleteProject(project)"
                class="p-1 rounded-lg bg-slate-800 text-rose-400"
              >
                <Trash2 class="w-3.5 h-3.5" />
              </button>
            </div>
          </div>

          <div class="text-xs text-slate-400">
            {{ project.description || 'Tidak ada deskripsi.' }}
          </div>

          <!-- Kuota Bar -->
          <div class="space-y-1">
            <div class="flex items-center justify-between text-xs">
              <span class="text-slate-400">Alokasi Printer:</span>
              <span class="font-mono font-bold" :class="getQuotaTextColor(project)">
                {{ project.printers_count }} / {{ project.max_printers }} ({{ Math.round((project.printers_count / project.max_printers) * 100) }}%)
              </span>
            </div>
            <div class="w-full h-1.5 rounded-full bg-slate-900 border border-slate-800 overflow-hidden">
              <div
                class="h-full rounded-full transition-all"
                :class="getQuotaProgressColor(project)"
                :style="{ width: `${Math.min(100, Math.round((project.printers_count / project.max_printers) * 100))}%` }"
              ></div>
            </div>
          </div>

          <!-- API Key Mobile -->
          <div class="flex items-center justify-between gap-2 p-2 rounded-lg bg-slate-900 border border-slate-800 text-xs">
            <span class="font-mono text-[11px] truncate flex-1">
              {{ visibleKeys[project.id] ? project.api_key : maskKey(project.api_key) }}
            </span>
            <div class="flex items-center gap-1 shrink-0">
              <button @click="copyKey(project.api_key)" class="p-1 text-slate-400 hover:text-white">
                <Copy class="w-3 h-3" />
              </button>
              <button @click="toggleKeyVisibility(project.id)" class="p-1 text-slate-400 hover:text-white">
                <EyeOff v-if="visibleKeys[project.id]" class="w-3 h-3" />
                <Eye v-else class="w-3 h-3" />
              </button>
            </div>
          </div>

          <!-- Mobile Actions Bottom -->
          <div class="flex items-center justify-between gap-2 pt-1 border-t border-slate-800/60">
            <button
              v-if="project.printers && project.printers.length"
              @click="openPrintersDetailModal(project)"
              class="text-xs text-indigo-400 flex items-center gap-1"
            >
              <Printer class="w-3.5 h-3.5" />
              <span>{{ project.printers.length }} Hardware</span>
            </button>
            <div class="flex items-center gap-1.5 ml-auto">
              <button
                v-if="project.printers_count < project.max_printers"
                @click="openAssignModal(project)"
                class="px-2.5 py-1 rounded-lg bg-indigo-600 text-white text-xs font-medium"
              >
                + Alokasi
              </button>
              <button
                @click="confirmRegenerateKey(project)"
                class="px-2.5 py-1 rounded-lg bg-slate-800 text-amber-400 text-xs font-medium"
              >
                New Key
              </button>
            </div>
          </div>
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

    <!-- Mapped Hardware / Printers Detail Modal -->
    <div
      v-if="selectedProjectForPrinters"
      class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/80 backdrop-blur-sm"
      @click.self="selectedProjectForPrinters = null"
    >
      <div class="card-panel border border-slate-700/80 rounded-2xl max-w-lg w-full p-6 shadow-2xl space-y-4 bg-[#0d1424]">
        <div class="flex items-center justify-between pb-3 border-b border-slate-800">
          <div>
            <h3 class="text-sm sm:text-base font-bold text-white flex items-center gap-2">
              <Printer class="w-4 h-4 text-indigo-400" />
              <span>Hardware Terpetakan: {{ selectedProjectForPrinters.name }}</span>
            </h3>
            <p class="text-xs text-slate-400 mt-0.5">
              Kode Tenant: <span class="text-indigo-400 font-mono font-bold">{{ selectedProjectForPrinters.code }}</span> • Kuota: {{ selectedProjectForPrinters.printers_count }} / {{ selectedProjectForPrinters.max_printers }} Printer
            </p>
          </div>
          <button @click="selectedProjectForPrinters = null" class="text-slate-400 hover:text-white cursor-pointer">✕</button>
        </div>

        <div class="space-y-2.5 max-h-[350px] overflow-y-auto pr-1">
          <div v-if="!selectedProjectForPrinters.printers || selectedProjectForPrinters.printers.length === 0" class="p-6 rounded-xl border border-dashed border-slate-800 text-center text-xs text-slate-400">
            Belum ada printer fisik yang dialokasikan untuk project ini.
          </div>

          <div
            v-for="p in selectedProjectForPrinters.printers"
            :key="p.id"
            class="p-3 rounded-xl border border-slate-800 bg-slate-900/60 flex items-center justify-between gap-3"
          >
            <div class="flex items-center gap-2.5 min-w-0">
              <span class="relative flex h-2.5 w-2.5 shrink-0">
                <span
                  v-if="p.is_online"
                  class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"
                ></span>
                <span
                  class="relative inline-flex rounded-full h-2.5 w-2.5"
                  :class="p.is_online ? 'bg-emerald-500' : 'bg-slate-600'"
                ></span>
              </span>
              <div class="min-w-0">
                <div class="font-semibold text-slate-100 text-xs sm:text-sm truncate">{{ p.name }}</div>
                <div class="text-[11px] text-slate-400 font-mono">
                  <span>channel: printer.{{ p.slug }}</span>
                  <span class="text-slate-600 mx-1.5">•</span>
                  <span>{{ p.machine_name || 'No Machine' }}</span>
                  <span class="text-slate-600 mx-1.5">•</span>
                  <span>{{ p.ip_address || 'No IP' }}</span>
                </div>
              </div>
            </div>

            <button
              @click="removePrinterFromProject(selectedProjectForPrinters, p)"
              class="px-2 py-1 rounded-lg bg-slate-900 hover:bg-rose-950/60 border border-slate-800 hover:border-rose-800/60 text-slate-400 hover:text-rose-400 transition-colors text-xs inline-flex items-center gap-1 shrink-0 cursor-pointer"
              title="Lepaskan printer ini dari project"
            >
              <X class="w-3.5 h-3.5" />
              <span>Lepas</span>
            </button>
          </div>
        </div>

        <div class="pt-3 border-t border-slate-800 flex items-center justify-between">
          <button
            v-if="selectedProjectForPrinters.printers_count < selectedProjectForPrinters.max_printers"
            @click="openAssignFromDetailModal"
            class="btn-primary text-xs"
          >
            <Plus class="w-3.5 h-3.5" />
            <span>Alokasikan Printer</span>
          </button>
          <span v-else class="text-xs text-amber-400 font-mono">
            Kuota Penuh ({{ selectedProjectForPrinters.max_printers }} Printer)
          </span>

          <button
            @click="selectedProjectForPrinters = null"
            class="btn-secondary ml-auto"
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
  Search,
} from 'lucide-vue-next';
import { useToast } from '../composables/useToast';

const toast = useToast();
const loading = ref(false);
const saving = ref(false);
const projects = ref([]);
const allClients = ref([]);
const visibleKeys = reactive({});
const searchQuery = ref('');
const statusFilter = ref('all');

const showCreateModal = ref(false);
const showEditModal = ref(false);
const showAssignModal = ref(false);
const selectedProject = ref(null);
const selectedProjectForPrinters = ref(null);

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

const filteredProjects = computed(() => {
  return projects.value.filter((p) => {
    // Status Filter
    if (statusFilter.value !== 'all' && p.status !== statusFilter.value) {
      return false;
    }
    // Search Query
    if (searchQuery.value.trim()) {
      const q = searchQuery.value.toLowerCase();
      const matchName = p.name?.toLowerCase().includes(q);
      const matchCode = p.code?.toLowerCase().includes(q);
      const matchDesc = p.description?.toLowerCase().includes(q);
      const matchKey = p.api_key?.toLowerCase().includes(q);
      return matchName || matchCode || matchDesc || matchKey;
    }
    return true;
  });
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
      // If modal is open, refresh selected project
      if (selectedProjectForPrinters.value) {
        selectedProjectForPrinters.value = projects.value.find(p => p.id === selectedProjectForPrinters.value.id) || null;
      }
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

const openPrintersDetailModal = (project) => {
  selectedProjectForPrinters.value = project;
};

const openAssignFromDetailModal = () => {
  if (selectedProjectForPrinters.value) {
    selectedProject.value = selectedProjectForPrinters.value;
    selectedProjectForPrinters.value = null;
    showAssignModal.value = true;
  }
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
