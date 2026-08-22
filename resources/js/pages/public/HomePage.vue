<!-- HomePage.vue -->
<template>
  <div class="bu-portal">
    
    <!-- Hero Section -->
    <HeroSection />

    <!-- ==========================================
         TOP SECTION: Values & Mission (White)
    =========================================== -->
    <section class="mission-section">
      <!-- Massive Faded Background Text -->
      <div class="bg-text">LEGAL OFFICE</div>

      <!-- Core Values & Torch Graphic -->
      <div class="core-values">
        <!-- Left Values -->
        <div class="value-group">
          <span>Scholarship</span>
          <div class="divider"></div>
          <span>Leadership</span>
        </div>

        <!-- Torch Image -->
        <img 
          src="/resources/images/bu-torch.png" 
          alt="BU Torch" 
          class="torch-img" 
        />

        <!-- Right Values -->
        <div class="value-group">
          <span>Character</span>
          <div class="divider"></div>
          <span>Service</span>
        </div>
      </div>

      <!-- Mission / Vision Statement -->
      <div class="mission-statement">
        <h2>
          <span class="text-light">
            To be a Premier Model of Legal Integrity and Proactive Governance, Safeguarding the University's Rights and Assets while&nbsp;
          </span>
          <span class="text-bold">
            Providing Efficient, Modern Legal Stewardship that Supports Academic and Institutional Excellence.
          </span>
        </h2>
      </div>

      <!-- Overlapping Circular Button -->
      <button class="scroll-down-btn" @click="scrollToServices">
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
          <path stroke-linecap="round" stroke-linejoin="round" d="M19 14l-7 7m0 0l-7-7m7 7V3" />
        </svg>
      </button>
    </section>

    <!-- ==========================================
         BOTTOM SECTION: Services Grid (Dark)
    =========================================== -->
    <section class="services" id="services" ref="servicesSection">
      <div class="services-wrapper">
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
      </div>
    </section>

    <!-- ==========================================
         HOW TO USE OUR PORTAL SECTION
    =========================================== -->
    <section class="how-to" id="track">
      <h2 class="section-title">How To Use Our Portal</h2>
      
      <div class="steps-container">
        <template v-for="(step, i) in steps" :key="step.title">
          <div class="step-card">
            <div class="step-icon-wrapper" v-html="step.icon"></div>
            <h4 class="step-title">{{ step.title }}</h4>
            <p class="step-desc">{{ step.desc }}</p>
          </div>
          
          <!-- Connecting Arrow Line -->
          <div class="step-connector" v-if="i < steps.length - 1">
            <svg class="connector-arrow" width="100" height="24" viewBox="0 0 100 24" fill="none" xmlns="http://www.w3.org/2000/svg">
              <path d="M0 12H92M92 12L82 4M92 12L82 20" stroke="#6b77ff" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
          </div>
        </template>
      </div>
    </section>

    <!-- ==========================================
         FREQUENTLY ASKED QUESTIONS (FAQ) SECTION
    =========================================== -->
    <section class="faq-section">
      <div class="faq-container">
        
        <!-- Left Side: Header & Chatbot Callout -->
        <div class="faq-left">
          <h2 class="faq-heading">Frequently Asked<br />Questions</h2>
          <p class="faq-subtext">Still have questions?<br />Ask our chatbot for more help.</p>
          <button class="chat-btn" @click="openChatbot">
            Chat with Us
          </button>
        </div>

        <!-- Right Side: Accordion Cards -->
        <div class="faq-right">
          <div 
            class="faq-item-wrapper" 
            v-for="(faq, index) in faqs" 
            :key="index"
          >
            <div 
              class="faq-item" 
              @click="toggleFaq(index)"
            >
              <span>{{ faq.question }}</span>
              <span class="faq-icon">{{ faq.open ? '−' : '+' }}</span>
            </div>
            
            <!-- Expandable Answer Body -->
            <div v-show="faq.open" class="faq-answer">
              <p>{{ faq.answer }}</p>
            </div>
          </div>
        </div>

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

// Function to handle smooth scrolling from the new arrow button
function scrollToServices() {
  if (servicesSection.value) {
    servicesSection.value.scrollIntoView({ behavior: 'smooth' })
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
    desc: 'Fill out our online forms and upload required documents.',
    icon: `<svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="#6b77ff" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
      <path d="M9 3h6a1 1 0 0 1 1 1v1H8V4a1 1 0 0 1 1-1z"/>
      <rect x="5" y="4" width="14" height="17" rx="2"/>
      <path d="M8 11h8M8 15h5"/>
    </svg>`
  },
  {
    title: '2. Submit and Track Status',
    desc: 'Click submit and a tracking number will be generated.',
    icon: `<svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="#6b77ff" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
      <path d="M9 12l2 2 4-4"/>
      <circle cx="12" cy="12" r="9"/>
    </svg>`
  },
  {
    title: '3. Receive Updates',
    desc: 'Monitor status in real-time.',
    icon: `<svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="#6b77ff" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
      <path d="M18 8a6 6 0 0 0-12 0c0 7-3 9-3 9h18s-3-2-3-9"/>
      <path d="M13.73 21a2 2 0 0 1-3.46 0"/>
    </svg>`
  }
]

// FAQ Accordion State with sample Q&As
const faqs = reactive([
  { 
    question: 'How can I track the status of my submitted legal document?', 
    answer: 'You can check your document progress anytime by heading to the Track page and entering your unique tracking number.',
    open: false 
  },
  { 
    question: 'What types of documents require Legal Affairs review?', 
    answer: 'All university contracts, deeds, conveyances, memorandums of agreement, and official legal documents must go through office review.',
    open: false 
  },
  { 
    question: 'How long does a standard document review take?', 
    answer: 'Review timelines depend on document scope and complexity, but you can monitor updates directly through the status tracker.',
    open: false 
  },
  { 
    question: 'Where can I download official legal templates or request forms?', 
    answer: 'Standard templates and request forms are available for viewing and downloading under the Legal Forms menu section.',
    open: false 
  }
])

function toggleFaq(index) {
  faqs[index].open = !faqs[index].open
}

function openChatbot() {
  showToast('Chatbot feature coming soon!')
}
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

  overflow-x: hidden;
}
.bu-portal *{margin:0;padding:0;box-sizing:border-box;}
.bu-portal a{text-decoration:none;color:inherit;}
.bu-portal ul{list-style:none;}
.bu-portal img{max-width:100%;display:block;}


/* ---------- New Mission Section CSS ---------- */
.mission-section {
  position: relative;
  background: var(--white);
  padding: 80px 24px 140px; 
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  z-index: 10;
}

.bg-text {
  position: absolute;
  top: 50%;
  left: 50%;
  transform: translate(-50%, -50%);
  font-size: 14vw;
  font-weight: 900;
  color: transparent;
  background: linear-gradient(180deg, #e2e8f0 0%, #ffffff 100%);
  -webkit-background-clip: text;
  background-clip: text;
  z-index: 0;
  pointer-events: none;
  user-select: none;
  letter-spacing: -0.02em;
  white-space: nowrap;
  word-spacing: -1vw;
}

.core-values {
  position: relative;
  z-index: 10;
  display: flex;
  align-items: center;
  gap: 24px;
  margin-top: 20px;
}

.value-group {
  display: flex;
  align-items: center;
  gap: 24px;
  color: #6b77ff; 
  font-weight: 500;
  letter-spacing: 0.05em;
  font-size: 15px;
}

.value-group .divider {
  width: 1px;
  height: 32px;
  background: #6b77ff;
  opacity: 0.4;
}

.torch-img {
  height: 500px;
  object-fit: contain;
  margin: 0 16px;
  z-index: 20;
}

.mission-statement {
  position: relative;
  z-index: 10;
  max-width: 900px;
  text-align: center;
  margin-top: 48px;
  padding: 0 24px;
}

.mission-statement h2 {
  font-size: 30px;
  line-height: 1.5;
  letter-spacing: -0.01em;
}

.mission-statement .text-light {
  color: #a5b4fc; 
  font-weight: 700;
}

.mission-statement .text-bold {
  color: #000000;
  font-weight: 900;
}

/* Overlapping Circular Button */
.scroll-down-btn {
  position: absolute;
  bottom: -46px; 
  left: 50%;
  transform: translateX(-50%);
  width: 80px;
  height: 80px;
  background: #ffffff;
  border-radius: 50%;
  border: 6px solid #ffffff; 
  box-shadow: 
    0 0 0 6px #6b77ff, 
    0 0 0 12px #ffffff, 
    0 8px 16px 12px rgba(0,0,0,0.15);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 30;
  cursor: pointer;
  transition: all 0.3s ease;
}

.scroll-down-btn:hover {
  background: #f8fafc;
  transform: translateX(-50%) scale(1.05);
}

.scroll-down-btn svg {
  width: 32px;
  height: 32px;
  color: #000000;
}


/* ---------- Updated Services (Dark Mode) ---------- */
.services {
  padding: 120px 24px 90px;
  background: #0F172A;      
  text-align: left;
  position: relative;
  z-index: 5;
}

.services-wrapper {
  max-width: 1300px;
  margin: 0 auto;
}

.services h2 {
  color: #6b77ff;           
  font-size: 34px;
  font-weight: 800;
  margin-bottom: 60px;
  position: relative;
}

.services h2::after {
  display: none;
}

.services-grid {
  display: grid;
  grid-template-columns: repeat(4, 1fr);
  gap: 28px;
}

.service-card {
  background: #f8fafc; 
  border-radius: 12px;
  min-height: 280px;
  padding: 34px 28px;
  display: flex;
  flex-direction: column;
  justify-content: center;
  box-shadow: 0 10px 24px rgba(0, 0, 0, 0.2);
  transition: all 0.35s ease;
  
  opacity: 0;
  transform: translateY(60px);
  transition:
    opacity 0.7s ease,
    transform 0.7s ease,
    box-shadow 0.35s ease;
}

.service-card.show {
  opacity: 1;
  transform: translateY(0);
}
.service-card:nth-child(1) { animation-delay: 0.1s; }
.service-card:nth-child(2) { animation-delay: 0.3s; }
.service-card:nth-child(3) { animation-delay: 0.5s; }
.service-card:nth-child(4) { animation-delay: 0.7s; }

.service-card:hover {
  transform: translateY(-10px) scale(1.02);
  box-shadow: 0 18px 40px rgba(0, 0, 0, 0.4);
}

.service-card h3 {
  color: #000000;
  font-size: 20px;
  font-weight: 800;
  margin-bottom: 16px;
  transition: color 0.3s ease;
}

.service-card:hover h3 {
  color: #6b77ff; 
}

.service-card p {
  font-size: 15px;
  line-height: 1.6;
  color: #334155;
}


/* ---------- How To Use Our Portal ---------- */
.how-to {
  background: #ffffff;
  padding: 100px 24px 80px;
  text-align: center;
}

.section-title {
  color: #6b77ff;
  font-size: 34px;
  font-weight: 800;
  margin-bottom: 70px;
  letter-spacing: -0.01em;
}

.steps-container {
  display: flex;
  align-items: center;
  justify-content: center;
  max-width: 1100px;
  margin: 0 auto;
  gap: 20px;
}

.step-card {
  flex: 1;
  display: flex;
  flex-direction: column;
  align-items: center;
  max-width: 300px;
}

.step-icon-wrapper {
  width: 90px;
  height: 90px;
  border-radius: 50%;
  border: 1.5px solid #dbeafe;
  background: #f8fafc;
  display: flex;
  align-items: center;
  justify-content: center;
  margin-bottom: 24px;
  box-shadow: 0 10px 25px rgba(107, 119, 255, 0.1);
}

.step-title {
  font-size: 18px;
  font-weight: 800;
  color: #000000;
  margin-bottom: 12px;
}

.step-desc {
  font-size: 14px;
  color: #4b5563;
  line-height: 1.6;
}

.step-connector {
  display: flex;
  align-items: center;
  justify-content: center;
  margin-top: -40px;
}


/* ---------- FAQ Section (Dark Box) ---------- */
.faq-section {
  background: #ffffff;
  padding: 40px 24px 100px;
}

.faq-container {
  max-width: 1250px;
  margin: 0 auto;
  background: #0F172A;
  border-radius: 24px;
  padding: 70px 60px;
  display: grid;
  grid-template-columns: 1fr 1.3fr;
  gap: 60px;
  align-items: start;
  box-shadow: 0 20px 40px rgba(0, 0, 0, 0.15);
}

.faq-left {
  color: #ffffff;
}

.faq-heading {
  font-size: 40px;
  font-weight: 900;
  line-height: 1.2;
  margin-bottom: 20px;
  color: #ffffff;
}

.faq-subtext {
  font-size: 16px;
  color: #94a3b8;
  line-height: 1.6;
  margin-bottom: 35px;
}

.chat-btn {
  background: #6b77ff;
  color: #ffffff;
  font-weight: 700;
  font-size: 15px;
  padding: 14px 32px;
  border-radius: 12px;
  border: none;
  cursor: pointer;
  transition: all 0.3s ease;
  box-shadow: 0 8px 20px rgba(107, 119, 255, 0.3);
}

.chat-btn:hover {
  background: #5763e0;
  transform: translateY(-2px);
}

.faq-right {
  display: flex;
  flex-direction: column;
  gap: 16px;
}

.faq-item-wrapper {
  background: #64748b;
  border-radius: 12px;
  overflow: hidden;
  transition: background 0.2s ease;
}

.faq-item-wrapper:hover {
  background: #475569;
}

.faq-item {
  color: #ffffff;
  padding: 20px 24px;
  font-size: 15px;
  font-weight: 600;
  display: flex;
  justify-content: space-between;
  align-items: center;
  cursor: pointer;
}

.faq-icon {
  font-size: 20px;
  font-weight: 700;
}

.faq-answer {
  padding: 0 24px 20px 24px;
  color: #e2e8f0;
  font-size: 14px;
  line-height: 1.6;
  border-top: 1px solid rgba(255, 255, 255, 0.1);
  margin-top: -4px;
  padding-top: 14px;
}

@media (max-width: 900px) {
  .steps-container { flex-direction: column; gap: 40px; }
  .step-connector { display: none; }
  .faq-container { grid-template-columns: 1fr; padding: 40px 24px; }
}

@media (max-width:860px){
  .services-grid{grid-template-columns:repeat(2,1fr);}
  .mission-statement h2 { font-size: 24px; }
  .bg-text { font-size: 18vw; }
}

@media (max-width:720px){
  .core-values { flex-direction: column; }
  .value-group .divider { width: 32px; height: 1px; }
  .torch-img { height: 280px; margin: 24px 0; }
}

@media (max-width:520px){
  .services-grid{grid-template-columns:1fr;}
}
</style>