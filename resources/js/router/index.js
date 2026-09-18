import { createRouter, createWebHistory } from 'vue-router';
import DashboardView from '../views/DashboardView.vue';
import ProjectsView from '../views/ProjectsView.vue';
import ClientsView from '../views/ClientsView.vue';
import QueuesView from '../views/QueuesView.vue';
import IntegrationView from '../views/IntegrationView.vue';
import PublicItemCardView from '../views/public/PublicItemCardView.vue';
import PublicMaintenanceFormView from '../views/public/PublicMaintenanceFormView.vue';

const routes = [
    {
        path: '/',
        name: 'dashboard',
        component: DashboardView,
        meta: { title: 'Overview & Live Relay' },
    },
    {
        path: '/projects',
        name: 'projects',
        component: ProjectsView,
        meta: { title: 'Projects & Tenant Isolation' },
    },
    {
        path: '/clients',
        name: 'clients',
        component: ClientsView,
        meta: { title: 'Connected Clients' },
    },
    {
        path: '/queues',
        name: 'queues',
        component: QueuesView,
        meta: { title: 'Queue & Store-Forward Explorer' },
    },
    {
        path: '/integration',
        name: 'integration',
        component: IntegrationView,
        meta: { title: 'Client Integration Guide' },
    },

    // --- Public Scan Portal Routes (Mobile First, No Admin Shell) ---
    {
        path: '/s/:identifier',
        name: 'public-cssd',
        component: PublicItemCardView,
        meta: { title: 'Verifikasi Sterilisasi CSSD', isPublic: true },
    },
    {
        path: '/i/:identifier',
        name: 'public-cssd-alias',
        component: PublicItemCardView,
        meta: { title: 'Verifikasi Sterilisasi CSSD', isPublic: true },
    },
    {
        path: '/a/:identifier',
        name: 'public-asset',
        component: PublicItemCardView,
        meta: { title: 'Verifikasi Inventaris Aset', isPublic: true },
    },
    {
        path: '/sn/:identifier',
        name: 'public-serial',
        component: PublicItemCardView,
        meta: { title: 'Verifikasi Nomor Seri', isPublic: true },
    },
    {
        path: '/m/:identifier?',
        name: 'public-maintenance',
        component: PublicMaintenanceFormView,
        meta: { title: 'Formulir Laporan Maintenance', isPublic: true },
    },
    {
        path: '/maintenance/:identifier?',
        name: 'public-maintenance-alias',
        component: PublicMaintenanceFormView,
        meta: { title: 'Formulir Laporan Maintenance', isPublic: true },
    },

    {
        path: '/:pathMatch(.*)*',
        redirect: '/',
    },
];

const router = createRouter({
    history: createWebHistory(),
    routes,
    scrollBehavior() {
        return { top: 0 };
    },
});

router.afterEach((to) => {
    document.title = to.meta.title ? `${to.meta.title} | ID-Grow WebHost` : 'ID-Grow WebHost';
});

export default router;
