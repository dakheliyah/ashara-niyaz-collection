import { createRouter, createWebHistory } from 'vue-router';
import Dashboard from '../components/Dashboard.vue';
import AdminDashboard from '../components/AdminDashboard.vue';
import CollectorDashboard from '../components/CollectorDashboard.vue';
import DonorDashboard from '../components/DonorDashboard.vue';
import NotFound from '../components/NotFound.vue';
import CreateEvent from '../components/CreateEvent.vue';
import ManageEvents from '../components/ManageEvents.vue';
import RecordDonation from '../components/RecordDonation.vue';
import EventDashboard from '../components/EventDashboard.vue';
import UserManagement from '../components/UserManagement.vue';
// Using window.axios for API calls with token authentication

// Helper function to check user role
async function getUserRole() {
    try {
        const response = await window.axios.get('/api/me');
        return response.data.role;
    } catch (error) {
        console.error('Error fetching user role:', error);
        return null;
    }
}

// Route guard for role-based access
async function requireRole(allowedRoles) {
    const userRole = await getUserRole();
    if (!userRole || !allowedRoles.includes(userRole)) {
        return { name: 'Dashboard' }; // Redirect to main dashboard
    }
    return true;
}

const routes = [
    {
        path: '/',
        name: 'Dashboard',
        component: Dashboard,
    },
    {
        path: '/admin',
        name: 'AdminDashboard',
        component: AdminDashboard,
        beforeEnter: async () => await requireRole(['admin']),
    },
    {
        path: '/collector',
        name: 'CollectorDashboard',
        component: CollectorDashboard,
        beforeEnter: async () => await requireRole(['collector']),
    },
    {
        path: '/donor',
        name: 'DonorDashboard',
        component: DonorDashboard,
        beforeEnter: async () => await requireRole(['donor']),
    },
    {
        path: '/create-event',
        name: 'CreateEvent',
        component: CreateEvent,
        beforeEnter: async () => await requireRole(['admin', 'collector']),
    },
    {
        path: '/manage-events',
        name: 'ManageEvents',
        component: ManageEvents,
        beforeEnter: async () => await requireRole(['admin', 'collector']),
    },
    {
        path: '/record-donation',
        name: 'RecordDonation',
        component: RecordDonation,
        beforeEnter: async () => await requireRole(['collector']),
    },
    {
        path: '/events/:eventId/dashboard',
        name: 'EventDashboard',
        component: EventDashboard,
        props: true,
        beforeEnter: async () => await requireRole(['admin', 'collector', 'donor']),
    },
    {
        path: '/user-management',
        name: 'UserManagement',
        component: UserManagement,
        beforeEnter: async () => await requireRole(['admin']),
    },
    {
        path: '/:pathMatch(.*)*',
        name: 'NotFound',
        component: NotFound,
    },
];

const router = createRouter({
    history: createWebHistory(),
    routes,
});

export default router;
