<template>
  <section class="hero-section">
    <div class="hero-container">
      <div class="hero-text text-left">
      <h1>
          <span class="text-white">BU LEGAL AFFAIRS</span><br />
          <span class="text-purple">OFFICE PORTAL</span>
        </h1>
        <p class="lead text-white">
          Submit documents, track requests, and get official
          updates from the BU Legal Affairs Office.
        </p>
        <p class="hours text-gray">Office hours : Monday - Friday, 8:00 AM - 5:00 PM</p>

        <div class="hero-ctas">
          <button class="btn btn-solid" type="button" @click="goTo('/login')">
            SUBMIT A DOCUMENT
          </button>
          
          <button class="btn btn-outline" type="button" @click="goTo('/track')">
            TRACK A DOCUMENT
          </button>
        </div>
      </div>
    </div>

    <div 
      class="hero-image-circle" 
      :style="{ backgroundImage: `url(${bgImage})` }"
    ></div>
  </section>
</template>

<script setup>
import { useRouter } from 'vue-router'
import bgImage from '../../../../images/hero-bg.png'

const router = useRouter()

function goTo(path) {
  router.push(path)
}
</script>

<style scoped>
.hero-section {
  background-color: #0F172A;
  min-height: 100vh;
  display: flex;
  align-items: center;
  padding: 80px 0 40px; 
  overflow: hidden;
  position: relative;
}

.hero-container {
  width: 100%;
  max-width: 1280px !important; 
  margin: 0 auto !important;
  padding: 0 40px !important; 
}

.hero-text {
  max-width: 600px;
  margin-left: 0 !important; 
  transform: none !important; 
}

.hero-text h1 {
  font-size: 64px;
  font-weight: 800;
  line-height: 1.15;
  margin-bottom: 30px; 
  letter-spacing: 0.02em;
}

.text-white { color: #ffffff; }
.text-purple { color: #6b77ff; }

.lead {
  font-size: 19px;
  color: #f1f5f9;
  margin-bottom: 16px; 
  line-height: 1.6;
}

.hours {
  font-size: 15px;
  color: #cbd5e1;
  margin-bottom: 48px; 
}

.hero-ctas {
  display: flex;
  gap: 20px; 
  flex-wrap: wrap;
  margin-top: 40px !important;
}

.btn {
  padding-top: 16px !important;
  padding-bottom: 16px !important;
  padding-left: 40px !important; 
  padding-right: 40px !important; 
  
  border-radius: 8px;
  font-size: 15px !important;
  font-weight: 700 !important;
  letter-spacing: 0.04em;
  cursor: pointer;
  border: 2px solid transparent;
  transition: all 0.25s ease;
  text-transform: uppercase;
  
  white-space: nowrap !important; 
}

.btn-solid {
  background: #6b77ff;
  color: #ffffff;
}

.btn-solid:hover {
  background: #5a65e0;
}

.btn-outline {
  background: #ffffff;
  color: #6b77ff;
}

.btn-outline:hover {
  background: #f1f5f9;
}

.hero-image-circle {
  position: absolute;
  right: 0; 
  top: 50%;
  transform: translate(45%, -65%); 
  width: 1400px;
  height: 1400px;
  border-radius: 50%;
  background-size: cover;
  background-position: center left;
  background-repeat: no-repeat;
  border: 32px solid #6b77ff; 
  box-shadow: 0 0 0 32px #ffffff; 
  z-index: 1;
}

@media (max-width: 1024px) {
  .hero-image-circle {
    transform: translate(45%, -65%); 
    width: 1000px;
    height: 1000px;
  }
  .hero-text h1 { font-size: 44px; }
  .hero-container { padding: 0 24px; } 
}

@media (max-width: 768px) {
  .hero-image-circle {
    opacity: 0.15;
    transform: translate(50%, -15%);
    top: 40%;
    width: 700px;
    height: 700px;
  }
  .hero-text {
    max-width: 100%;
    text-align: center;
  }
  .hero-ctas {
    justify-content: center;
    flex-direction: column;
  }
  .btn {
    width: 100%;
  }
  .hero-text h1 { font-size: 36px; }
}
</style>