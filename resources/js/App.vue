<template>
  <div class="min-h-screen bg-slate-950 text-slate-100 flex flex-col selection:bg-indigo-500 selection:text-white">
    <!-- Top Navigation Header -->
    <header class="sticky top-0 z-40 glass-panel border-b border-slate-800/80 shadow-lg">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex items-center justify-between h-16">
          <!-- Brand / Logo -->
          <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-xl bg-gradient-to-tr from-indigo-600 via-indigo-500 to-cyan-400 flex items-center justify-center shadow-lg shadow-indigo-500/20 ring-1 ring-white/20">
              <Radio class="w-5 h-5 text-white animate-pulse" />
            </div>
            <div>
              <div class="flex items-center gap-2">
                <span class="font-extrabold text-lg tracking-tight bg-clip-text text-transparent bg-gradient-to-r from-white via-slate-100 to-slate-400">
                  ID-Grow <span class="text-indigo-400 font-semibold">WebHost</span>
                </span>
                <span class="text-[10px] font-bold uppercase tracking-wider px-2 py-0.5 rounded-full bg-indigo-500/10 text-indigo-300 border border-indigo-500/20">
                  Central Relay
                </span>
              </div>
              <p class="text-xs text-slate-400 hidden sm:block">Laravel 11 • Reverb WS :8090 • Store & Forward</p>
            </div>
          </div>

          <!-- Navigation Links -->
          <nav class="flex items-center gap-1 sm:gap-2">
            <router-link
              to="/"
              class="px-3.5 py-2 rounded-lg text-sm font-medium transition-all duration-200 flex items-center gap-2"
              :class="$route.name === 'dashboard' ? 'bg-indigo-600/20 text-indigo-300 border border-indigo-500/30' : 'text-slate-400 hover:text-slate-200 hover:bg-slate-900/60'"
            >
              <Activity class="w-4 h-4" />
              <span>Overview</span>
            </router-link>

            <router-link
              to="/projects"
              class="px-3.5 py-2 rounded-lg text-sm font-medium transition-all duration-200 flex items-center gap-2"
              :class="$route.name === 'projects' ? 'bg-indigo-600/20 text-indigo-300 border border-indigo-500/30' : 'text-slate-400 hover:text-slate-200 hover:bg-slate-900/60'"
            >
              <FolderGit2 class="w-4 h-4" />
              <span>Projects</span>
            </router-link>

            <router-link
              to="/clients"
              class="px-3.5 py-2 rounded-lg text-sm font-medium transition-all duration-200 flex items-center gap-2"
              :class="$route.name === 'clients' ? 'bg-indigo-600/20 text-indigo-300 border border-indigo-500/30' : 'text-slate-400 hover:text-slate-200 hover:bg-slate-900/60'"
            >
              <Cpu class="w-4 h-4" />
              <span>Clients</span>
            </router-link>

            <router-link
              to="/queues"
              class="px-3.5 py-2 rounded-lg text-sm font-medium transition-all duration-200 flex items-center gap-2"
              :class="$route.name === 'queues' ? 'bg-indigo-600/20 text-indigo-300 border border-indigo-500/30' : 'text-slate-400 hover:text-slate-200 hover:bg-slate-900/60'"
            >
              <Layers class="w-4 h-4" />
              <span>Queues</span>
            </router-link>

            <router-link
              to="/integration"
              class="px-3.5 py-2 rounded-lg text-sm font-medium transition-all duration-200 flex items-center gap-2"
              :class="$route.name === 'integration' ? 'bg-indigo-600/20 text-indigo-300 border border-indigo-500/30' : 'text-slate-400 hover:text-slate-200 hover:bg-slate-900/60'"
            >
              <Code class="w-4 h-4" />
              <span>Integration</span>
            </router-link>
          </nav>

          <!-- Live WebSocket Status Badge -->
          <div class="hidden lg:flex items-center gap-2 px-3 py-1.5 rounded-lg border border-slate-800 bg-slate-900/50">
            <span class="relative flex h-2.5 w-2.5">
              <span class="animate-ping absolute inline-flex h-full w-full rounded-full opacity-75" :class="isWsConnected ? 'bg-emerald-400' : 'bg-amber-400'"></span>
              <span class="relative inline-flex rounded-full h-2.5 w-2.5" :class="isWsConnected ? 'bg-emerald-500' : 'bg-amber-500'"></span>
            </span>
            <span class="text-xs font-medium text-slate-300">
              {{ isWsConnected ? 'Reverb WS Live' : 'Connecting WS...' }}
            </span>
          </div>
        </div>
      </div>
    </header>

    <!-- Main Content Body -->
    <main class="flex-1 max-w-7xl w-full mx-auto px-4 sm:px-6 lg:px-8 py-8">
      <router-view v-slot="{ Component }">
        <transition name="fade" mode="out-in">
          <component :is="Component" />
        </transition>
      </router-view>
    </main>

    <!-- Footer -->
    <footer class="border-t border-slate-800/80 py-4 text-center text-xs text-slate-400 glass-panel">
      <div class="max-w-7xl mx-auto px-4 flex flex-col sm:flex-row items-center justify-between gap-2">
        <div class="flex items-center gap-2">
          <span>Central Real-Time Host Engine</span>
          <span class="text-slate-700">•</span>
          <span class="text-indigo-400">ID-Grow Ecosystem</span>
        </div>
        <div class="flex items-center gap-4 text-slate-400">
          <span>Serving: Prima (POS/Input) & Printer Service</span>
        </div>
      </div>
    </footer>
  </div>
</template>

<script setup>
import { ref, onMounted, onUnmounted } from 'vue';
import { Activity, FolderGit2, Cpu, Layers, Code, Radio } from 'lucide-vue-next';
import echo from './echo';

const isWsConnected = ref(false);

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

onUnmounted(() => {
  // cleanup
});
</script>

<style scoped>
.fade-enter-active,
.fade-leave-active {
  transition: opacity 0.15s ease;
}

.fade-enter-from,
.fade-leave-to {
  opacity: 0;
}
</style>
