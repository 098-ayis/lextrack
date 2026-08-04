import { createRouter, createWebHistory } from 'vue-router'

import HomePage from '../pages/public/HomePage.vue'
import AboutPage from '../pages/public/AboutPage.vue'
import TrackPage from '../pages/public/TrackPage.vue'
import FormsPage from '../pages/public/FormsPage.vue'
import LoginPage from '../pages/public/LoginPage.vue'
import PublicLayout from '../layouts/PublicLayout.vue'
import ClientLayout from '../layouts/ClientLayout.vue'
import AdminLayout from '../layouts/AdminLayout.vue'

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
            { path: 'login', component: LoginPage },
        ]
    },

    // CLIENT
    /*{
        path: '/client',
        component: ClientLayout,
        children: [
            { path: '', component: ClientDashboard },
            { path: 'submit', component: SubmitDocument },
            { path: 'documents', component: MyDocuments },
            { path: 'track', component: TrackRequest },
            { path: 'profile', component: Profile },
        ]
    },

    // ADMIN
    {
        path: '/admin',
        component: AdminLayout,
        children: [
            { path: '', component: AdminDashboard },
            { path: 'documents', component: Documents },
            { path: 'clients', component: Clients },
            { path: 'users', component: Users },
            { path: 'reports', component: Reports },
            { path: 'settings', component: Settings },
        ]
    }*/

]

const router = createRouter({
    history: createWebHistory(),
    routes,
    scrollBehavior() {
        return { top: 0 } // scroll to the top on page navigation
    }
})

export default router