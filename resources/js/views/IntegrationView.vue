<template>
  <div class="space-y-8 max-w-5xl">
    <!-- Header -->
    <div>
      <h1 class="text-2xl font-extrabold text-white tracking-tight flex items-center gap-2">
        <Code class="w-7 h-7 text-indigo-400" />
        <span>Client Integration Guide</span>
      </h1>
      <p class="text-slate-400 text-sm mt-1">
        Panduan langkah demi langkah menghubungkan aplikasi klien (Prima & Printer Service) ke WebHost Relay.
      </p>
    </div>

    <!-- Tab Selection -->
    <div class="flex items-center gap-2 border-b border-slate-800 pb-3">
      <button
        @click="activeTab = 'printer'"
        class="px-4 py-2 rounded-xl text-sm font-semibold transition-all duration-150 flex items-center gap-2 cursor-pointer"
        :class="activeTab === 'printer' ? 'bg-indigo-600/20 text-indigo-300 border border-indigo-500/30' : 'text-slate-400 hover:text-slate-200'"
      >
        <Printer class="w-4 h-4" />
        <span>1. Printer Service (Electron Client)</span>
      </button>

      <button
        @click="activeTab = 'prima'"
        class="px-4 py-2 rounded-xl text-sm font-semibold transition-all duration-150 flex items-center gap-2 cursor-pointer"
        :class="activeTab === 'prima' ? 'bg-indigo-600/20 text-indigo-300 border border-indigo-500/30' : 'text-slate-400 hover:text-slate-200'"
      >
        <MonitorSmartphone class="w-4 h-4" />
        <span>2. Prima (Business / Input Application)</span>
      </button>

      <button
        @click="activeTab = 'architecture'"
        class="px-4 py-2 rounded-xl text-sm font-semibold transition-all duration-150 flex items-center gap-2 cursor-pointer"
        :class="activeTab === 'architecture' ? 'bg-indigo-600/20 text-indigo-300 border border-indigo-500/30' : 'text-slate-400 hover:text-slate-200'"
      >
        <Workflow class="w-4 h-4" />
        <span>3. Arsitektur Store & Forward</span>
      </button>
    </div>

    <!-- Tab 1: Printer Service -->
    <div v-if="activeTab === 'printer'" class="space-y-6">
      <div class="glass-card rounded-2xl p-6 border border-slate-800 space-y-4">
        <h3 class="text-lg font-bold text-white flex items-center gap-2">
          <span>Konfigurasi di Printer Service</span>
          <span class="text-xs px-2 py-0.5 rounded bg-emerald-500/10 text-emerald-400 border border-emerald-500/20">Zero Code Change Compatible</span>
        </h3>
        <p class="text-sm text-slate-300 leading-relaxed">
          Printer Service yang berjalan di PC kasir/loket dapat langsung terhubung ke WebHost tanpa perlu mengubah baris kode logika WebSocket-nya. Cukup ubah konfigurasi server di menu <strong>Settings</strong> aplikasi Printer Service:
        </p>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-xs font-mono">
          <div class="p-3.5 rounded-xl bg-slate-900 border border-slate-800">
            <span class="text-slate-400 block mb-1">Main Application URL:</span>
            <span class="text-indigo-300 font-bold">http://localhost:8000</span>
            <span class="text-slate-400 block mt-1">(Atau IP VPS Host Anda)</span>
          </div>
          <div class="p-3.5 rounded-xl bg-slate-900 border border-slate-800">
            <span class="text-slate-400 block mb-1">API Key:</span>
            <span class="text-emerald-400 font-bold">ps_counter01_testkey99887766554433221100</span>
            <span class="text-slate-400 block mt-1">(Ambil dari menu Clients di dashboard)</span>
          </div>
        </div>

        <div class="space-y-3 pt-2">
          <h4 class="text-sm font-semibold text-slate-200">Cara Kerja Koneksi:</h4>
          <ol class="list-decimal list-inside space-y-2 text-xs text-slate-300">
            <li>Printer Service memanggil <code class="text-indigo-300 font-mono">POST /api/print-service/connect-info</code> dengan API Key.</li>
            <li>WebHost membalas dengan kredensial Reverb di port <code class="text-indigo-300 font-mono">8090</code> dan nama channel unik (misal: <code class="text-indigo-300 font-mono">printer.Loket-Pendaftaran-1</code>).</li>
            <li>Echo di Printer Service otomatis tersambung ke Reverb WebSocket dan mendengarkan event <code class="text-indigo-300 font-mono">PrintJobDispatched</code>.</li>
            <li>Saat menerima event, payload langsung diteruskan ke port lokal <code class="text-indigo-300 font-mono">http://127.0.0.1:18181/print</code> untuk mencetak secara instan (*silent print*).</li>
          </ol>
        </div>
      </div>

      <!-- On-Boot Sync Code Snippet -->
      <div class="glass-panel rounded-2xl p-6 border border-slate-800 space-y-3">
        <h4 class="text-sm font-bold text-white flex items-center gap-2">
          <CheckCircle2 class="w-4 h-4 text-emerald-400" />
          <span>Optimalisasi: On-Boot Sync & ACK (Tanpa Cron di PC Klien)</span>
        </h4>
        <p class="text-xs text-slate-300">
          Untuk memastikan antrian cetak saat PC mati dapat langsung dicetak saat baru dinyalakan, tambahkan panggilan sinkronisasi saat aplikasi Printer Service pertama kali terbuka:
        </p>

        <pre class="p-4 rounded-xl bg-slate-900 text-xs font-mono text-slate-200 overflow-x-auto border border-slate-800">
// Tambahkan di fungsi inisialisasi Printer Service (App Boot)
async function syncOfflineJobsOnBoot() {
  try {
    const res = await axios.get(`${mainAppUrl}/api/v1/client/sync`, {
      headers: { 'X-Client-Key': apiKey }
    });

    if (res.data.success && res.data.count > 0) {
      console.log(`[Sync] Ditemukan ${res.data.count} job pending selama offline. Memproses...`);
      for (const item of res.data.data) {
        // Eksekusi cetak ke printer lokal port 18181
        await client.post('/print', item.payload);
        
        // Kirim konfirmasi ACK ke WebHost
        await axios.post(`${mainAppUrl}/api/v1/client/ack`, {
          message_id: item.id,
          status: 'synced'
        }, {
          headers: { 'X-Client-Key': apiKey }
        });
      }
    }
  } catch (err) {
    console.error('Gagal sync job offline:', err);
  }
}</pre>
      </div>
    </div>

    <!-- Tab 2: Prima Application -->
    <div v-if="activeTab === 'prima'" class="space-y-6">
      <div class="glass-card rounded-2xl p-6 border border-slate-800 space-y-4">
        <div class="flex items-center justify-between">
          <h3 class="text-lg font-bold text-white flex items-center gap-2">
            <span>Penerapan Additive pada Aplikasi Prima (Non-Breaking)</span>
          </h3>
          <span class="text-xs px-2.5 py-1 rounded-full bg-emerald-500/10 text-emerald-300 border border-emerald-500/20 font-semibold">
            Zero Breaking Changes
          </span>
        </div>

        <p class="text-sm text-slate-300 leading-relaxed">
          Penerapan pada Prima dilakukan secara <strong>aditif (tidak mengubah struktur eksisting)</strong> agar tidak error saat menggunakan printer lokal (<code class="text-indigo-300 font-mono">localhost:18181</code>) maupun saat masih menggunakan Pusher lama.
        </p>

        <!-- 3-Tier Execution Flow -->
        <div class="p-4 rounded-xl bg-slate-900 border border-slate-800 space-y-3 text-xs">
          <div class="font-bold text-slate-200 uppercase tracking-wider text-[11px]">
            Tingkatan Eksekusi Cetak (3-Tier Fallback):
          </div>
          <div class="flex items-start gap-2 text-emerald-400">
            <span class="font-bold">1. Plan A (Localhost 18181):</span>
            <span class="text-slate-300 font-normal">
              Tetap dieksekusi pertama kali. Jika Prima & printer ada di PC yang sama, cetak langsung tanpa lewat jaringan.
            </span>
          </div>
          <div class="flex items-start gap-2 text-indigo-400">
            <span class="font-bold">2. Plan B (WebHost Multi-Tenant Relay):</span>
            <span class="text-slate-300 font-normal">
              Jika Plan A gagal (misal Prima diakses via browser/cloud), cek apakah <code class="text-indigo-300 font-mono">VITE_WEBHOST_URL</code> dan <code class="text-indigo-300 font-mono">VITE_PROJECT_API_KEY</code> aktif. Jika ada, kirim ke WebHost dengan proteksi isolasi project.
            </span>
          </div>
          <div class="flex items-start gap-2 text-amber-400">
            <span class="font-bold">3. Plan C (Legacy Pusher Fallback):</span>
            <span class="text-slate-300 font-normal">
              Jika WebHost tidak dikonfigurasi atau gagal, sistem secara otomatis mundur ke endpoint Pusher lama tanpa melempar crash.
            </span>
          </div>
        </div>

        <!-- .env configuration -->
        <div class="space-y-2">
          <h4 class="text-xs font-bold uppercase tracking-wider text-slate-400">1. Konfigurasi Environment Prima (.env)</h4>
          <pre class="p-4 rounded-xl bg-slate-900 text-xs font-mono text-slate-200 overflow-x-auto border border-slate-800">
# URL Host Relay WebHost
VITE_WEBHOST_URL=http://localhost:8000

# Project API Key per Rumah Sakit / Unit (Ambil dari menu Projects di WebHost)
# Contoh untuk Prima CSSD (Kuota 4 Printer):
VITE_PROJECT_API_KEY=proj_a8f9c2d1e0b3456789abcdef01234567

# Port default printer lokal (tetap tidak berubah)
VITE_PRINT_SERVICE_PORT=18181</pre>
        </div>

        <!-- Additive code in useCetakLabel.js -->
        <div class="space-y-2">
          <h4 class="text-xs font-bold uppercase tracking-wider text-slate-400">2. Cuplikan Kode Additive di <code class="text-indigo-300">useCetakLabel.js</code></h4>
          <pre class="p-4 rounded-xl bg-slate-900 text-xs font-mono text-slate-200 overflow-x-auto border border-slate-800">
// Di dalam blok Plan B fallback di prima/src/composables/useCetakLabel.js:
const WEBHOST_URL = import.meta.env.VITE_WEBHOST_URL
const PROJECT_KEY = import.meta.env.VITE_PROJECT_API_KEY

let dispatchSuccess = false

// Prioritas 1: Jika WebHost & Project Key tersedia, gunakan WebHost Relay (Store & Forward)
if (WEBHOST_URL && PROJECT_KEY) {
  try {
    const webhostRes = await fetch(`${WEBHOST_URL}/api/print-service/dispatch`, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-Project-Key': PROJECT_KEY // Isolasi printer unit rumah sakit
      },
      body: JSON.stringify({ jobs: networkJobs })
    })
    const data = await webhostRes.json()
    if (data.success && data.data?.dispatched > 0) {
      dispatchSuccess = true
      onNotify({ title: 'Cetak Berhasil', message: 'Terkirim ke printer via WebHost Relay.', variant: 'success' })
      safeClearSelection()
    }
  } catch (webhostErr) {
    console.warn('[Print] WebHost relay unavailable, falling back to legacy Pusher...', webhostErr)
  }
}

// Prioritas 2: Fallback ke Pusher lama jika WebHost belum ada atau gagal
if (!dispatchSuccess) {
  const dispatchRes = await api.post('/print-service/dispatch', { jobs: networkJobs })
  if (dispatchRes.data.success && dispatchRes.data.data.dispatched > 0) {
    onNotify({ title: 'Cetak Berhasil', message: 'Berhasil mencetak via Pusher.', variant: 'success' })
    safeClearSelection()
  } else {
    throw new Error('Tidak ada PC Printer yang aktif atau sesuai untuk mencetak label ini.')
  }
}</pre>
        </div>

        <!-- Fetch allocated printers -->
        <div class="space-y-2">
          <h4 class="text-xs font-bold uppercase tracking-wider text-slate-400">3. Menampilkan Daftar Printer Terisolasi (Opsional)</h4>
          <p class="text-xs text-slate-300">
            Aplikasi Prima dapat mengambil daftar printer yang secara eksklusif dialokasikan untuk unitnya melalui endpoint:
          </p>
          <pre class="p-4 rounded-xl bg-slate-900 text-xs font-mono text-slate-200 overflow-x-auto border border-slate-800">
// Mengambil printer milik project (hanya menampilkan printer yang diizinkan untuk unit ini)
const response = await fetch(`${WEBHOST_URL}/api/v1/project/printers`, {
  headers: { 'X-Project-Key': PROJECT_KEY }
});
const { data: allocatedPrinters } = await response.json();
// Hasil: Array printer dengan status online, hardware info, & target labels</pre>
        </div>
      </div>
    </div>

    <!-- Tab 3: Architecture Diagram -->
    <div v-if="activeTab === 'architecture'" class="space-y-6">
      <div class="glass-panel rounded-2xl p-6 border border-slate-800 space-y-4">
        <h3 class="text-lg font-bold text-white">Alur Komunikasi Lengkap (Store & Forward)</h3>
        <div class="p-4 rounded-xl bg-slate-900/80 border border-slate-800 text-xs font-mono text-slate-300 space-y-3 leading-relaxed">
          <div class="text-indigo-400 font-bold">1. OUTBOUND WEBSOCKET DARI KLIEN LOKAL:</div>
          <p class="pl-4">PC Printer Service di loket inisiasi koneksi ke <span class="text-emerald-400">ws://webhost:8090</span>. Tidak perlu IP publik atau Ngrok di PC loket.</p>

          <div class="text-indigo-400 font-bold">2. BUFFER QUEUE DI HOST:</div>
          <p class="pl-4">Saat ada permintaan cetak dari Prima, Host menyimpan payload ke database <span class="text-amber-400">message_queues (status: pending)</span>.</p>

          <div class="text-indigo-400 font-bold">3. REALTIME PUSH KE LOKET YANG TEPAT:</div>
          <p class="pl-4">Host langsung mem-broadcast ke channel Reverb <span class="text-cyan-400">printer.{slug}</span> milik PC tersebut (latensi &lt; 100ms).</p>

          <div class="text-indigo-400 font-bold">4. EKSEKUSI & KONFIRMASI (ACK):</div>
          <p class="pl-4">Printer Service mencetak dokumen fisik, lalu mengirimkan <span class="text-emerald-400">POST /api/v1/client/ack</span> ke Host. Status antrian berubah menjadi <span class="text-emerald-400">synced</span>.</p>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue';
import { Code, Printer, MonitorSmartphone, Workflow, CheckCircle2 } from 'lucide-vue-next';

const activeTab = ref('printer');
</script>
