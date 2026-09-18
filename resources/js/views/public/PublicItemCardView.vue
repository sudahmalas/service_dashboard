<template>
  <div class="min-h-[85vh] flex flex-col justify-center max-w-md mx-auto py-4">
    <!-- Brand Header -->
    <div class="text-center mb-5">
      <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-slate-900 border border-slate-800 text-[11px] font-mono text-slate-400 mb-2">
        <ShieldCheck class="w-3.5 h-3.5 text-indigo-400" />
        <span>Verifikasi Resmi Item • {{ item?.project?.name || 'Sistem Rumah Sakit' }}</span>
      </div>
      <h1 class="text-lg font-bold text-white tracking-tight">Portal Publik Verifikasi QR</h1>
    </div>

    <!-- Loading State -->
    <div v-if="loading" class="card-panel p-8 rounded-2xl text-center space-y-3">
      <RefreshCw class="w-7 h-7 animate-spin mx-auto text-indigo-400" />
      <p class="text-xs text-slate-300 font-medium">Memuat data item dari WebHost...</p>
    </div>

    <!-- Error / Not Found State -->
    <div v-else-if="error" class="card-panel p-6 rounded-2xl text-center space-y-4 border-rose-900/40 bg-rose-950/10">
      <div class="w-12 h-12 rounded-2xl bg-rose-500/10 border border-rose-500/20 flex items-center justify-center mx-auto text-rose-400">
        <AlertCircle class="w-6 h-6" />
      </div>
      <div>
        <h3 class="text-sm font-bold text-white">Item Tidak Ditemukan</h3>
        <p class="text-xs text-slate-400 mt-1">
          Kode <code class="text-rose-300 font-mono font-bold">{{ identifier }}</code> belum terdaftar dalam database snapshot WebHost.
        </p>
      </div>
      <div class="pt-2">
        <router-link
          :to="`/m/${identifier}`"
          class="btn-primary w-full justify-center text-xs"
        >
          <Wrench class="w-3.5 h-3.5" />
          <span>Isi Laporan Maintenance Mandiri</span>
        </router-link>
      </div>
    </div>

    <!-- Verified Item Card -->
    <div v-else-if="item" class="card-panel rounded-2xl overflow-hidden shadow-2xl border-slate-700/80">
      <!-- Status Top Banner -->
      <div
        class="px-5 py-3.5 flex items-center justify-between border-b border-slate-800"
        :class="{
          'bg-emerald-500/10 text-emerald-300': item.status_color === 'emerald',
          'bg-amber-500/10 text-amber-300': item.status_color === 'amber',
          'bg-rose-500/10 text-rose-300': item.status_color === 'rose',
          'bg-indigo-500/10 text-indigo-300': item.status_color === 'indigo' || item.status_color === 'cyan',
        }"
      >
        <div class="flex items-center gap-2">
          <span class="relative flex h-2.5 w-2.5">
            <span class="animate-ping absolute inline-flex h-full w-full rounded-full opacity-75"
              :class="{
                'bg-emerald-400': item.status_color === 'emerald',
                'bg-amber-400': item.status_color === 'amber',
                'bg-rose-400': item.status_color === 'rose',
                'bg-indigo-400': item.status_color === 'indigo' || item.status_color === 'cyan',
              }"
            ></span>
            <span class="relative inline-flex rounded-full h-2.5 w-2.5"
              :class="{
                'bg-emerald-400': item.status_color === 'emerald',
                'bg-amber-400': item.status_color === 'amber',
                'bg-rose-400': item.status_color === 'rose',
                'bg-indigo-400': item.status_color === 'indigo' || item.status_color === 'cyan',
              }"
            ></span>
          </span>
          <span class="font-bold text-xs uppercase tracking-wider font-mono">
            {{ item.status_label }}
          </span>
        </div>

        <span class="text-[10px] font-mono uppercase px-2 py-0.5 rounded bg-slate-900/80 border border-slate-800 text-slate-300">
          {{ item.category === 'cssd' ? 'CSSD STERIL' : 'INVENTARIS MEDIS' }}
        </span>
      </div>

      <!-- Item Main Body -->
      <div class="p-5 space-y-4">
        <div>
          <span class="text-[10px] font-mono text-indigo-400 uppercase tracking-widest font-semibold block">
            {{ item.subtitle || item.code }}
          </span>
          <h2 class="text-base sm:text-lg font-bold text-white tracking-tight mt-0.5">
            {{ item.title }}
          </h2>
          <div class="text-xs text-slate-400 flex items-center gap-1.5 mt-1">
            <MapPin class="w-3.5 h-3.5 text-slate-500 shrink-0" />
            <span>{{ item.location || 'Lokasi belum diset' }}</span>
          </div>
        </div>

        <!-- Metadata Section (CSSD or Asset specific) -->
        <div class="p-3.5 rounded-xl bg-slate-900/90 border border-slate-800 space-y-2.5 text-xs">
          <!-- CSSD Metadata -->
          <template v-if="item.category === 'cssd'">
            <div class="flex items-center justify-between">
              <span class="text-slate-400">Tgl Sterilisasi:</span>
              <span class="font-mono text-slate-200">{{ formatMetaDate(item.meta_data?.sterilized_at) }}</span>
            </div>
            <div class="flex items-center justify-between">
              <span class="text-slate-400">Berlaku Sampai:</span>
              <span class="font-mono font-bold" :class="item.status_color === 'rose' ? 'text-rose-400' : 'text-emerald-400'">
                {{ formatMetaDate(item.meta_data?.expired_at) }}
              </span>
            </div>
            <div v-if="item.meta_data?.operator_name" class="flex items-center justify-between">
              <span class="text-slate-400">Petugas CSSD:</span>
              <span class="text-slate-200">{{ item.meta_data.operator_name }}</span>
            </div>
            <div v-if="item.meta_data?.method" class="flex items-center justify-between">
              <span class="text-slate-400">Metode Steril:</span>
              <span class="font-mono text-indigo-300">{{ item.meta_data.method }}</span>
            </div>
          </template>

          <!-- Asset / Serial Number Metadata -->
          <template v-else>
            <div class="flex items-center justify-between">
              <span class="text-slate-400">Nomor Seri (SN):</span>
              <span class="font-mono text-slate-200">{{ item.meta_data?.serial_number || item.code || '-' }}</span>
            </div>
            <div v-if="item.meta_data?.brand || item.meta_data?.model" class="flex items-center justify-between">
              <span class="text-slate-400">Merk / Model:</span>
              <span class="text-slate-200">{{ [item.meta_data?.brand, item.meta_data?.model].filter(Boolean).join(' - ') }}</span>
            </div>
            <div class="flex items-center justify-between">
              <span class="text-slate-400">Kondisi Fisik:</span>
              <span class="text-emerald-400 font-semibold">{{ item.meta_data?.operational_status || 'Baik & Siap Pakai' }}</span>
            </div>
            <div v-if="item.meta_data?.last_maintenance_at" class="flex items-center justify-between">
              <span class="text-slate-400">Terakhir Servis:</span>
              <span class="font-mono text-slate-300">{{ formatMetaDate(item.meta_data.last_maintenance_at) }}</span>
            </div>
          </template>
        </div>

        <!-- Action Button: Lapor Maintenance -->
        <div class="pt-2">
          <router-link
            :to="`/m/${item.identifier}`"
            class="w-full py-3 px-4 rounded-xl bg-indigo-600 hover:bg-indigo-500 active:scale-98 text-white font-bold text-xs uppercase tracking-wider transition-all flex items-center justify-center gap-2 shadow-lg cursor-pointer"
          >
            <Wrench class="w-4 h-4" />
            <span>Laporkan Maintenance / Servis</span>
          </router-link>
        </div>
      </div>

      <!-- Footer Info -->
      <div class="px-5 py-3 bg-slate-900/50 border-t border-slate-800/80 text-[11px] text-slate-500 flex items-center justify-between font-mono">
        <span>Unit: {{ item.project?.name || 'Rumah Sakit' }}</span>
        <span>ID: {{ item.identifier }}</span>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { useRoute } from 'vue-router';
import axios from 'axios';
import {
  ShieldCheck,
  RefreshCw,
  AlertCircle,
  MapPin,
  Wrench,
} from 'lucide-vue-next';

const route = useRoute();
const identifier = route.params.identifier;

const loading = ref(true);
const error = ref(null);
const item = ref(null);

const formatMetaDate = (str) => {
  if (!str) return '-';
  try {
    const d = new Date(str);
    return d.toLocaleDateString('id-ID', {
      day: 'numeric',
      month: 'short',
      year: 'numeric',
      hour: '2-digit',
      minute: '2-digit',
    });
  } catch (e) {
    return str;
  }
};

const fetchItem = async () => {
  loading.value = true;
  error.value = null;
  try {
    const res = await axios.get(`/api/v1/public/item/${encodeURIComponent(identifier)}`);
    if (res.data.success) {
      item.value = res.data.data;
    }
  } catch (err) {
    error.value = err.response?.data?.message || 'Item tidak ditemukan.';
  } finally {
    loading.value = false;
  }
};

onMounted(() => {
  fetchItem();
});
</script>
