import { createRouter, createWebHistory } from 'vue-router';
import Dashboard from '../components/Dashboard.vue';
import AdminDashboard from '../components/AdminDashboard.vue';
import CollectorDashboard from '../components/CollectorDashboard.vue';
import DonorDashboard from '../components/DonorDashboard.vue';
import NotFound from '../components/NotFound.vue';
import Sessions from '../components/Sessions.vue';
import CreateEvent from '../components/CreateEvent.vue';
import ManageEvents from '../components/ManageEvents.vue';
import RecordDonation from '../components/RecordDonation.vue';
import EventDashboard from '../components/EventDashboard.vue';
import UserManagement from '../components/UserManagement.vue';

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
    },
    {
        path: '/collector',
        name: 'CollectorDashboard',
        component: CollectorDashboard,
    },
    {
        path: '/donor',
        name: 'DonorDashboard',
        component: DonorDashboard,
    },
    {
        path: '/sessions',
        name: 'Sessions',
        component: Sessions,
    },
    {
        path: '/create-event',
        name: 'CreateEvent',
        component: CreateEvent,
    },
    {
        path: '/manage-events',
        name: 'ManageEvents',
        component: ManageEvents,
    },
    {
        path: '/record-donation',
        name: 'RecordDonation',
        component: RecordDonation,
    },
    {
        path: '/events/:eventId/dashboard',
        name: 'EventDashboard',
        component: EventDashboard,
        props: true,
    },
    {
        path: '/user-management',
        name: 'UserManagement',
        component: UserManagement,
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
