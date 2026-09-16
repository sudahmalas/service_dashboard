<template>
  <div class="space-y-7 max-w-5xl">
    <!-- Header -->
    <div>
      <h1 class="text-2xl font-extrabold text-white tracking-tight flex items-center gap-2.5">
        <Code class="w-6 h-6 text-indigo-400" />
        <span>Client Integration Guide</span>
      </h1>
      <p class="text-slate-400 text-xs sm:text-sm mt-1">
        Panduan langkah demi langkah menghubungkan aplikasi klien (Prima & Printer Service) ke WebHost Relay.
      </p>
    </div>

    <!-- Segmented Tab Navigation -->
    <div class="flex items-center gap-2 border-b border-slate-800 pb-3 overflow-x-auto">
      <button
        @click="activeTab = 'printer'"
        class="px-3.5 py-2 rounded-xl text-xs sm:text-sm font-semibold transition-all duration-150 flex items-center gap-2 cursor-pointer shrink-0"
        :class="activeTab === 'printer' ? 'bg-indigo-600 text-white shadow-sm' : 'text-slate-400 hover:text-slate-200 hover:bg-slate-900'"
      >
        <Printer class="w-4 h-4" />
        <span>1. Printer Service (Electron Client)</span>
      </button>

      <button
        @click="activeTab = 'prima'"
        class="px-3.5 py-2 rounded-xl text-xs sm:text-sm font-semibold transition-all duration-150 flex items-center gap-2 cursor-pointer shrink-0"
        :class="activeTab === 'prima' ? 'bg-indigo-600 text-white shadow-sm' : 'text-slate-400 hover:text-slate-200 hover:bg-slate-900'"
      >
        <MonitorSmartphone class="w-4 h-4" />
        <span>2. Prima (Business / Input POS)</span>
      </button>

      <button
        @click="activeTab = 'architecture'"
        class="px-3.5 py-2 rounded-xl text-xs sm:text-sm font-semibold transition-all duration-150 flex items-center gap-2 cursor-pointer shrink-0"
        :class="activeTab === 'architecture' ? 'bg-indigo-600 text-white shadow-sm' : 'text-slate-400 hover:text-slate-200 hover:bg-slate-900'"
      >
        <Workflow class="w-4 h-4" />
        <span>3. Arsitektur Store & Forward</span>
      </button>
    </div>

    <!-- Tab 1: Printer Service -->
    <div v-if="activeTab === 'printer'" class="space-y-6">
      <div class="card-panel rounded-2xl p-6 space-y-4">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2">
          <h3 class="text-base sm:text-lg font-bold text-white flex items-center gap-2">
            <span>Konfigurasi di Printer Service</span>
          </h3>
          <span class="badge-emerald font-mono text-[10px]">
            Zero Code Change Compatible
          </span>
        </div>
        <p class="text-xs sm:text-sm text-slate-300 leading-relaxed">
          Printer Service yang berjalan di PC kasir/loket dapat langsung terhubung ke WebHost tanpa perlu mengubah baris kode logika WebSocket-nya. Cukup ubah konfigurasi server di menu <strong>Settings</strong> aplikasi Printer Service:
        </p>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-xs font-mono">
          <div class="p-3.5 rounded-xl bg-slate-900/90 border border-slate-800">
            <span class="text-slate-400 block mb-1">Main Application URL:</span>
            <span class="text-indigo-300 font-bold">http://localhost:8000</span>
            <span class="text-slate-500 block mt-1 text-[11px]">(Atau IP VPS / Cloudflare Tunnel)</span>
          </div>
          <div class="p-3.5 rounded-xl bg-slate-900/90 border border-slate-800">
            <span class="text-slate-400 block mb-1">API Key Client:</span>
            <span class="text-emerald-400 font-bold">ps_counter01_testkey9988...</span>
            <span class="text-slate-500 block mt-1 text-[11px]">(Ambil dari menu Clients di WebHost)</span>
          </div>
        </div>

        <div class="space-y-2.5 pt-2">
          <h4 class="text-xs font-bold text-slate-200 uppercase tracking-wider">Cara Kerja Koneksi:</h4>
          <ol class="list-decimal list-inside space-y-2 text-xs text-slate-300 leading-relaxed">
            <li>Printer Service memanggil <code class="text-indigo-300 font-mono">POST /api/print-service/connect-info</code> dengan API Key.</li>
            <li>WebHost membalas dengan kredensial Reverb di port <code class="text-indigo-300 font-mono">8090</code> dan nama channel unik (misal: <code class="text-indigo-300 font-mono">printer.Loket-Pendaftaran-1</code>).</li>
            <li>Echo di Printer Service otomatis tersambung ke Reverb WebSocket dan mendengarkan event <code class="text-indigo-300 font-mono">PrintJobDispatched</code>.</li>
            <li>Saat menerima event, payload langsung diteruskan ke port lokal <code class="text-indigo-300 font-mono">http://127.0.0.1:18181/print</code> untuk mencetak secara instan (*silent print*).</li>
          </ol>
        </div>
      </div>

      <!-- On-Boot Sync Code Snippet -->
      <div class="card-panel rounded-2xl p-6 space-y-3">
        <div class="flex items-center justify-between">
          <h4 class="text-sm font-bold text-white flex items-center gap-2">
            <CheckCircle2 class="w-4 h-4 text-emerald-400" />
            <span>Optimalisasi: On-Boot Sync & ACK (Store & Forward)</span>
          </h4>
          <button
            @click="copySnippet(bootSnippet, 'Snippet On-Boot Sync')"
            class="text-xs text-indigo-400 hover:text-indigo-300 flex items-center gap-1 cursor-pointer font-medium"
          >
            <Copy class="w-3.5 h-3.5" />
            <span>Copy Snippet</span>
          </button>
        </div>
        <p class="text-xs text-slate-400 leading-relaxed">
          Untuk memastikan antrian cetak saat PC mati dapat langsung dicetak saat baru dinyalakan, panggil sinkronisasi saat aplikasi Printer Service pertama kali terbuka:
        </p>

        <pre class="p-4 rounded-xl bg-[#080c14] text-xs font-mono text-indigo-200 overflow-x-auto border border-slate-800 leading-relaxed">{{ bootSnippet }}</pre>
      </div>
    </div>

    <!-- Tab 2: Prima Application -->
    <div v-if="activeTab === 'prima'" class="space-y-6">
      <div class="card-panel rounded-2xl p-6 space-y-4">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2">
          <h3 class="text-base sm:text-lg font-bold text-white flex items-center gap-2">
            <span>Penerapan Additive pada Aplikasi Prima (Non-Breaking)</span>
          </h3>
          <span class="badge-emerald font-mono text-[10px]">
            Zero Breaking Changes
          </span>
        </div>

        <p class="text-xs sm:text-sm text-slate-300 leading-relaxed">
          Penerapan pada Prima dilakukan secara <strong>aditif (tidak merusak struktur eksisting)</strong> agar tidak error saat menggunakan printer lokal (<code class="text-indigo-300 font-mono">localhost:18181</code>) maupun saat masih menggunakan Pusher lama.
        </p>

        <!-- 3-Tier Execution Flow -->
        <div class="p-4 rounded-xl bg-slate-900/80 border border-slate-800 space-y-2.5 text-xs">
          <div class="font-bold text-slate-200 uppercase tracking-wider text-[11px]">
            Tingkatan Eksekusi Cetak (3-Tier Fallback):
          </div>
          <div class="flex items-start gap-2 text-emerald-400">
            <span class="font-bold shrink-0">1. Plan A (Localhost 18181):</span>
            <span class="text-slate-300 font-normal">
              Tetap dieksekusi pertama kali. Jika Prima & printer ada di PC yang sama, cetak langsung tanpa lewat jaringan.
            </span>
          </div>
          <div class="flex items-start gap-2 text-indigo-400">
            <span class="font-bold shrink-0">2. Plan B (WebHost Multi-Tenant Relay):</span>
            <span class="text-slate-300 font-normal">
              Jika Plan A gagal (misal Prima diakses via browser cloud), relay ke WebHost dengan proteksi isolasi tenant via header <code class="text-indigo-300 font-mono">X-Project-Key</code>.
            </span>
          </div>
          <div class="flex items-start gap-2 text-amber-400">
            <span class="font-bold shrink-0">3. Plan C (Legacy Fallback):</span>
            <span class="text-slate-300 font-normal">
              Jika WebHost tidak dikonfigurasi, sistem mundur ke endpoint backend Prima tanpa melempar crash.
            </span>
          </div>
        </div>

        <!-- .env configuration -->
        <div class="space-y-2">
          <div class="flex items-center justify-between">
            <h4 class="text-xs font-bold uppercase tracking-wider text-slate-400">1. Konfigurasi Environment Prima (.env)</h4>
            <button
              @click="copySnippet(envSnippet, 'Konfigurasi .env')"
              class="text-xs text-indigo-400 hover:text-indigo-300 flex items-center gap-1 cursor-pointer font-medium"
            >
              <Copy class="w-3.5 h-3.5" />
              <span>Copy</span>
            </button>
          </div>
          <pre class="p-4 rounded-xl bg-[#080c14] text-xs font-mono text-indigo-200 overflow-x-auto border border-slate-800 leading-relaxed">{{ envSnippet }}</pre>
        </div>

        <!-- Additive code in useCetakLabel.js -->
        <div class="space-y-2">
          <div class="flex items-center justify-between">
            <h4 class="text-xs font-bold uppercase tracking-wider text-slate-400">2. Cuplikan Kode Additive di <code class="text-indigo-300">useCetakLabel.js</code></h4>
            <button
              @click="copySnippet(composableSnippet, 'Cuplikan useCetakLabel.js')"
              class="text-xs text-indigo-400 hover:text-indigo-300 flex items-center gap-1 cursor-pointer font-medium"
            >
              <Copy class="w-3.5 h-3.5" />
              <span>Copy</span>
            </button>
          </div>
          <pre class="p-4 rounded-xl bg-[#080c14] text-xs font-mono text-indigo-200 overflow-x-auto border border-slate-800 leading-relaxed max-h-72">{{ composableSnippet }}</pre>
        </div>
      </div>
    </div>

    <!-- Tab 3: Architecture Diagram -->
    <div v-if="activeTab === 'architecture'" class="space-y-6">
      <div class="card-panel rounded-2xl p-6 space-y-4">
        <h3 class="text-base sm:text-lg font-bold text-white">Alur Komunikasi Lengkap (Store & Forward)</h3>
        <div class="p-5 rounded-xl bg-slate-900/80 border border-slate-800 text-xs font-mono text-slate-300 space-y-4 leading-relaxed">
          <div>
            <div class="text-indigo-400 font-bold mb-1">1. OUTBOUND WEBSOCKET DARI KLIEN LOKAL:</div>
            <p class="pl-4 text-slate-400">PC Printer Service di loket menginisiasi koneksi ke <span class="text-emerald-400">ws://webhost:8090</span>. Tidak perlu IP publik atau port forwarding di PC loket.</p>
          </div>

          <div>
            <div class="text-indigo-400 font-bold mb-1">2. BUFFER QUEUE DI HOST:</div>
            <p class="pl-4 text-slate-400">Saat ada permintaan cetak dari Prima, Host menyimpan payload ke database <span class="text-amber-400">message_queues (status: pending)</span>.</p>
          </div>

          <div>
            <div class="text-indigo-400 font-bold mb-1">3. REALTIME PUSH KE LOKET YANG TEPAT:</div>
            <p class="pl-4 text-slate-400">Host langsung mem-broadcast ke channel Reverb <span class="text-cyan-400">printer.{slug}</span> milik PC tersebut (latensi &lt; 50ms).</p>
          </div>

          <div>
            <div class="text-indigo-400 font-bold mb-1">4. EKSEKUSI & KONFIRMASI (ACK):</div>
            <p class="pl-4 text-slate-400">Printer Service mencetak dokumen fisik ke port 18181, lalu mengirimkan <span class="text-emerald-400">POST /api/v1/client/ack</span> ke Host. Status antrian berubah menjadi <span class="text-emerald-400">synced</span>.</p>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue';
import { Code, Printer, MonitorSmartphone, Workflow, CheckCircle2, Copy } from 'lucide-vue-next';
import { useToast } from '../composables/useToast';

const toast = useToast();
const activeTab = ref('printer');

const copySnippet = async (code, label) => {
  try {
    await navigator.clipboard.writeText(code);
    toast.success(`${label} disalin ke clipboard!`);
  } catch (e) {
    toast.error('Gagal menyalin kode.');
  }
};

const bootSnippet = `// Tambahkan di fungsi inisialisasi Printer Service (App Boot)
async function syncOfflineJobsOnBoot() {
  try {
    const res = await axios.get(\`\${mainAppUrl}/api/v1/client/sync\`, {
      headers: { 'X-Client-Key': apiKey }
    });

    if (res.data.success && res.data.count > 0) {
      console.log(\`[Sync] Ditemukan \${res.data.count} job pending selama offline. Memproses...\`);
      for (const item of res.data.data) {
        // Eksekusi cetak ke printer lokal port 18181
        await client.post('/print', item.payload);
        
        // Kirim konfirmasi ACK ke WebHost
        await axios.post(\`\${mainAppUrl}/api/v1/client/ack\`, {
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
}`;

const envSnippet = `# URL Host Relay WebHost
VITE_WEBHOST_URL=http://localhost:8000

# Project API Key per Rumah Sakit / Unit (Ambil dari menu Projects di WebHost)
VITE_PROJECT_API_KEY=proj_a8f9c2d1e0b3456789abcdef01234567

# Port default printer lokal (tetap tidak berubah)
VITE_PRINT_SERVICE_PORT=18181`;

const composableSnippet = `// Di dalam blok Plan B fallback di prima/src/composables/useCetakLabel.js:
const WEBHOST_URL = import.meta.env.VITE_WEBHOST_URL
const PROJECT_KEY = import.meta.env.VITE_PROJECT_API_KEY

let dispatchSuccess = false

// Prioritas 1: Jika WebHost & Project Key tersedia, gunakan WebHost Relay (Store & Forward)
if (WEBHOST_URL && PROJECT_KEY) {
  try {
    const webhostRes = await fetch(\`\${WEBHOST_URL}/api/print-service/dispatch\`, {
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
}`;
</script>
