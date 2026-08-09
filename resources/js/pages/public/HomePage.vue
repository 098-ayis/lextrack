<!-- HomePage.vue -->
<template>
  <div class="bu-portal">
    
    <!-- Hero Section -->
    <HeroSection />
   

    <section class="services" id="services" ref="servicesSection">
      <h2>Services We Provide</h2>

      <div class="services-grid">
        <div
          class="service-card"
          :class="{ show: servicesVisible }"
          v-for="service in services"
          :key="service.title"
        >
          <h3>{{ service.title }}</h3>
          <p>{{ service.desc }}</p>
        </div>
      </div>
    </section>

    <section class="how-to" id="track">
      <h2>How To Use Our Portal</h2>
      <div class="steps">
        <template v-for="(step, i) in steps" :key="step.title">
          <div class="step">
            <div class="step-icon" v-html="step.icon"></div>
            <h4>{{ step.title }}</h4>
            <p>{{ step.desc }}</p>
          </div>
          <div class="step-arrow" v-if="i < steps.length - 1">
            <span class="dash"></span>
            <svg width="14" height="14" viewBox="0 0 24 24" fill="currentColor"><path d="M8 5l10 7-10 7z"/></svg>
          </div>
        </template>
      </div>
    </section>

   

    <!-- Toast -->
    <transition name="fade">
      <div v-if="toast.visible" class="bu-toast show">{{ toast.message }}</div>
    </transition>
  </div>
</template>

<script setup>
import { ref, reactive, onMounted, onBeforeUnmount } from 'vue'
import { useRouter } from 'vue-router'
import HeroSection from '@/components/public/homePage/HeroSection.vue'

const router = useRouter()

/* ---------- Mobile nav ---------- */
const navOpen = ref(false)
const navRef = ref(null)
const servicesSection = ref(null)
const servicesVisible = ref(false)

function toggleNav() {
  navOpen.value = !navOpen.value
}
function closeNav() {
  navOpen.value = false
}
function handleOutsideClick(e) {
  if (navOpen.value && navRef.value && !navRef.value.contains(e.target)) {
    navOpen.value = false
  }
}
onMounted(() => {
  document.addEventListener("click", handleOutsideClick)

  const observer = new IntersectionObserver(
    ([entry]) => {
      if (entry.isIntersecting) {
        servicesVisible.value = true
        observer.disconnect() // animation only happens once
      }
    },
    {
      threshold: 0.25
    }
  )

  if (servicesSection.value) {
    observer.observe(servicesSection.value)
  }
})
onBeforeUnmount(() => document.removeEventListener('click', handleOutsideClick))

/* ---------- Toast helper ---------- */
const toast = reactive({ visible: false, message: '' })
let toastTimer = null

function showToast(message, duration = 2600) {
  toast.message = message
  toast.visible = true
  clearTimeout(toastTimer)
  toastTimer = setTimeout(() => {
    toast.visible = false
  }, duration)
}
defineExpose({ showToast })

/* ---------- Navigation helper (router with fallback) ---------- */
function goTo(path) {
  if (router) {
    router.push(path)
  } else {
    window.location.href = path
  }
}

/* ---------- Content data ---------- */
const services = [
  {
    title: 'Document Review',
    desc: 'Submit contracts, MOUs, and official letters for legal review before processing or signing.'
  },
  {
    title: 'Case Consultation',
    desc: 'Request guidance on university-related legal concerns and administrative disputes.'
  },
  {
    title: 'Compliance Advisory',
    desc: 'Get support ensuring university activities align with local laws and institutional policy.'
  },
  {
    title: 'Records & Certification',
    desc: 'Request certified copies, endorsements, and other official legal documentation.'
  }
]

const steps = [
  {
    title: '1. Complete details',
    desc: 'Fill out our online form and upload required documents.',
    icon: `<svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="#d97a3d" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
      <path d="M9 3h6a1 1 0 0 1 1 1v1H8V4a1 1 0 0 1 1-1z"/>
      <rect x="5" y="4" width="14" height="17" rx="2"/>
      <path d="M8 11h8M8 15h5"/>
    </svg>`
  },
  {
    title: '2. Submit and track status',
    desc: 'Click submit and a tracking number will be generated.',
    icon: `<svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="#d97a3d" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
      <path d="M9 12l2 2 4-4"/>
      <circle cx="12" cy="12" r="9"/>
    </svg>`
  },
  {
    title: '3. Receive updates',
    desc: 'Monitor status in real-time.',
    icon: `<svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="#d97a3d" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
      <path d="M18 8a6 6 0 0 0-12 0c0 7-3 9-3 9h18s-3-2-3-9"/>
      <path d="M13.73 21a2 2 0 0 1-3.46 0"/>
    </svg>`
  }
]
</script>

<style scoped>
.bu-portal{
  --navy: #1b3a5c;
  --navy-deep: #142c47;
  --sky: #eaf3fb;
  --sky-border: #bcd9ef;
  --orange: #e8935a;
  --orange-dark: #d97a3d;
  --text-dark: #1f2a37;
  --text-muted: #5b6b7c;
  --white: #ffffff;
  --radius: 6px;

  font-family: 'Segoe UI', Roboto, -apple-system, sans-serif;
  color: var(--text-dark);
  background: var(--white);
  line-height:1.5;
}
.bu-portal *{margin:0;padding:0;box-sizing:border-box;}
.bu-portal a{text-decoration:none;color:inherit;}
.bu-portal ul{list-style:none;}
.bu-portal img{max-width:100%;display:block;}

/* Header */
header{
  background: var(--navy);
  color: var(--white);
}
.nav-wrap{
  display:flex;
  align-items:center;
  justify-content:space-between;
  padding:14px 24px;
  max-width:1100px;
  margin:0 auto;
}
.brand{display:flex;align-items:center;gap:10px;}
.brand-logo{
  width:38px;height:38px;border-radius:50%;
  background: var(--white);
  display:flex;align-items:center;justify-content:center;
  font-weight:700;color:var(--navy);font-size:14px;
  flex-shrink:0;
}
.brand-text small{
  display:block;font-size:10px;letter-spacing:.08em;
  text-transform:uppercase;color:#fff;
  font-weight:600;
}
.brand-text small .uni{color:var(--orange);}
.brand-text strong{
  display:block;font-size:18px;font-weight:700;letter-spacing:.02em;
}
nav ul{display:flex;align-items:center;gap:28px;}
nav a{
  font-size:13px;font-weight:600;letter-spacing:.04em;
  color:var(--white);
  transition:color .15s ease;
}
nav a.active,
nav a:hover{color:var(--orange);}
.nav-divider{
  width:1px;
  height:22px;
  background:rgba(255,255,255,.28);
}
.signin{
  display:flex;align-items:center;gap:8px;
  font-size:13px;font-weight:600;
  transition: color .15s ease;
}
.signin:hover{color:var(--orange);}
.signin .dot{
  width:26px;height:26px;border-radius:50%;
  border:1.5px solid rgba(255,255,255,.7);
  display:flex;align-items:center;justify-content:center;
  flex-shrink:0;
}
.menu-toggle{display:none;background:none;border:0;color:var(--white);font-size:24px;cursor:pointer;}

/* Buttons */
.btn{
  display:inline-block;
  padding:12px 26px;
  border-radius:4px;
  font-size:13px;
  font-weight:700;
  letter-spacing:.04em;
  cursor:pointer;
  border:1.5px solid transparent;
  text-align:center;
  text-decoration:none;
  transition:transform .12s ease, box-shadow .12s ease, background .15s ease;
}
.btn:hover{transform:translateY(-1px);}
.btn:active{transform:translateY(0);}
.btn-outline{
  background: var(--sky);
  border-color: var(--sky-border);
  color: var(--navy);
}
.btn-outline:hover{background:#dcedf9;}
.btn-solid{
  background: var(--orange);
  color: var(--white);
  box-shadow: 0 2px 8px rgba(232,147,90,.35);
}
.btn-solid:hover{background:var(--orange-dark);}

/* Toast */
.bu-toast{
  position:fixed;
  left:50%;
  bottom:28px;
  transform:translateX(-50%) translateY(0);
  background: var(--navy-deep);
  color:#fff;
  font-size:13px;
  font-weight:600;
  padding:12px 20px;
  border-radius:8px;
  box-shadow:0 12px 30px rgba(0,0,0,.25);
  z-index:100;
  max-width:90vw;
  text-align:center;
}
.fade-enter-active, .fade-leave-active { transition: opacity .25s ease, transform .25s ease; }
.fade-enter-from, .fade-leave-to { opacity: 0; transform: translateX(-50%) translateY(20px); }

/* Footer */
footer{
  background:var(--navy-deep);
  color:#cdd9e4;
  padding:44px 24px 26px;
}
.footer-grid{
  max-width:1100px;
  margin:0 auto;
  display:grid;
  grid-template-columns:2fr 1fr;
  gap:40px;
}
.footer-brand{
  display:flex;
  gap:12px;
  margin-bottom:14px;
}
.footer-brand .brand-logo{background:var(--white);color:var(--navy-deep);}
.footer-brand strong{font-size:16px;color:var(--white);}
.footer-brand small{color:#8fa5bb;}
footer p.desc{
  font-size:12.5px;
  color:#a9bccd;
  max-width:400px;
  margin-bottom:20px;
}
.footer-contact{
  display:flex;
  flex-direction:column;
  gap:8px;
  font-size:12.5px;
}
.footer-contact li{display:flex;align-items:center;gap:8px;color:#a9bccd;}
footer h5{
  color:var(--white);
  font-size:13px;
  font-weight:700;
  margin-bottom:14px;
}
.footer-links li{margin-bottom:8px;}
.footer-links a{
  font-size:12.5px;
  color:#a9bccd;
  transition:color .15s ease;
}
.footer-links a:hover{color:var(--orange);}
.footer-bottom{
  max-width:1100px;
  margin:30px auto 0;
  padding-top:18px;
  border-top:1px solid rgba(255,255,255,.08);
  font-size:11.5px;
  color:#7f93a8;
  text-align:center;
}

@media (max-width:860px){
  .footer-grid{grid-template-columns:1fr;}
}
@media (max-width:720px){
  .nav-wrap{position:relative;}
  nav ul{display:none;}
  nav.nav-open ul{
    display:flex;
    flex-direction:column;
    align-items:flex-start;
    position:absolute;
    top:56px;
    right:24px;
    background: var(--navy);
    padding:16px 22px;
    border-radius:8px;
    box-shadow: 0 14px 30px rgba(0,0,0,.28);
    gap:16px;
    min-width:180px;
    z-index:60;
  }
  .menu-toggle{display:block;}
  .signin span.label{display:none;}
}

/* Hero */
.hero{
  position:relative;
  padding: 70px 24px 60px;
  overflow:hidden;
  isolation:isolate;
}
.hero-bg{
  position:absolute;
  inset:0;
  z-index:0;
  background: var(--sky) center 30%/cover no-repeat;
  transition: background-image .2s ease;
}
.hero-overlay{
  position:absolute;
  inset:0;
  z-index:1;
  background: linear-gradient(180deg,
    rgba(255,255,255,.55) 0%,
    rgba(255,255,255,.94) 100%);
  pointer-events:none;
}
.hero-inner{
  position:relative;
  z-index:2;
  max-width:760px;
  margin:0 auto;
  text-align:center;
}
.hero h1{
  font-size:34px;
  font-weight:800;
  color:var(--navy);
  letter-spacing:.02em;
  margin-bottom:16px;
  text-shadow: 0 1px 0 rgba(255,255,255,.4);
}
.hero p.lead{
  font-size:16px;
  color:#2c3e50;
  margin-bottom:6px;
}
.hero p.hours{
  font-size:13px;
  color:#5b6b7c;
  margin-bottom:30px;
}
.hero-ctas{
  display:flex;
  gap:16px;
  justify-content:center;
  flex-wrap:wrap;
}

/* ---------- Services ---------- */

.services {
  padding: 90px 24px;
  background: #f8fbff;
  text-align: center;
}

.services h2 {
  color: #002347;
  font-size: 36px;
  font-weight: 800;
  margin-bottom: 60px;
  position: relative;
}

.services h2::after {
  content: "";
  display: block;
  width: 80px;
  height: 4px;
  margin: 14px auto 0;
  border-radius: 999px;
}

.services-grid {
  display: grid;
  grid-template-columns: repeat(4, 1fr);
  gap: 28px;
  max-width: 1300px;
  margin: 0 auto;
  text-align: left;
}

.service-card {
  background: linear-gradient(180deg, #ffffff, #f8fbff);
  border: 1px solid #dbe8f4;
  border-top: 5px solid #fb923c;
  border-radius: 18px;

  min-height: 280px;
  padding: 34px 28px;

  display: flex;
  flex-direction: column;
  justify-content: center;

  box-shadow: 0 10px 24px rgba(0, 35, 71, 0.08);

  transition: all 0.35s ease;

  opacity: 0;
  transform: translateY(60px);
  transition:
    opacity 0.7s ease,
    transform 0.7s ease,
    box-shadow 0.35s ease;
}

/* Animation delays */
.service-card.show {
  opacity: 1;
  transform: translateY(0);
}
.service-card:nth-child(1) {
  animation-delay: 0.1s;
}

.service-card:nth-child(2) {
  animation-delay: 0.3s;
}

.service-card:nth-child(3) {
  animation-delay: 0.5s;
}

.service-card:nth-child(4) {
  animation-delay: 0.7s;
}

.service-card:hover {
  transform: translateY(-12px) scale(1.03);
  border-top-color: #f97316;
  box-shadow: 0 18px 40px rgba(0, 35, 71, 0.18);
}
.service-card h3 {
  color: #002347;
  font-size: 22px;
  font-weight: 700;
  margin-bottom: 16px;
  transition: color 0.3s ease;
}

.service-card:hover h3 {
  color: #f97316;
}

.service-card p {
  font-size: 15px;
  line-height: 1.8;
  color: #5f6b7a;
}

/* Fade-up animation */
@keyframes fadeUp {
  from {
    opacity: 0;
    transform: translateY(40px);
  }

  to {
    opacity: 1;
    transform: translateY(0);
  }
}


/* How to use */
.how-to{
  background:#f4f7f9;
  padding:56px 24px 70px;
  text-align:center;
}
.how-to h2{
  color:var(--navy);
  font-size:22px;
  font-weight:800;
  margin-bottom:44px;
}
.steps{
  display:flex;
  align-items:flex-start;
  justify-content:center;
  max-width:900px;
  margin:0 auto;
  gap:0;
}
.step{
  flex:1;
  display:flex;
  flex-direction:column;
  align-items:center;
  max-width:220px;
}
.step-icon{
  width:62px;height:62px;
  border-radius:50%;
  background:#f7dfc9;
  display:flex;align-items:center;justify-content:center;
  margin-bottom:16px;
  font-size:24px;
  box-shadow: 0 4px 14px rgba(217,122,61,.18);
}
.step h4{
  font-size:14px;
  font-weight:700;
  color:var(--text-dark);
  margin-bottom:6px;
}
.step p{
  font-size:12px;
  color:var(--text-muted);
}
.step-arrow{
  align-self:flex-start;
  margin-top:52px;
  display:flex;
  align-items:center;
  width:64px;
  flex-shrink:0;
}
.step-arrow .dash{
  flex:1;
  height:0;
  border-top:2px dashed var(--orange);
}
.step-arrow svg{
  flex-shrink:0;
  color: var(--orange);
  margin-left:-2px;
}

@media (max-width:860px){
  .services-grid{grid-template-columns:repeat(2,1fr);}
}
@media (max-width:720px){
  .hero h1{font-size:26px;}
  .steps{flex-direction:column;align-items:center;gap:8px;}
  .step-arrow{
    width:auto;
    height:36px;
    margin:0;
    flex-direction:column;
  }
  .step-arrow .dash{
    width:0;
    flex:1;
    border-top:none;
    border-left:2px dashed var(--orange);
  }
  .step-arrow svg{
    margin-left:0;
    margin-top:-2px;
    transform:rotate(90deg);
  }
}
@media (max-width:520px){
  .services-grid{grid-template-columns:1fr;}
}
</style>