<template>
  <div class="space-y-8">
    <!-- Header banner -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
      <div>
        <h1 class="text-2xl sm:text-3xl font-extrabold text-white tracking-tight flex items-center gap-3">
          <span>Relay Host Overview</span>
          <span class="text-xs px-2.5 py-1 rounded-full bg-emerald-500/10 text-emerald-400 border border-emerald-500/20 font-medium">
            System Operational
          </span>
        </h1>
        <p class="text-slate-400 text-sm mt-1">
          Penyangga Antrian (Store & Forward) & Relayer Event Real-Time Berbasis Laravel Reverb.
        </p>
      </div>

      <!-- Quick Actions -->
      <div class="flex items-center gap-3">
        <button
          @click="openTestDispatchModal"
          class="px-4 py-2 rounded-xl bg-gradient-to-r from-indigo-600 to-indigo-500 hover:from-indigo-500 hover:to-indigo-400 text-white text-sm font-semibold shadow-lg shadow-indigo-600/30 transition-all duration-200 flex items-center gap-2 cursor-pointer active:scale-95"
        >
          <Send class="w-4 h-4" />
          <span>Dispatch Test Job</span>
        </button>
        <button
          @click="fetchStats"
          :disabled="loading"
          class="p-2 rounded-xl bg-slate-900 border border-slate-800 hover:bg-slate-800 text-slate-300 transition-all duration-150 cursor-pointer disabled:opacity-50"
          title="Refresh Data"
        >
          <RefreshCw class="w-4 h-4" :class="{ 'animate-spin': loading }" />
        </button>
      </div>
    </div>

    <!-- KPI Metric Cards Grid -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
      <!-- Clients Online -->
      <div class="glass-card rounded-2xl p-5 relative overflow-hidden group hover:border-indigo-500/40 transition-all duration-300">
        <div class="flex items-center justify-between">
          <span class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Active Clients</span>
          <div class="w-9 h-9 rounded-xl bg-emerald-500/10 border border-emerald-500/20 flex items-center justify-center text-emerald-400">
            <Cpu class="w-5 h-5" />
          </div>
        </div>
        <div class="mt-4 flex items-baseline gap-2">
          <span class="text-3xl font-extrabold text-white">{{ stats.kpis?.online_clients || 0 }}</span>
          <span class="text-xs text-slate-400">/ {{ stats.kpis?.total_clients || 0 }} total</span>
        </div>
        <p class="text-xs text-emerald-400 mt-2 flex items-center gap-1.5">
          <span class="w-1.5 h-1.5 rounded-full bg-emerald-400 animate-ping"></span>
          <span>{{ stats.kpis?.online_clients || 0 }} mesin aktif (heartbeat &lt; 2m)</span>
        </p>
      </div>

      <!-- Pending Queues -->
      <div class="glass-card rounded-2xl p-5 relative overflow-hidden group hover:border-amber-500/40 transition-all duration-300">
        <div class="flex items-center justify-between">
          <span class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Pending Queues</span>
          <div class="w-9 h-9 rounded-xl bg-amber-500/10 border border-amber-500/20 flex items-center justify-center text-amber-400">
            <Clock class="w-5 h-5" />
          </div>
        </div>
        <div class="mt-4 flex items-baseline gap-2">
          <span class="text-3xl font-extrabold text-amber-400">{{ stats.kpis?.pending_queues || 0 }}</span>
          <span class="text-xs text-slate-400">jobs di antrian</span>
        </div>
        <p class="text-xs text-slate-400 mt-2">
          Disimpan sementara (Store & Forward)
        </p>
      </div>

      <!-- Synced Today -->
      <div class="glass-card rounded-2xl p-5 relative overflow-hidden group hover:border-indigo-500/40 transition-all duration-300">
        <div class="flex items-center justify-between">
          <span class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Synced Today</span>
          <div class="w-9 h-9 rounded-xl bg-indigo-500/10 border border-indigo-500/20 flex items-center justify-center text-indigo-400">
            <CheckCircle2 class="w-5 h-5" />
          </div>
        </div>
        <div class="mt-4 flex items-baseline gap-2">
          <span class="text-3xl font-extrabold text-white">{{ stats.kpis?.synced_today || 0 }}</span>
          <span class="text-xs text-slate-400">sukses dieksekusi</span>
        </div>
        <p class="text-xs text-indigo-300 mt-2">
          Terkonfirmasi via client ACK endpoint
        </p>
      </div>

      <!-- Failed Queues -->
      <div class="glass-card rounded-2xl p-5 relative overflow-hidden group hover:border-rose-500/40 transition-all duration-300">
        <div class="flex items-center justify-between">
          <span class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Failed / Errors</span>
          <div class="w-9 h-9 rounded-xl bg-rose-500/10 border border-rose-500/20 flex items-center justify-center text-rose-400">
            <AlertTriangle class="w-5 h-5" />
          </div>
        </div>
        <div class="mt-4 flex items-baseline gap-2">
          <span class="text-3xl font-extrabold text-rose-400">{{ stats.kpis?.failed_queues || 0 }}</span>
          <span class="text-xs text-slate-400">kegagalan</span>
        </div>
        <p class="text-xs text-slate-400 mt-2">
          Dapat dikirim ulang manual (Resend)
        </p>
      </div>
    </div>

    <!-- Main Content: Live Activity Stream + Connected Clients Table -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
      <!-- Live Realtime Reverb Activity Stream (2 cols) -->
      <div class="lg:col-span-2 glass-panel rounded-2xl border border-slate-800 p-6 flex flex-col">
        <div class="flex items-center justify-between pb-4 border-b border-slate-800/80 mb-4">
          <div class="flex items-center gap-2.5">
            <div class="relative flex h-3 w-3">
              <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-indigo-400 opacity-75"></span>
              <span class="relative inline-flex rounded-full h-3 w-3 bg-indigo-500"></span>
            </div>
            <div>
              <h2 class="text-base font-bold text-white tracking-tight">Live Activity Stream</h2>
              <p class="text-xs text-slate-400">Mendengarkan event Reverb <code class="text-indigo-300 font-mono">dashboard.activity</code> secara real-time</p>
            </div>
          </div>
          <div class="flex items-center gap-2">
            <span class="text-xs text-slate-400 font-mono">{{ activityLog.length }} events</span>
            <button
              @click="activityLog = []"
              class="text-xs text-slate-400 hover:text-slate-200 px-2 py-1 rounded bg-slate-900 border border-slate-800"
            >
              Clear
            </button>
          </div>
        </div>

        <!-- Activity Feed List -->
        <div class="flex-1 overflow-y-auto max-h-[420px] space-y-2.5 pr-1">
          <div v-if="activityLog.length === 0" class="text-center py-16 text-slate-400 text-sm">
            <Radio class="w-8 h-8 mx-auto mb-2 text-slate-400 animate-pulse" />
            <p>Menunggu aktivitas baru...</p>
            <p class="text-xs text-slate-400 mt-1">Coba tekan tombol "Dispatch Test Job" di kanan atas.</p>
          </div>

          <div
            v-for="(act, idx) in activityLog"
            :key="act.id || idx"
            class="p-3 rounded-xl border border-slate-800/80 bg-slate-900/40 hover:bg-slate-900/70 transition-all duration-150 flex items-start justify-between gap-3 text-sm"
          >
            <div class="flex items-start gap-3">
              <span
                class="mt-0.5 px-2 py-0.5 rounded text-[11px] font-mono font-semibold uppercase tracking-wider"
                :class="{
                  'bg-indigo-500/20 text-indigo-300 border border-indigo-500/30': act.status === 'dispatched',
                  'bg-emerald-500/20 text-emerald-300 border border-emerald-500/30': act.status === 'synced',
                  'bg-rose-500/20 text-rose-300 border border-rose-500/30': act.status === 'failed',
                  'bg-cyan-500/20 text-cyan-300 border border-cyan-500/30': act.status === 'online' || act.status === 'registered',
                  'bg-slate-700 text-slate-300': !['dispatched', 'synced', 'failed', 'online', 'registered'].includes(act.status)
                }"
              >
                {{ act.status }}
              </span>
              <div>
                <p class="text-slate-200 font-medium leading-snug">{{ act.message }}</p>
                <div class="flex items-center gap-2 mt-1 text-xs text-slate-400">
                  <span class="font-semibold text-slate-300">{{ act.client_name }}</span>
                  <span>•</span>
                  <span class="font-mono text-[11px] text-slate-400">{{ formatTime(act.timestamp) }}</span>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Quick Client Status Summary (1 col) -->
      <div class="glass-panel rounded-2xl border border-slate-800 p-6 flex flex-col justify-between">
        <div>
          <div class="flex items-center justify-between pb-4 border-b border-slate-800/80 mb-4">
            <div>
              <h2 class="text-base font-bold text-white tracking-tight">Registered Clients</h2>
              <p class="text-xs text-slate-400">Status koneksi mesin lokal</p>
            </div>
            <router-link to="/clients" class="text-xs text-indigo-400 hover:text-indigo-300 font-semibold flex items-center gap-1">
              <span>Manage</span>
              <ArrowRight class="w-3.5 h-3.5" />
            </router-link>
          </div>

          <!-- Clients list -->
          <div class="space-y-3">
            <div
              v-for="client in stats.clients || []"
              :key="client.id"
              class="p-3 rounded-xl border border-slate-800/80 bg-slate-900/40 flex items-center justify-between gap-2"
            >
              <div class="min-w-0">
                <div class="flex items-center gap-2">
                  <span class="font-semibold text-sm text-slate-200 truncate">{{ client.name }}</span>
                  <span
                    class="text-[10px] uppercase font-mono px-1.5 py-0.5 rounded"
                    :class="client.scope === 'printer_service' ? 'bg-purple-500/10 text-purple-300 border border-purple-500/20' : 'bg-sky-500/10 text-sky-300 border border-sky-500/20'"
                  >
                    {{ client.scope }}
                  </span>
                </div>
                <p class="text-xs text-slate-400 mt-0.5 font-mono truncate">
                  {{ client.machine_name || client.ip_address || 'No hardware reported' }}
                </p>
              </div>

              <div class="flex items-center gap-1.5 flex-shrink-0">
                <span
                  class="inline-flex items-center gap-1.5 px-2 py-0.5 rounded-full text-xs font-medium"
                  :class="client.is_online ? 'bg-emerald-500/10 text-emerald-400 border border-emerald-500/20' : 'bg-slate-800 text-slate-400 border border-slate-700'"
                >
                  <span class="w-1.5 h-1.5 rounded-full" :class="client.is_online ? 'bg-emerald-400' : 'bg-slate-500'"></span>
                  <span>{{ client.is_online ? 'Online' : 'Offline' }}</span>
                </span>
              </div>
            </div>

            <div v-if="!stats.clients || stats.clients.length === 0" class="text-center py-6 text-slate-400 text-xs">
              Belum ada client terdaftar.
            </div>
          </div>
        </div>

        <!-- Buffer Queue status summary box -->
        <div class="mt-6 p-4 rounded-xl bg-indigo-950/30 border border-indigo-500/20">
          <div class="flex items-start gap-3">
            <div class="p-2 rounded-lg bg-indigo-500/20 text-indigo-300">
              <ShieldCheck class="w-5 h-5" />
            </div>
            <div>
              <h4 class="text-xs font-bold text-indigo-200 uppercase tracking-wider">Zero-Inbound Security</h4>
              <p class="text-xs text-indigo-300/80 mt-1 leading-relaxed">
                PC Client lokal tidak memerlukan IP Publik atau Ngrok. Client menggunakan koneksi WebSocket Outbound ke port 8090.
              </p>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Test Dispatch Modal -->
    <div v-if="showTestModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/80 backdrop-blur-sm">
      <div class="glass-panel border border-slate-800 rounded-2xl max-w-lg w-full p-6 shadow-2xl space-y-4">
        <div class="flex items-center justify-between pb-3 border-b border-slate-800">
          <div class="flex items-center gap-2">
            <Send class="w-5 h-5 text-indigo-400" />
            <h3 class="text-base font-bold text-white">Dispatch Test Job (Sandbox)</h3>
          </div>
          <button @click="showTestModal = false" class="text-slate-400 hover:text-white">✕</button>
        </div>

        <form @submit.prevent="submitTestDispatch" class="space-y-4">
          <div>
            <label class="block text-xs font-medium text-slate-300 mb-1">Pilih Target Client</label>
            <select
              v-model="testForm.target_client_id"
              class="w-full rounded-xl bg-slate-900 border border-slate-800 text-sm text-slate-100 p-2.5 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500"
              required
            >
              <option value="" disabled>-- Pilih Client --</option>
              <option v-for="c in stats.clients" :key="c.id" :value="c.id">
                {{ c.name }} ({{ c.scope }}) - {{ c.is_online ? '🟢 Online' : '⚪ Offline' }}
              </option>
            </select>
          </div>

          <div>
            <label class="block text-xs font-medium text-slate-300 mb-1">Tipe Event</label>
            <select
              v-model="testForm.type"
              class="w-full rounded-xl bg-slate-900 border border-slate-800 text-sm text-slate-100 p-2.5 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500"
            >
              <option value="print_label">Cetak Label (print_label)</option>
              <option value="form_submission">Form Service Intake (form_submission)</option>
              <option value="sync_command">Perintah Sinkronisasi (sync_command)</option>
            </select>
          </div>

          <div v-if="testForm.type === 'print_label'">
            <label class="block text-xs font-medium text-slate-300 mb-1">Label Kategori</label>
            <input
              type="text"
              v-model="testForm.label_category"
              class="w-full rounded-xl bg-slate-900 border border-slate-800 text-sm text-slate-100 p-2.5 focus:border-indigo-500"
              placeholder="Contoh: Gelang Pasien, Etiket Obat, cetak_serial"
            />
          </div>

          <div>
            <label class="block text-xs font-medium text-slate-300 mb-1">Jumlah Lembar (Qty)</label>
            <input
              type="number"
              v-model.number="testForm.qty"
              min="1"
              max="50"
              class="w-full rounded-xl bg-slate-900 border border-slate-800 text-sm text-slate-100 p-2.5 focus:border-indigo-500"
            />
          </div>

          <div class="flex items-center justify-end gap-3 pt-3 border-t border-slate-800">
            <button
              type="button"
              @click="showTestModal = false"
              class="px-4 py-2 rounded-xl text-sm text-slate-400 hover:text-slate-200"
            >
              Batal
            </button>
            <button
              type="submit"
              :disabled="dispatching"
              class="px-5 py-2 rounded-xl bg-indigo-600 hover:bg-indigo-500 text-white text-sm font-semibold shadow-lg shadow-indigo-600/30 flex items-center gap-2 cursor-pointer disabled:opacity-50"
            >
              <Send class="w-4 h-4" />
              <span>{{ dispatching ? 'Mengirim...' : 'Kirim ke Relay' }}</span>
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

const loading = ref(false);
const dispatching = ref(false);
const stats = ref({});
const activityLog = ref([]);
const showTestModal = ref(false);

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

const fetchStats = async () => {
  loading.value = true;
  try {
    const res = await axios.get('/api/v1/admin/stats');
    if (res.data.success) {
      stats.value = res.data.data;
    }
  } catch (err) {
    console.error('Failed to load stats', err);
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
      await fetchStats();
    }
  } catch (err) {
    alert('Gagal mengirim dispatch: ' + (err.response?.data?.message || err.message));
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
