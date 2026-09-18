<template>
  <div class="min-h-[85vh] max-w-lg mx-auto py-4">
    <!-- Brand / Header -->
    <div class="text-center mb-5">
      <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-slate-900 border border-slate-800 text-[11px] font-mono text-slate-400 mb-2">
        <Wrench class="w-3.5 h-3.5 text-indigo-400" />
        <span>IPSRS & Teknisi Elektromedis • WebHost Gateway</span>
      </div>
      <h1 class="text-xl font-bold text-white tracking-tight">Formulir Laporan Maintenance</h1>
      <p class="text-xs text-slate-400 mt-1">Laporan akan diteruskan ke database lokal Rumah Sakit melalui Store & Forward.</p>
    </div>

    <!-- Success Screen State -->
    <div v-if="submittedSuccess" class="card-panel p-6 sm:p-8 rounded-2xl text-center space-y-4 border-emerald-500/30 bg-emerald-950/10">
      <div class="w-14 h-14 rounded-2xl bg-emerald-500/15 border border-emerald-500/30 flex items-center justify-center mx-auto text-emerald-400">
        <CheckCircle2 class="w-7 h-7" />
      </div>
      <div>
        <h2 class="text-base sm:text-lg font-bold text-white">Laporan Berhasil Terkirim!</h2>
        <p class="text-xs text-slate-300 mt-1.5 leading-relaxed">
          Data laporan teknisi Anda telah disimpan secara aman di antrian WebHost dan siap disinkronkan ke aplikasi Prima di jaringan lokal rumah sakit.
        </p>
      </div>

      <div class="p-3.5 rounded-xl bg-slate-900/90 border border-slate-800 font-mono text-xs space-y-1 text-left">
        <div class="flex justify-between text-slate-400">
          <span>Queue Ticket ID:</span>
          <span class="text-indigo-400 font-bold">{{ submittedData?.queue_id?.substring(0, 13) }}...</span>
        </div>
        <div class="flex justify-between text-slate-400">
          <span>Waktu Kirim:</span>
          <span class="text-slate-200">{{ formatSubmittedTime(submittedData?.submitted_at) }}</span>
        </div>
        <div class="flex justify-between text-slate-400">
          <span>Status Antrian:</span>
          <span class="text-amber-400 font-bold uppercase">Pending Sync</span>
        </div>
      </div>

      <div class="pt-2 flex flex-col sm:flex-row gap-2">
        <button
          @click="resetForm"
          class="btn-secondary w-full justify-center text-xs py-2.5"
        >
          Isi Laporan Lain
        </button>
        <router-link
          v-if="identifier"
          :to="`/a/${identifier}`"
          class="btn-primary w-full justify-center text-xs py-2.5"
        >
          Kembali ke Info Alat
        </router-link>
      </div>
    </div>

    <!-- Maintenance Entry Form -->
    <div v-else class="card-panel rounded-2xl p-5 sm:p-6 shadow-2xl border-slate-700/80 space-y-5">
      <!-- Target Unit Header Card -->
      <div class="p-3.5 rounded-xl bg-slate-900/90 border border-slate-800 flex items-center justify-between gap-3">
        <div class="min-w-0">
          <span class="text-[10px] font-bold text-indigo-400 uppercase tracking-wider block font-mono">
            Target Unit / Alat
          </span>
          <div class="font-bold text-slate-100 text-sm truncate">
            {{ itemSnapshot?.title || form.identifier || 'Alat Medis' }}
          </div>
          <div class="text-[11px] text-slate-400 font-mono mt-0.5">
            {{ itemSnapshot?.code || identifier || 'Tanpa Kode Barcode' }}
          </div>
        </div>
        <span
          class="px-2 py-1 rounded text-[10px] font-mono font-bold uppercase shrink-0 border"
          :class="itemSnapshot ? 'bg-indigo-500/10 text-indigo-300 border-indigo-500/20' : 'bg-slate-800 text-slate-400 border-slate-700'"
        >
          {{ itemSnapshot?.category || 'Alat Fisik' }}
        </span>
      </div>

      <form @submit.prevent="submitReport" class="space-y-4 text-xs">
        <!-- Performer / Technician Name -->
        <div>
          <label class="block font-semibold text-slate-300 mb-1">
            Nama Teknisi / Petugas Pelaksana <span class="text-rose-400">*</span>
          </label>
          <input
            type="text"
            v-model="form.performer_name"
            class="input-field"
            placeholder="Contoh: Budi Santoso (Teknisi IPSRS)"
            required
          />
        </div>

        <!-- Job Type & Operational Status -->
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
          <div>
            <label class="block font-semibold text-slate-300 mb-1">
              Jenis Tindakan <span class="text-rose-400">*</span>
            </label>
            <select v-model="form.job_type" class="input-field" required>
              <option value="rutin">Pemeliharaan Rutin</option>
              <option value="perbaikan">Perbaikan Kerusakan</option>
              <option value="kalibrasi">Uji Kalibrasi Alat</option>
            </select>
          </div>

          <div>
            <label class="block font-semibold text-slate-300 mb-1">
              Kondisi Pasca-Cek <span class="text-rose-400">*</span>
            </label>
            <select v-model="form.operational_status" class="input-field" required>
              <option value="bisa_digunakan">Bisa Digunakan (Laik)</option>
              <option value="tidak_bisa_digunakan">Tidak Bisa Digunakan (Rusak)</option>
            </select>
          </div>
        </div>

        <!-- Standard SOP Checklist -->
        <div>
          <label class="block font-semibold text-slate-300 mb-1.5">
            Ceklis Pemeriksaan Fisik & Fungsi (SOP)
          </label>
          <div class="space-y-1.5 p-3 rounded-xl bg-slate-900/60 border border-slate-800">
            <label
              v-for="(check, idx) in standardChecklist"
              :key="idx"
              class="flex items-center gap-2.5 text-slate-300 hover:text-white cursor-pointer select-none"
            >
              <input
                type="checkbox"
                v-model="check.checked"
                class="rounded bg-slate-800 border-slate-700 text-indigo-600 focus:ring-0 focus:outline-none w-4 h-4 cursor-pointer"
              />
              <span class="text-xs">{{ check.label }}</span>
            </label>
          </div>
        </div>

        <!-- Problem / Fault Finding -->
        <div>
          <label class="block font-semibold text-slate-300 mb-1">
            Temuan Masalah / Gejala Kerusakan
          </label>
          <textarea
            v-model="form.problem"
            rows="2"
            class="input-field"
            placeholder="Contoh: Kabel power longgar, tekanan vakum menurun, indikator alarm menyala."
          ></textarea>
        </div>

        <!-- Resolution / Actions Performed -->
        <div>
          <label class="block font-semibold text-slate-300 mb-1">
            Tindakan Perbaikan / Catatan Pekerjaan
          </label>
          <textarea
            v-model="form.resolution"
            rows="2"
            class="input-field"
            placeholder="Contoh: Pembersihan filter udara, penggantian seal gasket, kalibrasi ulang sensor suhu."
          ></textarea>
        </div>

        <!-- Submit Button -->
        <div class="pt-2">
          <button
            type="submit"
            :disabled="submitting"
            class="w-full py-3 px-4 rounded-xl bg-indigo-600 hover:bg-indigo-500 active:scale-98 text-white font-bold text-xs uppercase tracking-wider transition-all flex items-center justify-center gap-2 shadow-lg cursor-pointer disabled:bg-slate-800 disabled:text-slate-500 disabled:cursor-not-allowed"
          >
            <Send class="w-4 h-4" :class="{ 'animate-spin': submitting }" />
            <span>{{ submitting ? 'Mengirim ke WebHost...' : 'Kirim Laporan Maintenance' }}</span>
          </button>
        </div>
      </form>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue';
import { useRoute } from 'vue-router';
import axios from 'axios';
import {
  Wrench,
  CheckCircle2,
  Send,
} from 'lucide-vue-next';

const route = useRoute();
const identifier = route.params.identifier || '';

const itemSnapshot = ref(null);
const submitting = ref(false);
const submittedSuccess = ref(false);
const submittedData = ref(null);

const form = reactive({
  identifier: identifier,
  performer_name: '',
  job_type: 'rutin',
  operational_status: 'bisa_digunakan',
  problem: '',
  resolution: '',
  notes: '',
});

const standardChecklist = reactive([
  { label: 'Pemeriksaan fisik casing & kebersihan unit', checked: true },
  { label: 'Pemeriksaan kabel daya, adaptor & konektor listrik', checked: true },
  { label: 'Uji fungsi operasional saklar & tombol panel', checked: true },
  { label: 'Pemeriksaan display/layar monitor indikator', checked: true },
  { label: 'Pembersihan filter & komponen bergerak', checked: false },
]);

const fetchItemDetails = async () => {
  if (!identifier) return;
  try {
    const res = await axios.get(`/api/v1/public/item/${encodeURIComponent(identifier)}`);
    if (res.data.success) {
      itemSnapshot.value = res.data.data;
      if (itemSnapshot.value.title) {
        form.problem = '';
      }
    }
  } catch (err) {
    // Snapshot might not exist yet, allow ad-hoc submission
  }
};

const submitReport = async () => {
  submitting.value = true;
  try {
    const payload = {
      identifier: form.identifier || identifier,
      performer_name: form.performer_name,
      job_type: form.job_type,
      operational_status: form.operational_status,
      problem: form.problem || null,
      resolution: form.resolution || null,
      notes: form.notes || null,
      checklist: standardChecklist.filter(c => c.checked).map(c => c.label),
    };

    const res = await axios.post('/api/v1/public/maintenance/submit', payload);
    if (res.data.success) {
      submittedSuccess.value = true;
      submittedData.value = res.data.data;
    }
  } catch (err) {
    alert('Gagal mengirim laporan: ' + (err.response?.data?.message || err.message));
  } finally {
    submitting.value = false;
  }
};

const resetForm = () => {
  submittedSuccess.value = false;
  submittedData.value = null;
  form.performer_name = '';
  form.problem = '';
  form.resolution = '';
};

const formatSubmittedTime = (iso) => {
  if (!iso) return '-';
  try {
    const d = new Date(iso);
    return d.toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit', second: '2-digit' }) + ', ' +
      d.toLocaleDateString('id-ID', { day: 'numeric', month: 'short', year: 'numeric' });
  } catch (e) {
    return iso;
  }
};

onMounted(() => {
  fetchItemDetails();
});
</script>
