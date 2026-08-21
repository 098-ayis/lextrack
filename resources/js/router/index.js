import { createRouter, createWebHistory } from 'vue-router'

import HomePage from '../pages/public/HomePage.vue'
import AboutPage from '../pages/public/AboutPage.vue'
import TrackPage from '../pages/public/TrackPage.vue'
import FormsPage from '../pages/public/FormsPage.vue'
import LoginPage from '../pages/public/LoginPage.vue'

import PublicLayout from '../layouts/PublicLayout.vue'
import LoginLayout from '../layouts/LoginLayout.vue'



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

]

const router = createRouter({
    history: createWebHistory(),
    routes,
    scrollBehavior() {
        return { top: 0 } 
    }
})

export default router



