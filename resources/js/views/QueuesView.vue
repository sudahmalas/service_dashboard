<template>
  <div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
      <div>
        <h1 class="text-2xl font-extrabold text-white tracking-tight flex items-center gap-2.5">
          <Layers class="w-6 h-6 text-indigo-400" />
          <span>Queue & Store-Forward Explorer</span>
        </h1>
        <p class="text-slate-400 text-xs sm:text-sm mt-1">
          Pantau antrian penyangga dan lalu lintas relay cetak antar project tenant secara real-time.
        </p>
      </div>

      <div class="flex items-center gap-2.5">
        <button
          @click="fetchQueues(true)"
          :disabled="loading"
          class="btn-secondary"
        >
          <RefreshCw class="w-3.5 h-3.5" :class="{ 'animate-spin': loading }" />
          <span>Refresh Data</span>
        </button>
      </div>
    </div>

    <!-- Clickable Summary Metrics Cards -->
    <div class="grid grid-cols-2 md:grid-cols-4 gap-3.5">
      <!-- Total -->
      <div
        @click="quickFilterStatus('')"
        class="card-panel p-4 rounded-2xl cursor-pointer transition-all hover:border-slate-700"
        :class="filters.status === '' ? 'ring-1 ring-indigo-500/50 border-indigo-500/40 bg-[#111a2e]' : ''"
      >
        <div class="flex items-center justify-between">
          <span class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Total Relay</span>
          <Layers class="w-4 h-4 text-slate-500" />
        </div>
        <div class="text-2xl font-black text-white font-mono mt-2">{{ stats.total }}</div>
        <div class="text-[11px] text-slate-500 mt-1">Klik untuk lihat semua</div>
      </div>

      <!-- Pending Buffer -->
      <div
        @click="quickFilterStatus('pending')"
        class="card-panel p-4 rounded-2xl cursor-pointer transition-all hover:border-amber-500/40"
        :class="filters.status === 'pending' ? 'ring-1 ring-amber-500/50 border-amber-500/40 bg-amber-500/10' : ''"
      >
        <div class="flex items-center justify-between">
          <span class="text-xs font-semibold text-amber-400 uppercase tracking-wider">Pending (Buffer)</span>
          <Clock class="w-4 h-4 text-amber-400" />
        </div>
        <div class="text-2xl font-black text-amber-300 font-mono mt-2">{{ stats.pending }}</div>
        <div class="text-[11px] text-amber-400/80 mt-1">Menunggu PC Client online</div>
      </div>

      <!-- Dispatched -->
      <div
        @click="quickFilterStatus('dispatched')"
        class="card-panel p-4 rounded-2xl cursor-pointer transition-all hover:border-indigo-500/40"
        :class="filters.status === 'dispatched' ? 'ring-1 ring-indigo-500/50 border-indigo-500/40 bg-indigo-500/10' : ''"
      >
        <div class="flex items-center justify-between">
          <span class="text-xs font-semibold text-indigo-400 uppercase tracking-wider">Dispatched</span>
          <Send class="w-4 h-4 text-indigo-400" />
        </div>
        <div class="text-2xl font-black text-indigo-300 font-mono mt-2">{{ stats.dispatched }}</div>
        <div class="text-[11px] text-indigo-400/80 mt-1">Tersiar ke Reverb WS</div>
      </div>

      <!-- Synced -->
      <div
        @click="quickFilterStatus('synced')"
        class="card-panel p-4 rounded-2xl cursor-pointer transition-all hover:border-emerald-500/40"
        :class="filters.status === 'synced' ? 'ring-1 ring-emerald-500/50 border-emerald-500/40 bg-emerald-500/10' : ''"
      >
        <div class="flex items-center justify-between">
          <span class="text-xs font-semibold text-emerald-400 uppercase tracking-wider">Synced / ACK</span>
          <CheckCircle2 class="w-4 h-4 text-emerald-400" />
        </div>
        <div class="text-2xl font-black text-emerald-300 font-mono mt-2">{{ stats.synced }}</div>
        <div class="text-[11px] text-emerald-400/80 mt-1">Berhasil dicetak & di-ACK</div>
      </div>
    </div>

    <!-- Filter & Search Bar -->
    <div class="card-panel p-4 sm:p-5 rounded-2xl space-y-3">
      <div class="grid grid-cols-1 md:grid-cols-12 gap-3 items-end">
        <!-- Search -->
        <div class="md:col-span-4">
          <label class="block text-xs font-semibold text-slate-400 mb-1.5">Pencarian Cepat</label>
          <div class="relative">
            <Search class="w-4 h-4 text-slate-500 absolute left-3 top-1/2 -translate-y-1/2" />
            <input
              v-model="filters.search"
              @keyup.enter="applySearch"
              type="text"
              placeholder="Cari Queue ID, Client, atau Project..."
              class="w-full pl-9 pr-8 py-2 rounded-xl bg-slate-900/90 border border-slate-800 text-xs sm:text-sm text-slate-200 placeholder-slate-500 focus:outline-none focus:border-indigo-500 transition-colors"
            />
            <button
              v-if="filters.search"
              @click="clearSearch"
              class="absolute right-2.5 top-1/2 -translate-y-1/2 text-slate-500 hover:text-slate-200 p-0.5"
            >
              <X class="w-3.5 h-3.5" />
            </button>
          </div>
        </div>

        <!-- Project Filter -->
        <div class="md:col-span-3">
          <label class="block text-xs font-semibold text-slate-400 mb-1.5 flex items-center justify-between">
            <span class="flex items-center gap-1.5">
              <FolderGit2 class="w-3.5 h-3.5 text-indigo-400" />
              <span>Project / Tenant</span>
            </span>
          </label>
          <select
            v-model="filters.project_id"
            @change="handleProjectChange"
            class="input-field !text-xs !py-2"
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
          <label class="block text-xs font-semibold text-slate-400 mb-1.5">Client Node</label>
          <select
            v-model="filters.client_id"
            @change="fetchQueues()"
            class="input-field !text-xs !py-2"
          >
            <option value="">Semua Client</option>
            <option v-for="c in filteredClients" :key="c.id" :value="c.id">
              {{ c.name }}
            </option>
          </select>
        </div>

        <!-- Status filter -->
        <div class="md:col-span-2">
          <label class="block text-xs font-semibold text-slate-400 mb-1.5">Status</label>
          <select
            v-model="filters.status"
            @change="fetchQueues()"
            class="input-field !text-xs !py-2"
          >
            <option value="">Semua Status</option>
            <option value="pending">Pending (Buffer / Offline)</option>
            <option value="dispatched">Dispatched (Reverb)</option>
            <option value="synced">Synced (Selesai ACK)</option>
            <option value="failed">Failed (Gagal)</option>
          </select>
        </div>

        <!-- Reset Button -->
        <div class="md:col-span-1">
          <button
            @click="resetFilters"
            class="w-full py-2 text-xs font-semibold text-slate-400 hover:text-white rounded-xl bg-slate-900 border border-slate-800 hover:bg-slate-800 transition-colors cursor-pointer"
            title="Reset Semua Filter"
          >
            Reset
          </button>
        </div>
      </div>
    </div>

    <!-- Queues Data Container (Zero Horizontal Scroll, 100% Responsive) -->
    <div class="card-panel rounded-2xl overflow-hidden shadow-xl">
      <!-- Desktop & Tablet View (md and up): Fluid Table with 0 horizontal scroll -->
      <div class="hidden md:block">
        <table class="w-full text-left text-xs text-slate-300">
          <thead class="bg-slate-900/90 text-[11px] font-bold uppercase tracking-wider text-slate-400 border-b border-slate-800">
            <tr>
              <th class="px-4 py-3.5 w-[18%]">Queue & Tipe</th>
              <th class="px-4 py-3.5 w-[20%]">Project / Tenant</th>
              <th class="px-4 py-3.5 w-[24%]">Target Client / Node</th>
              <th class="px-4 py-3.5 w-[14%]">Status</th>
              <th class="px-4 py-3.5 w-[14%]">Timestamp</th>
              <th class="px-4 py-3.5 w-[10%] text-right">Aksi</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-800/60 font-sans">
            <tr v-if="loading && queues.length === 0">
              <td colspan="6" class="px-6 py-16 text-center text-slate-400">
                <RefreshCw class="w-6 h-6 animate-spin mx-auto mb-2 text-indigo-400" />
                <span class="text-xs font-medium">Memuat data antrian relay...</span>
              </td>
            </tr>

            <tr v-else-if="queues.length === 0">
              <td colspan="6" class="px-6 py-16 text-center text-slate-400">
                <Layers class="w-8 h-8 mx-auto mb-3 text-slate-500" />
                <p class="font-medium text-slate-300 text-sm">Tidak ada antrian yang cocok.</p>
                <p class="text-xs text-slate-500 mt-1">Coba sesuaikan filter atau lakukan dispatch test dari Overview.</p>
              </td>
            </tr>

            <tr
              v-for="q in queues"
              :key="q.id"
              class="hover:bg-slate-900/50 transition-colors duration-150 group"
            >
              <!-- Queue ID & Tipe -->
              <td class="px-4 py-3.5 align-top">
                <div class="flex items-center gap-1.5 flex-wrap">
                  <span class="font-mono text-xs font-bold text-indigo-400">{{ q.id.substring(0, 8) }}</span>
                  <button
                    @click="copyText(q.id, 'Queue ID')"
                    class="opacity-0 group-hover:opacity-100 text-slate-400 hover:text-white transition-opacity p-0.5 cursor-pointer"
                    title="Copy Full UUID"
                  >
                    <Copy class="w-3 h-3" />
                  </button>
                </div>
                <div class="mt-1">
                  <span
                    class="px-1.5 py-0.5 rounded text-[10px] font-mono font-semibold inline-block"
                    :class="{
                      'bg-indigo-500/15 text-indigo-300 border border-indigo-500/30': q.type === 'print_label',
                      'bg-cyan-500/15 text-cyan-300 border border-cyan-500/30': q.type === 'form_submission',
                      'bg-amber-500/15 text-amber-300 border border-amber-500/30': q.type === 'sync_command',
                    }"
                  >
                    {{ q.type }}
                  </span>
                </div>
              </td>

              <!-- Project / Tenant Column -->
              <td class="px-4 py-3.5 align-top">
                <div v-if="q.client?.project" class="space-y-0.5">
                  <span class="badge-indigo font-mono text-[10px]">
                    <FolderGit2 class="w-2.5 h-2.5 text-indigo-400 shrink-0" />
                    {{ q.client.project.code }}
                  </span>
                  <p class="text-xs font-semibold text-white truncate" :title="q.client.project.name">
                    {{ q.client.project.name }}
                  </p>
                </div>
                <div v-else class="text-xs text-slate-500 italic">
                  <span>Tanpa Project</span>
                </div>
              </td>

              <!-- Target Client -->
              <td class="px-4 py-3.5 align-top">
                <div class="font-bold text-white text-xs truncate" :title="q.client?.name">
                  {{ q.client?.name || 'Unknown Client' }}
                </div>
                <div class="text-[11px] text-slate-400 mt-0.5 font-mono truncate">
                  <span>{{ q.client?.machine_name || 'PC Client' }}</span>
                  <span v-if="q.client?.ip_address" class="text-slate-500 ml-1">({{ q.client.ip_address }})</span>
                </div>
                <div class="text-[10px] text-indigo-400/80 font-mono mt-0.5 truncate">
                  printer.{{ q.client?.slug || 'unknown' }}
                </div>
              </td>

              <!-- Status & Retries -->
              <td class="px-4 py-3.5 align-top">
                <div class="flex items-center gap-1.5 flex-wrap">
                  <span
                    class="inline-flex items-center gap-1.5 px-2 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wider border font-mono"
                    :class="{
                      'badge-amber': q.status === 'pending',
                      'badge-indigo': q.status === 'dispatched',
                      'badge-emerald': q.status === 'synced',
                      'badge-rose': q.status === 'failed',
                    }"
                  >
                    <span
                      class="w-1.5 h-1.5 rounded-full"
                      :class="{
                        'bg-amber-400 animate-pulse': q.status === 'pending',
                        'bg-indigo-400': q.status === 'dispatched',
                        'bg-emerald-400': q.status === 'synced',
                        'bg-rose-400': q.status === 'failed',
                      }"
                    ></span>
                    <span>{{ q.status }}</span>
                  </span>
                  <span v-if="q.retry_count > 0" class="text-[10px] font-mono font-bold text-amber-400">
                    {{ q.retry_count }}x
                  </span>
                </div>
                <div v-if="q.error_message" class="text-[10px] text-rose-400 mt-1 truncate" :title="q.error_message">
                  {{ q.error_message }}
                </div>
              </td>

              <!-- Timestamp -->
              <td class="px-4 py-3.5 align-top font-mono text-[11px]">
                <div class="flex items-center gap-1 text-slate-300">
                  <span class="text-[8px] font-bold text-slate-500 uppercase px-1 py-0.5 rounded bg-slate-900 border border-slate-800/80">IN</span>
                  <span class="text-[10px] sm:text-[11px]">{{ formatTimestamp(q.created_at) }}</span>
                </div>
                <div v-if="q.synced_at" class="flex items-center gap-1 text-emerald-400 mt-1">
                  <span class="text-[8px] font-bold text-emerald-400 uppercase px-1 py-0.5 rounded bg-emerald-500/10 border border-emerald-500/20">OUT</span>
                  <span class="text-[10px] sm:text-[11px]">{{ formatTimestamp(q.synced_at) }}</span>
                </div>
                <div v-else-if="q.status === 'pending'" class="flex items-center gap-1 text-amber-400/80 mt-1">
                  <span class="text-[8px] font-bold text-amber-400 uppercase px-1 py-0.5 rounded bg-amber-500/10 border border-amber-500/20">OUT</span>
                  <span class="text-[9px] italic">menunggu...</span>
                </div>
              </td>

              <!-- Actions -->
              <td class="px-4 py-3.5 align-top text-right whitespace-nowrap">
                <div class="flex items-center justify-end gap-1.5">
                  <button
                    @click="inspectPayload(q)"
                    class="px-2 py-1 rounded-lg bg-slate-900 hover:bg-slate-800 border border-slate-800 text-xs text-slate-300 hover:text-white transition-colors inline-flex items-center gap-1 cursor-pointer font-medium"
                    title="Periksa Detail Payload"
                  >
                    <Eye class="w-3.5 h-3.5 text-indigo-400" />
                    <span>Detail</span>
                  </button>
                  <button
                    @click="resendQueue(q)"
                    :disabled="resendingId === q.id"
                    class="px-2 py-1 rounded-lg bg-indigo-950/40 hover:bg-indigo-900/60 border border-indigo-800/40 text-xs text-indigo-300 hover:text-indigo-200 transition-colors inline-flex items-center gap-1 cursor-pointer font-medium"
                    title="Kirim Ulang via Reverb"
                  >
                    <Send class="w-3.5 h-3.5" :class="{ 'animate-spin': resendingId === q.id }" />
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

      <!-- Mobile Card List View (< md): High-Density Clean Cards -->
      <div class="md:hidden divide-y divide-slate-800/60">
        <div v-if="loading && queues.length === 0" class="p-8 text-center text-slate-400 text-xs">
          <RefreshCw class="w-6 h-6 animate-spin mx-auto mb-2 text-indigo-400" />
          <span>Memuat antrian...</span>
        </div>

        <div v-else-if="queues.length === 0" class="p-8 text-center text-slate-400 text-xs">
          Tidak ada antrian yang cocok.
        </div>

        <div
          v-for="q in queues"
          :key="q.id"
          class="p-4 space-y-2.5 bg-slate-900/40 hover:bg-slate-900/70 transition-colors"
        >
          <!-- Card Header -->
          <div class="flex items-center justify-between gap-2">
            <div class="flex items-center gap-2">
              <span class="font-mono text-xs font-bold text-indigo-400">{{ q.id.substring(0, 8) }}</span>
              <span
                class="px-1.5 py-0.5 rounded text-[9px] font-mono font-semibold"
                :class="{
                  'bg-indigo-500/15 text-indigo-300 border border-indigo-500/30': q.type === 'print_label',
                  'bg-cyan-500/15 text-cyan-300 border border-cyan-500/30': q.type === 'form_submission',
                  'bg-amber-500/15 text-amber-300 border border-amber-500/30': q.type === 'sync_command',
                }"
              >
                {{ q.type }}
              </span>
            </div>
            <span
              class="inline-flex items-center gap-1.5 px-2 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wider border font-mono"
              :class="{
                'badge-amber': q.status === 'pending',
                'badge-indigo': q.status === 'dispatched',
                'badge-emerald': q.status === 'synced',
                'badge-rose': q.status === 'failed',
              }"
            >
              <span class="w-1.5 h-1.5 rounded-full" :class="q.status === 'synced' ? 'bg-emerald-400' : 'bg-amber-400'"></span>
              <span>{{ q.status }}</span>
            </span>
          </div>

          <!-- Project & Target -->
          <div class="text-xs space-y-1">
            <div v-if="q.client?.project" class="flex items-center gap-1.5">
              <span class="badge-indigo font-mono text-[9px]">{{ q.client.project.code }}</span>
              <span class="text-slate-300 font-semibold truncate">{{ q.client.project.name }}</span>
            </div>
            <div class="text-slate-400 font-mono text-[11px]">
              Target: <span class="text-white font-semibold">{{ q.client?.name || 'Unknown' }}</span>
              <span v-if="q.client?.machine_name"> ({{ q.client.machine_name }})</span>
            </div>
          </div>

          <!-- Timestamps & Actions -->
          <div class="pt-2 border-t border-slate-800/80 flex items-center justify-between gap-2 text-[10px] font-mono text-slate-400">
            <div>
              <span>In: {{ formatTimestamp(q.created_at) }}</span>
              <span v-if="q.synced_at" class="text-emerald-400 ml-2">• Out: {{ formatTimestamp(q.synced_at) }}</span>
            </div>
            <div class="flex items-center gap-1.5">
              <button
                @click="inspectPayload(q)"
                class="px-2 py-1 rounded bg-slate-800 text-slate-300 hover:text-white"
              >
                Detail
              </button>
              <button
                @click="resendQueue(q)"
                :disabled="resendingId === q.id"
                class="px-2 py-1 rounded bg-indigo-600 text-white"
              >
                Resend
              </button>
            </div>
          </div>
        </div>
      </div>

      <!-- Pagination Bar -->
      <div v-if="meta.total > 0" class="p-4 bg-slate-900/60 border-t border-slate-800 flex items-center justify-between text-xs text-slate-400">
        <div>
          Menampilkan baris {{ (meta.current_page - 1) * meta.per_page + 1 }} -
          {{ Math.min(meta.current_page * meta.per_page, meta.total) }} dari
          <span class="text-white font-bold font-mono">{{ meta.total }}</span> antrian
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
    <div
      v-if="selectedQueue"
      class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/80 backdrop-blur-sm"
      @click.self="selectedQueue = null"
    >
      <div class="card-panel border border-slate-700/80 rounded-2xl max-w-2xl w-full p-6 shadow-2xl space-y-4 max-h-[85vh] flex flex-col bg-[#0d1424]">
        <div class="flex items-center justify-between pb-3 border-b border-slate-800">
          <div>
            <h3 class="text-sm sm:text-base font-bold text-white flex items-center gap-2">
              <Eye class="w-4 h-4 text-indigo-400" />
              <span>Detail Antrian & Payload</span>
            </h3>
            <p class="text-[11px] text-slate-400 font-mono mt-0.5">ID: {{ selectedQueue.id }}</p>
          </div>
          <button @click="selectedQueue = null" class="text-slate-400 hover:text-white p-1 cursor-pointer">
            <X class="w-4 h-4" />
          </button>
        </div>

        <!-- Project & Client Metadata -->
        <div class="grid grid-cols-2 gap-3 p-3.5 rounded-xl bg-slate-900/70 border border-slate-800/80 text-xs">
          <div>
            <span class="text-slate-500 block mb-1">Project Tenant:</span>
            <div v-if="selectedQueue.client?.project" class="flex items-center gap-2">
              <span class="badge-indigo font-mono text-[10px]">
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
        <div class="flex-1 overflow-y-auto space-y-2">
          <div class="flex items-center justify-between text-xs text-slate-400">
            <span class="font-mono text-[11px]">Payload Content ({{ selectedQueue.type }}):</span>
            <button
              @click="copyText(JSON.stringify(selectedQueue.payload, null, 2), 'Payload JSON')"
              class="text-indigo-400 hover:text-indigo-300 flex items-center gap-1 cursor-pointer font-medium"
            >
              <Copy class="w-3.5 h-3.5" />
              <span>Copy JSON</span>
            </button>
          </div>

          <pre class="p-4 rounded-xl bg-[#080c14] border border-slate-800 text-xs font-mono text-indigo-200 overflow-x-auto selection:bg-indigo-600 selection:text-white max-h-[280px]">{{ JSON.stringify(selectedQueue.payload, null, 2) }}</pre>
        </div>

        <!-- Modal Footer -->
        <div class="pt-3 border-t border-slate-800 flex items-center justify-between">
          <button
            @click="resendQueue(selectedQueue)"
            :disabled="resendingId === selectedQueue.id"
            class="btn-primary !text-xs !py-1.5"
          >
            <Send class="w-3.5 h-3.5" />
            <span>Kirim Ulang Sekarang</span>
          </button>
          <button
            @click="selectedQueue = null"
            class="btn-secondary !text-xs !py-1.5"
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
import { useToast } from '../composables/useToast';

const toast = useToast();
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
    const day = String(d.getDate()).padStart(2, '0');
    const months = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];
    const month = months[d.getMonth()];
    const hours = String(d.getHours()).padStart(2, '0');
    const minutes = String(d.getMinutes()).padStart(2, '0');
    const seconds = String(d.getSeconds()).padStart(2, '0');
    return `${day} ${month} ${hours}:${minutes}:${seconds}`;
  } catch {
    return ts;
  }
};

const filteredClients = computed(() => {
  if (!filters.project_id) {
    return clients.value;
  }
  if (filters.project_id === 'none') {
    return clients.value.filter((c) => !c.project_client_id);
  }
  return clients.value.filter((c) => c.project_client_id === filters.project_id);
});

const quickFilterStatus = (statusValue) => {
  filters.status = statusValue;
  filters.page = 1;
  fetchQueues();
};

const handleProjectChange = () => {
  if (filters.client_id) {
    const matched = filteredClients.value.find((c) => c.id === filters.client_id);
    if (!matched) filters.client_id = '';
  }
  filters.page = 1;
  fetchQueues();
};

const applySearch = () => {
  filters.page = 1;
  fetchQueues();
};

const clearSearch = () => {
  filters.search = '';
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

const fetchQueues = async (isManual = false) => {
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
      if (isManual) {
        toast.success('Daftar antrian diperbarui.');
      }
    }
  } catch (err) {
    console.error('Failed to load queues', err);
    if (isManual) {
      toast.error('Gagal memuat antrian.');
    }
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
  toast.info('Filter antrian di-reset.');
};

const inspectPayload = (q) => {
  selectedQueue.value = q;
};

const copyText = async (text, label = 'Teks') => {
  try {
    await navigator.clipboard.writeText(text);
    toast.success(`${label} berhasil disalin!`);
  } catch (e) {
    toast.error(`Gagal menyalin ${label}.`);
  }
};

const resendQueue = async (q) => {
  resendingId.value = q.id;
  try {
    const res = await axios.post(`/api/v1/admin/queues/${q.id}/resend`);
    if (res.data.success) {
      toast.success('Antrian berhasil dikirim ulang ke Reverb WebSocket!');
      await fetchQueues();
      if (selectedQueue.value && selectedQueue.value.id === q.id) {
        selectedQueue.value.status = 'pending';
      }
    }
  } catch (err) {
    toast.error('Gagal resend: ' + (err.response?.data?.message || err.message));
  } finally {
    resendingId.value = null;
  }
};

const deleteQueue = async (id) => {
  if (!confirm('Apakah Anda yakin ingin menghapus item antrian ini?')) return;

  try {
    const res = await axios.delete(`/api/v1/admin/queues/${id}`);
    if (res.data.success) {
      toast.success('Item antrian berhasil dihapus.');
      await fetchQueues();
    }
  } catch (err) {
    toast.error('Gagal menghapus antrian: ' + (err.response?.data?.message || err.message));
  }
};

onMounted(() => {
  fetchProjects();
  fetchClients();
  fetchQueues();
});
</script>
