<template>
  <div class="space-y-7">
    <!-- Header Banner & Controls -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
      <div>
        <div class="flex items-center gap-2.5">
          <h1 class="text-2xl font-extrabold text-white tracking-tight">Central Relay Overview</h1>
          <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[11px] font-semibold bg-emerald-500/10 text-emerald-400 border border-emerald-500/20 font-mono">
            <span class="w-1.5 h-1.5 rounded-full bg-emerald-400 animate-pulse"></span>
            OPERATIONAL
          </span>
        </div>
        <p class="text-slate-400 text-xs sm:text-sm mt-1">
          Penyangga antrian (Store & Forward) dan relayer event real-time berbasis Laravel Reverb.
        </p>
      </div>

      <!-- Quick Action Controls -->
      <div class="flex items-center gap-2.5">
        <button
          @click="openTestDispatchModal"
          class="btn-primary"
        >
          <Send class="w-3.5 h-3.5" />
          <span>Dispatch Test Job</span>
        </button>
        <button
          @click="fetchStats(true)"
          :disabled="loading"
          class="btn-secondary !p-2"
          title="Refresh Data Dashboard"
        >
          <RefreshCw class="w-4 h-4" :class="{ 'animate-spin': loading }" />
        </button>
      </div>
    </div>

    <!-- KPI Metric Cards Grid (Anti-Slop Modern Surfaces) -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
      <!-- KPI 1: Clients Online -->
      <div class="card-panel rounded-2xl p-5 relative overflow-hidden transition-all duration-200 hover:border-slate-700">
        <div class="flex items-center justify-between">
          <span class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Active Clients</span>
          <div class="w-9 h-9 rounded-xl bg-emerald-500/10 border border-emerald-500/20 flex items-center justify-center text-emerald-400">
            <Cpu class="w-4 h-4" />
          </div>
        </div>
        <div class="mt-3.5 flex items-baseline gap-2">
          <span class="text-3xl font-extrabold text-white font-mono tracking-tight">{{ stats.kpis?.online_clients || 0 }}</span>
          <span class="text-xs text-slate-500 font-mono">/ {{ stats.kpis?.total_clients || 0 }} total node</span>
        </div>
        <div class="mt-3 flex items-center gap-1.5 text-xs text-emerald-400 font-medium">
          <span class="relative flex h-2 w-2">
            <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
            <span class="relative inline-flex rounded-full h-2 w-2 bg-emerald-400"></span>
          </span>
          <span>Heartbeat aktif (&lt; 2m)</span>
        </div>
      </div>

      <!-- KPI 2: Pending Queues -->
      <div class="card-panel rounded-2xl p-5 relative overflow-hidden transition-all duration-200 hover:border-slate-700">
        <div class="flex items-center justify-between">
          <span class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Pending Buffers</span>
          <div class="w-9 h-9 rounded-xl bg-amber-500/10 border border-amber-500/20 flex items-center justify-center text-amber-400">
            <Clock class="w-4 h-4" />
          </div>
        </div>
        <div class="mt-3.5 flex items-baseline gap-2">
          <span class="text-3xl font-extrabold text-amber-400 font-mono tracking-tight">{{ stats.kpis?.pending_queues || 0 }}</span>
          <span class="text-xs text-slate-500 font-mono">jobs tersimpan</span>
        </div>
        <div class="mt-3 text-xs text-slate-400 flex items-center gap-1">
          <span>Menunggu PC loket online (Store & Forward)</span>
        </div>
      </div>

      <!-- KPI 3: Synced Today -->
      <div class="card-panel rounded-2xl p-5 relative overflow-hidden transition-all duration-200 hover:border-slate-700">
        <div class="flex items-center justify-between">
          <span class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Synced Today</span>
          <div class="w-9 h-9 rounded-xl bg-indigo-500/10 border border-indigo-500/20 flex items-center justify-center text-indigo-400">
            <CheckCircle2 class="w-4 h-4" />
          </div>
        </div>
        <div class="mt-3.5 flex items-baseline gap-2">
          <span class="text-3xl font-extrabold text-white font-mono tracking-tight">{{ stats.kpis?.synced_today || 0 }}</span>
          <span class="text-xs text-slate-500 font-mono">sukses dieksekusi</span>
        </div>
        <div class="mt-3 text-xs text-indigo-300/90 flex items-center gap-1">
          <span>Terkonfirmasi via client ACK endpoint</span>
        </div>
      </div>

      <!-- KPI 4: Failed Queues -->
      <div class="card-panel rounded-2xl p-5 relative overflow-hidden transition-all duration-200 hover:border-slate-700">
        <div class="flex items-center justify-between">
          <span class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Failed / Errors</span>
          <div class="w-9 h-9 rounded-xl bg-rose-500/10 border border-rose-500/20 flex items-center justify-center text-rose-400">
            <AlertTriangle class="w-4 h-4" />
          </div>
        </div>
        <div class="mt-3.5 flex items-baseline gap-2">
          <span class="text-3xl font-extrabold font-mono tracking-tight" :class="(stats.kpis?.failed_queues || 0) > 0 ? 'text-rose-400' : 'text-slate-400'">
            {{ stats.kpis?.failed_queues || 0 }}
          </span>
          <span class="text-xs text-slate-500 font-mono">kegagalan cetak</span>
        </div>
        <div class="mt-3 text-xs text-slate-400 flex items-center justify-between">
          <span>Dapat di-resend via Queues</span>
          <router-link to="/queues" class="text-indigo-400 hover:underline text-[11px]">Inspect →</router-link>
        </div>
      </div>
    </div>

    <!-- Main Grid: Real-Time Stream + Connected Nodes Matrix -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
      <!-- Live Activity Stream (2 cols) -->
      <div class="lg:col-span-2 card-panel rounded-2xl p-5 sm:p-6 flex flex-col">
        <!-- Stream Header -->
        <div class="flex flex-col sm:flex-row sm:items-center justify-between pb-4 border-b border-slate-800/80 gap-3">
          <div class="flex items-center gap-2.5">
            <div class="relative flex h-2.5 w-2.5">
              <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-indigo-400 opacity-75"></span>
              <span class="relative inline-flex rounded-full h-2.5 w-2.5 bg-indigo-500"></span>
            </div>
            <div>
              <h2 class="text-sm sm:text-base font-bold text-white tracking-tight">Live Activity Stream</h2>
              <p class="text-[11px] text-slate-400">
                Listening to Reverb event <code class="text-indigo-300 font-mono">dashboard.activity</code>
              </p>
            </div>
          </div>

          <!-- Controls: Filter chips + Clear -->
          <div class="flex items-center gap-2 flex-wrap">
            <span class="text-[11px] font-mono text-slate-400 px-2 py-0.5 rounded bg-slate-900 border border-slate-800">
              {{ activityLog.length }} events
            </span>
            <button
              v-if="activityLog.length > 0"
              @click="clearActivityLog"
              class="btn-ghost !text-xs !py-1"
            >
              Clear
            </button>
          </div>
        </div>

        <!-- Activity Feed List -->
        <div class="flex-1 overflow-y-auto max-h-[460px] space-y-2 pt-4 pr-1">
          <!-- Empty State -->
          <div v-if="activityLog.length === 0" class="text-center py-16 text-slate-400">
            <div class="w-12 h-12 rounded-2xl bg-slate-900 border border-slate-800 flex items-center justify-center mx-auto mb-3 text-slate-500">
              <Radio class="w-6 h-6 animate-pulse text-indigo-400" />
            </div>
            <p class="font-medium text-slate-300 text-sm">Menunggu aktivitas baru dari sistem...</p>
            <p class="text-xs text-slate-400 mt-1 max-w-sm mx-auto">
              Event relay, pendaftaran klien baru, atau cetak dari Prima akan tampil secara instan di sini.
            </p>
            <button
              @click="openTestDispatchModal"
              class="mt-4 px-3.5 py-1.5 rounded-xl bg-slate-900 border border-slate-800 hover:border-slate-700 text-xs text-indigo-300 font-medium cursor-pointer"
            >
              Kirim Test Job Sekarang →
            </button>
          </div>

          <!-- Activity Item Card -->
          <div
            v-for="(act, idx) in activityLog"
            :key="act.id || idx"
            class="p-3.5 rounded-xl border border-slate-800/80 bg-slate-900/40 hover:bg-slate-900/70 transition-all duration-150 flex items-start justify-between gap-3 text-sm group"
          >
            <div class="flex items-start gap-3 min-w-0">
              <!-- Distinct Status Chip -->
              <span
                class="mt-0.5 px-2 py-0.5 rounded text-[10px] font-mono font-bold uppercase tracking-wider flex-shrink-0"
                :class="{
                  'badge-indigo': act.status === 'dispatched',
                  'badge-emerald': act.status === 'synced',
                  'badge-rose': act.status === 'failed',
                  'badge-cyan': act.status === 'online' || act.status === 'registered',
                  'badge-neutral': !['dispatched', 'synced', 'failed', 'online', 'registered'].includes(act.status)
                }"
              >
                {{ act.status }}
              </span>

              <div class="min-w-0">
                <p class="text-slate-200 text-xs sm:text-sm font-medium leading-snug break-words">
                  {{ act.message }}
                </p>
                <div class="flex items-center gap-2 mt-1 text-[11px] text-slate-400">
                  <span class="font-semibold text-slate-300">{{ act.client_name || 'System Host' }}</span>
                  <span>•</span>
                  <span class="font-mono text-slate-400">{{ formatTime(act.timestamp) }}</span>
                  <template v-if="act.payload">
                    <span>•</span>
                    <button
                      @click="toggleExpandPayload(idx)"
                      class="text-indigo-400 hover:text-indigo-300 cursor-pointer font-mono"
                    >
                      {{ expandedPayloads[idx] ? 'Tutup Payload [-]' : 'Lihat Payload [+]' }}
                    </button>
                  </template>
                </div>

                <!-- Expandable Payload Preview -->
                <div
                  v-if="act.payload && expandedPayloads[idx]"
                  class="mt-2.5 p-3 rounded-lg bg-[#080c14] border border-slate-800 font-mono text-[11px] text-slate-300 overflow-x-auto max-h-48"
                >
                  <pre>{{ JSON.stringify(act.payload, null, 2) }}</pre>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Connected Clients / Nodes Matrix (1 col) -->
      <div class="card-panel rounded-2xl p-5 sm:p-6 flex flex-col justify-between">
        <div>
          <div class="flex items-center justify-between pb-4 border-b border-slate-800/80 mb-4">
            <div>
              <h2 class="text-sm sm:text-base font-bold text-white tracking-tight">Registered Clients</h2>
              <p class="text-[11px] text-slate-400">Daftar node mesin kasir/loket fisik</p>
            </div>
            <router-link to="/clients" class="text-xs text-indigo-400 hover:text-indigo-300 font-semibold flex items-center gap-1">
              <span>Kelola</span>
              <ArrowRight class="w-3.5 h-3.5" />
            </router-link>
          </div>

          <!-- Clients List -->
          <div class="space-y-2.5">
            <div
              v-for="client in stats.clients || []"
              :key="client.id"
              class="p-3 rounded-xl border border-slate-800/80 bg-slate-900/50 hover:bg-slate-900/80 transition-colors flex items-center justify-between gap-3"
            >
              <div class="min-w-0">
                <div class="flex items-center gap-2">
                  <span class="font-semibold text-xs sm:text-sm text-slate-200 truncate">{{ client.name }}</span>
                  <span
                    class="text-[9px] uppercase font-mono px-1.5 py-0.5 rounded"
                    :class="client.scope === 'printer_service' ? 'bg-purple-500/10 text-purple-300 border border-purple-500/20' : 'bg-sky-500/10 text-sky-300 border border-sky-500/20'"
                  >
                    {{ client.scope }}
                  </span>
                </div>
                <div class="text-[11px] text-slate-400 mt-0.5 font-mono truncate flex items-center gap-1.5">
                  <span class="text-slate-500">Node:</span>
                  <span>{{ client.machine_name || client.ip_address || 'No hardware reported' }}</span>
                </div>
              </div>

              <!-- Status Badge -->
              <div class="flex items-center gap-1.5 flex-shrink-0">
                <span
                  class="inline-flex items-center gap-1.5 px-2 py-0.5 rounded-full text-[11px] font-medium"
                  :class="client.is_online ? 'bg-emerald-500/10 text-emerald-400 border border-emerald-500/20' : 'bg-slate-800 text-slate-400 border border-slate-700'"
                >
                  <span class="w-1.5 h-1.5 rounded-full" :class="client.is_online ? 'bg-emerald-400 animate-pulse' : 'bg-slate-500'"></span>
                  <span>{{ client.is_online ? 'Online' : 'Offline' }}</span>
                </span>
              </div>
            </div>

            <!-- Empty Clients -->
            <div v-if="!stats.clients || stats.clients.length === 0" class="text-center py-8 text-slate-400 text-xs">
              <Cpu class="w-6 h-6 mx-auto mb-2 text-slate-400" />
              <span>Belum ada client terdaftar.</span>
            </div>
          </div>
        </div>

        <!-- Zero-Inbound Security Card -->
        <div class="mt-6 p-4 rounded-xl bg-indigo-950/20 border border-indigo-500/20">
          <div class="flex items-start gap-3">
            <div class="p-2 rounded-lg bg-indigo-500/20 text-indigo-300 mt-0.5 flex-shrink-0">
              <ShieldCheck class="w-4 h-4" />
            </div>
            <div>
              <h4 class="text-xs font-bold text-indigo-200 uppercase tracking-wider">Zero-Inbound Security</h4>
              <p class="text-[11px] text-indigo-300/80 mt-1 leading-relaxed">
                PC Client lokal tidak memerlukan IP Publik atau port-forwarding. Koneksi dilakukan secara Outbound WebSocket ke port 8090.
              </p>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Dispatch Test Job Modal (Sandbox) -->
    <div
      v-if="showTestModal"
      class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/80 backdrop-blur-sm"
      @click.self="showTestModal = false"
    >
      <div class="card-panel border border-slate-700/80 rounded-2xl max-w-lg w-full p-6 shadow-2xl space-y-5 bg-[#0d1424]">
        <div class="flex items-center justify-between pb-3 border-b border-slate-800">
          <div class="flex items-center gap-2.5">
            <div class="p-2 rounded-xl bg-indigo-500/10 border border-indigo-500/20 text-indigo-400">
              <Send class="w-4 h-4" />
            </div>
            <div>
              <h3 class="text-sm font-bold text-white">Dispatch Test Job (Sandbox)</h3>
              <p class="text-[11px] text-slate-400">Uji coba pengiriman payload ke mesin client terdaftar</p>
            </div>
          </div>
          <button @click="showTestModal = false" class="text-slate-400 hover:text-white p-1 cursor-pointer">✕</button>
        </div>

        <form @submit.prevent="submitTestDispatch" class="space-y-4">
          <div>
            <label class="block text-xs font-semibold text-slate-300 mb-1.5">Pilih Target Client / Node</label>
            <select
              v-model="testForm.target_client_id"
              class="input-field"
              required
            >
              <option value="" disabled>-- Pilih Client --</option>
              <option v-for="c in stats.clients" :key="c.id" :value="c.id">
                {{ c.name }} ({{ c.scope }}) - {{ c.is_online ? '🟢 Online' : '⚪ Offline' }}
              </option>
            </select>
          </div>

          <div>
            <label class="block text-xs font-semibold text-slate-300 mb-1.5">Tipe Event</label>
            <select
              v-model="testForm.type"
              class="input-field"
            >
              <option value="print_label">Cetak Label (print_label)</option>
              <option value="form_submission">Form Service Intake (form_submission)</option>
              <option value="sync_command">Perintah Sinkronisasi (sync_command)</option>
            </select>
          </div>

          <div v-if="testForm.type === 'print_label'">
            <label class="block text-xs font-semibold text-slate-300 mb-1.5">Label Kategori</label>
            <input
              type="text"
              v-model="testForm.label_category"
              class="input-field"
              placeholder="Contoh: Gelang Pasien, Etiket Obat, cetak_serial"
            />
          </div>

          <div>
            <label class="block text-xs font-semibold text-slate-300 mb-1.5">Jumlah Lembar (Qty)</label>
            <input
              type="number"
              v-model.number="testForm.qty"
              min="1"
              max="50"
              class="input-field"
            />
          </div>

          <div class="flex items-center justify-end gap-2.5 pt-4 border-t border-slate-800">
            <button
              type="button"
              @click="showTestModal = false"
              class="btn-ghost"
            >
              Batal
            </button>
            <button
              type="submit"
              :disabled="dispatching"
              class="btn-primary"
            >
              <Send class="w-3.5 h-3.5" />
              <span>{{ dispatching ? 'Mengirim ke Relay...' : 'Kirim Sekarang' }}</span>
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive, onMounted, onUnmounted } from 'vue';
import axios from 'axios';
import {
  Activity,
  Cpu,
  Clock,
  CheckCircle2,
  AlertTriangle,
  Send,
  RefreshCw,
  Radio,
  ArrowRight,
  ShieldCheck,
} from 'lucide-vue-next';
import echo from '../echo';
import { useToast } from '../composables/useToast';

const toast = useToast();
const loading = ref(false);
const dispatching = ref(false);
const stats = ref({});
const activityLog = ref([]);
const showTestModal = ref(false);
const expandedPayloads = ref({});

const testForm = reactive({
  target_client_id: '',
  type: 'print_label',
  label_category: 'Gelang Pasien',
  qty: 1,
});

const formatTime = (ts) => {
  if (!ts) return '';
  try {
    return new Date(ts).toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit', second: '2-digit' });
  } catch {
    return ts;
  }
};

const toggleExpandPayload = (idx) => {
  expandedPayloads.value[idx] = !expandedPayloads.value[idx];
};

const clearActivityLog = () => {
  activityLog.value = [];
  toast.info('Activity stream dibersihkan.');
};

const fetchStats = async (isManual = false) => {
  loading.value = true;
  try {
    const res = await axios.get('/api/v1/admin/stats');
    if (res.data.success) {
      stats.value = res.data.data;
      if (isManual) {
        toast.success('Data overview berhasil diperbarui.');
      }
    }
  } catch (err) {
    console.error('Failed to load stats', err);
    if (isManual) {
      toast.error('Gagal mengambil data statistik.');
    }
  } finally {
    loading.value = false;
  }
};

const openTestDispatchModal = () => {
  if (stats.value.clients && stats.value.clients.length > 0) {
    testForm.target_client_id = stats.value.clients[0].id;
  }
  showTestModal.value = true;
};

const submitTestDispatch = async () => {
  if (!testForm.target_client_id) {
    toast.warning('Pilih target client terlebih dahulu.');
    return;
  }

  dispatching.value = true;
  try {
    const payload = testForm.type === 'print_label'
      ? {
          jobs: [
            {
              category: testForm.label_category,
              printer_name: 'system_default',
              qty: testForm.qty,
              lebar_mm: 50,
              tinggi_mm: 25,
              url: 'data:text/html;charset=utf-8,' + encodeURIComponent(`
                <div style="font-family: sans-serif; text-align: center; padding: 8px;">
                  <h3 style="margin: 0; font-size: 14px;">ID-GROW TEST PRINT</h3>
                  <p style="margin: 4px 0; font-size: 11px;">Kategori: ${testForm.label_category}</p>
                  <p style="margin: 0; font-size: 10px; color: #666;">Waktu: ${new Date().toLocaleTimeString()}</p>
                </div>
              `)
            }
          ]
        }
      : {
          sample_form_id: 'FORM-' + Math.floor(Math.random() * 90000 + 10000),
          customer_name: 'Budi Santoso',
          device: 'Laptop ThinkPad T480',
          issue: 'Ganti Pasta Pendingin & Install Ulang'
        };

    const res = await axios.post('/api/v1/relay/dispatch', {
      target_client_id: testForm.target_client_id,
      type: testForm.type,
      payload: payload
    });

    if (res.data.success) {
      showTestModal.value = false;
      toast.success('Test job berhasil dikirim ke antrian relay!');
      await fetchStats();
    }
  } catch (err) {
    toast.error('Gagal mengirim dispatch: ' + (err.response?.data?.message || err.message));
  } finally {
    dispatching.value = false;
  }
};

// Setup Reverb WebSocket Listener
onMounted(() => {
  fetchStats();

  try {
    if (echo) {
      echo.channel('dashboard.activity')
        .listen('.activity.logged', (e) => {
          activityLog.value.unshift(e);
          if (activityLog.value.length > 50) activityLog.value.pop();
          fetchStats();
        })
        .listen('activity.logged', (e) => {
          activityLog.value.unshift(e);
          if (activityLog.value.length > 50) activityLog.value.pop();
          fetchStats();
        });
    }
  } catch (e) {
    console.warn('Echo listener error:', e);
  }
});

onUnmounted(() => {
  try {
    if (echo) {
      echo.leaveChannel('dashboard.activity');
    }
  } catch (e) {}
});
</script>
