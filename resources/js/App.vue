<template>
  <div class="min-h-screen bg-[#080c14] text-slate-100 flex flex-col selection:bg-indigo-500 selection:text-white font-sans">
    <!-- Top Navigation Command Bar -->
    <header v-if="!$route.meta.isPublic" class="sticky top-0 z-40 bg-[#080c14]/90 backdrop-blur-md border-b border-slate-800/80">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex items-center justify-between h-16">
          <!-- Brand / Logo Lockup -->
          <router-link to="/" class="flex items-center gap-3 group">
            <div class="w-9 h-9 rounded-xl bg-gradient-to-br from-indigo-500 via-indigo-600 to-indigo-800 flex items-center justify-center shadow-sm ring-1 ring-white/15 group-hover:ring-indigo-400/40 transition-all duration-200">
              <Radio class="w-4 h-4 text-white animate-pulse" />
            </div>
            <div class="flex flex-col">
              <div class="flex items-center gap-2">
                <span class="font-extrabold text-base tracking-tight text-white group-hover:text-indigo-200 transition-colors">
                  ID-Grow <span class="text-indigo-400 font-bold">WebHost</span>
                </span>
                <span class="text-[10px] font-mono font-bold uppercase tracking-wider px-1.5 py-0.5 rounded bg-indigo-500/10 text-indigo-300 border border-indigo-500/20">
                  RELAY
                </span>
              </div>
              <span class="text-[11px] text-slate-400 font-mono hidden sm:block">Reverb :8090 • Store & Forward</span>
            </div>
          </router-link>

          <!-- Desktop Navigation Tabs (Segmented Control style) -->
          <nav class="hidden md:flex items-center p-1 rounded-xl bg-slate-900/90 border border-slate-800/80">
            <router-link
              to="/"
              class="px-3.5 py-1.5 rounded-lg text-xs font-semibold transition-all duration-150 flex items-center gap-2"
              :class="$route.name === 'dashboard' ? 'bg-indigo-600 text-white shadow-sm' : 'text-slate-400 hover:text-slate-200 hover:bg-slate-800/50'"
            >
              <Activity class="w-3.5 h-3.5" />
              <span>Overview</span>
            </router-link>

            <router-link
              to="/projects"
              class="px-3.5 py-1.5 rounded-lg text-xs font-semibold transition-all duration-150 flex items-center gap-2"
              :class="$route.name === 'projects' ? 'bg-indigo-600 text-white shadow-sm' : 'text-slate-400 hover:text-slate-200 hover:bg-slate-800/50'"
            >
              <FolderGit2 class="w-3.5 h-3.5" />
              <span>Projects</span>
            </router-link>

            <router-link
              to="/clients"
              class="px-3.5 py-1.5 rounded-lg text-xs font-semibold transition-all duration-150 flex items-center gap-2"
              :class="$route.name === 'clients' ? 'bg-indigo-600 text-white shadow-sm' : 'text-slate-400 hover:text-slate-200 hover:bg-slate-800/50'"
            >
              <Cpu class="w-3.5 h-3.5" />
              <span>Clients</span>
            </router-link>

            <router-link
              to="/queues"
              class="px-3.5 py-1.5 rounded-lg text-xs font-semibold transition-all duration-150 flex items-center gap-2"
              :class="$route.name === 'queues' ? 'bg-indigo-600 text-white shadow-sm' : 'text-slate-400 hover:text-slate-200 hover:bg-slate-800/50'"
            >
              <Layers class="w-3.5 h-3.5" />
              <span>Queues</span>
            </router-link>

            <router-link
              to="/integration"
              class="px-3.5 py-1.5 rounded-lg text-xs font-semibold transition-all duration-150 flex items-center gap-2"
              :class="$route.name === 'integration' ? 'bg-indigo-600 text-white shadow-sm' : 'text-slate-400 hover:text-slate-200 hover:bg-slate-800/50'"
            >
              <Code class="w-3.5 h-3.5" />
              <span>Integration</span>
            </router-link>
          </nav>

          <!-- Right Status & Mobile Toggle -->
          <div class="flex items-center gap-3">
            <!-- WebSocket Heartbeat Pill -->
            <div
              class="flex items-center gap-2 px-3 py-1.5 rounded-xl border text-xs font-medium transition-all"
              :class="isWsConnected 
                ? 'bg-emerald-500/5 border-emerald-500/20 text-emerald-300' 
                : 'bg-amber-500/5 border-amber-500/20 text-amber-300'"
              :title="isWsConnected ? 'WebSocket Reverb aktif terhubung' : 'Sedang mencoba terhubung ke Reverb :8090'"
            >
              <span class="relative flex h-2 w-2">
                <span
                  v-if="isWsConnected"
                  class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"
                ></span>
                <span
                  class="relative inline-flex rounded-full h-2 w-2"
                  :class="isWsConnected ? 'bg-emerald-400' : 'bg-amber-400'"
                ></span>
              </span>
              <span class="hidden sm:inline font-mono">
                {{ isWsConnected ? 'Reverb Live' : 'Reconnecting...' }}
              </span>
            </div>

            <!-- Mobile Menu Hamburger Button -->
            <button
              @click="mobileMenuOpen = !mobileMenuOpen"
              class="md:hidden p-2 rounded-xl bg-slate-900 border border-slate-800 text-slate-300 hover:text-white cursor-pointer"
              aria-label="Toggle Menu"
            >
              <Menu v-if="!mobileMenuOpen" class="w-5 h-5" />
              <X v-else class="w-5 h-5" />
            </button>
          </div>
        </div>
      </div>

      <!-- Mobile Dropdown Navigation -->
      <transition name="expand">
        <div v-if="mobileMenuOpen" class="md:hidden border-t border-slate-800 bg-[#0c1220] px-4 py-3 space-y-1">
          <router-link
            @click="mobileMenuOpen = false"
            to="/"
            class="flex items-center gap-2.5 px-3 py-2 rounded-lg text-sm font-medium"
            :class="$route.name === 'dashboard' ? 'bg-indigo-600 text-white' : 'text-slate-300 hover:bg-slate-800/60'"
          >
            <Activity class="w-4 h-4" />
            <span>Overview</span>
          </router-link>

          <router-link
            @click="mobileMenuOpen = false"
            to="/projects"
            class="flex items-center gap-2.5 px-3 py-2 rounded-lg text-sm font-medium"
            :class="$route.name === 'projects' ? 'bg-indigo-600 text-white' : 'text-slate-300 hover:bg-slate-800/60'"
          >
            <FolderGit2 class="w-4 h-4" />
            <span>Projects</span>
          </router-link>

          <router-link
            @click="mobileMenuOpen = false"
            to="/clients"
            class="flex items-center gap-2.5 px-3 py-2 rounded-lg text-sm font-medium"
            :class="$route.name === 'clients' ? 'bg-indigo-600 text-white' : 'text-slate-300 hover:bg-slate-800/60'"
          >
            <Cpu class="w-4 h-4" />
            <span>Clients</span>
          </router-link>

          <router-link
            @click="mobileMenuOpen = false"
            to="/queues"
            class="flex items-center gap-2.5 px-3 py-2 rounded-lg text-sm font-medium"
            :class="$route.name === 'queues' ? 'bg-indigo-600 text-white' : 'text-slate-300 hover:bg-slate-800/60'"
          >
            <Layers class="w-4 h-4" />
            <span>Queues</span>
          </router-link>

          <router-link
            @click="mobileMenuOpen = false"
            to="/integration"
            class="flex items-center gap-2.5 px-3 py-2 rounded-lg text-sm font-medium"
            :class="$route.name === 'integration' ? 'bg-indigo-600 text-white' : 'text-slate-300 hover:bg-slate-800/60'"
          >
            <Code class="w-4 h-4" />
            <span>Integration</span>
          </router-link>
        </div>
      </transition>
    </header>

    <!-- Main Content Workspace -->
    <main class="flex-1 max-w-7xl w-full mx-auto px-4 sm:px-6 lg:px-8 py-7">
      <router-view v-slot="{ Component }">
        <transition name="fade" mode="out-in">
          <component :is="Component" />
        </transition>
      </router-view>
    </main>

    <!-- Minimalist Anti-Slop Footer -->
    <footer v-if="!$route.meta.isPublic" class="border-t border-slate-800/80 py-4 text-xs text-slate-500 bg-[#080c14]">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 flex flex-col sm:flex-row items-center justify-between gap-3">
        <div class="flex items-center gap-2 font-mono text-[11px]">
          <span class="w-1.5 h-1.5 rounded-full bg-indigo-500"></span>
          <span class="text-slate-400 font-semibold">ID-Grow WebHost</span>
          <span>•</span>
          <span>Laravel 11 Reverb Relay</span>
          <span>•</span>
          <span class="text-indigo-400">Multi-Tenant Isolated</span>
        </div>
        <div class="flex items-center gap-4 text-slate-400">
          <span>Serving: Prima (POS/Input) & Printer Service</span>
        </div>
      </div>
    </footer>

    <!-- Global Anti-Slop Toast Container -->
    <div class="fixed bottom-5 right-5 z-50 flex flex-col gap-2 pointer-events-none max-w-sm w-full">
      <transition-group name="toast">
        <div
          v-for="toast in toasts"
          :key="toast.id"
          class="pointer-events-auto p-3.5 rounded-xl border shadow-xl flex items-start gap-3 backdrop-blur-md"
          :class="{
            'bg-[#0f172a]/95 border-emerald-500/30 text-emerald-200': toast.type === 'success',
            'bg-[#0f172a]/95 border-rose-500/30 text-rose-200': toast.type === 'error',
            'bg-[#0f172a]/95 border-amber-500/30 text-amber-200': toast.type === 'warning',
            'bg-[#0f172a]/95 border-indigo-500/30 text-indigo-200': toast.type === 'info',
          }"
        >
          <div class="mt-0.5 flex-shrink-0">
            <CheckCircle2 v-if="toast.type === 'success'" class="w-4 h-4 text-emerald-400" />
            <AlertCircle v-else-if="toast.type === 'error'" class="w-4 h-4 text-rose-400" />
            <AlertTriangle v-else-if="toast.type === 'warning'" class="w-4 h-4 text-amber-400" />
            <Info v-else class="w-4 h-4 text-indigo-400" />
          </div>
          <div class="flex-1 text-xs font-medium leading-snug">
            {{ toast.message }}
          </div>
          <button
            @click="removeToast(toast.id)"
            class="text-slate-400 hover:text-white transition-colors cursor-pointer p-0.5 -mr-1"
          >
            <X class="w-3.5 h-3.5" />
          </button>
        </div>
      </transition-group>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { 
  Activity, FolderGit2, Cpu, Layers, Code, Radio, Menu, X, 
  CheckCircle2, AlertCircle, AlertTriangle, Info 
} from 'lucide-vue-next';
import echo from './echo';
import { useToast } from './composables/useToast';

const { toasts, removeToast } = useToast();
const isWsConnected = ref(false);
const mobileMenuOpen = ref(false);

onMounted(() => {
  try {
    if (echo && echo.connector && echo.connector.pusher) {
      echo.connector.pusher.connection.bind('connected', () => {
        isWsConnected.value = true;
      });
      echo.connector.pusher.connection.bind('disconnected', () => {
        isWsConnected.value = false;
      });
      echo.connector.pusher.connection.bind('unavailable', () => {
        isWsConnected.value = false;
      });
    }
  } catch (e) {
    console.warn('Echo WS connection check notice:', e);
  }
});
</script>

<style scoped>
.fade-enter-active,
.fade-leave-active {
  transition: opacity 0.15s ease, transform 0.15s ease;
}

.fade-enter-from,
.fade-leave-to {
  opacity: 0;
  transform: translateY(4px);
}

.toast-enter-active,
.toast-leave-active {
  transition: all 0.25s cubic-bezier(0.16, 1, 0.3, 1);
}

.toast-enter-from {
  opacity: 0;
  transform: translateY(12px) scale(0.95);
}

.toast-leave-to {
  opacity: 0;
  transform: translateY(-8px) scale(0.95);
}

.expand-enter-active,
.expand-leave-active {
  transition: all 0.2s ease;
}

.expand-enter-from,
.expand-leave-to {
  opacity: 0;
  transform: translateY(-6px);
}
</style>
