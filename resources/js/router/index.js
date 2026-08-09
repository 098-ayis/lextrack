import { createRouter, createWebHistory } from 'vue-router'

import HomePage from '../pages/public/HomePage.vue'
import AboutPage from '../pages/public/AboutPage.vue'
import TrackPage from '../pages/public/TrackPage.vue'
import FormsPage from '../pages/public/FormsPage.vue'
import LoginPage from '../pages/public/LoginPage.vue'

import PublicLayout from '../layouts/PublicLayout.vue'
import ClientLayout from '../layouts/ClientLayout.vue'
import AdminLayout from '../layouts/AdminLayout.vue'
import LoginLayout from '../layouts/LoginLayout.vue'

import ClientDashboard from '../pages/client/Dashboard.vue'
import AdminDashboard from '../pages/admin/Dashboard.vue'

const routes = [

    // PUBLIC
    {
        path: '/',
        component: PublicLayout,
        children: [
            { path: '', component: HomePage },
            { path: 'about', component: AboutPage },
            { path: 'forms', component: FormsPage },
            { path: 'track', component: TrackPage },
    
        ]
    },

    {
        path: '/login',
        component: LoginLayout,
        children: [
            { path: '', component: LoginPage },
        ]
    },

    // CLIENT
    {
        path: '/client',
        meta: {
            requiresAuth: true,
            role: 'Client'
        },
        component: ClientLayout,
        children: [
            { path: '', component: ClientDashboard },
           // { path: 'submit', component: SubmitDocument },
           // { path: 'documents', component: MyDocuments },
          // { path: 'track', component: TrackRequest },
          // { path: 'profile', component: Profile },
        ]
    },

    // ADMIN
    {
        path: '/admin',
        component: AdminLayout,
        meta: {
            requiresAuth: true,
            role: 'Admin'
        },
        children: [
            { path: '', component: AdminDashboard },
           // { path: 'documents', component: Documents },
           // { path: 'clients', component: Clients },
           // { path: 'users', component: Users },
           // { path: 'reports', component: Reports },
           // { path: 'settings', component: Settings },
        ]
    }

]

const router = createRouter({
    history: createWebHistory(),
    routes,
    scrollBehavior() {
        return { top: 0 } 
    }
})

export default router

import { getCurrentUser } from '../services/auth'

router.beforeEach(async (to) => {

    if (!to.meta.requiresAuth) {
        return true
    }

    const user = await getCurrentUser()

    if (!user) {
        return '/login'
    }

    if (to.meta.role === 'Admin' && user.role_name !== 'Admin') {
        return '/client'
    }

    if (to.meta.role === 'Client' && user.role_name !== 'Client') {
        return '/admin'
    }

    return true
})