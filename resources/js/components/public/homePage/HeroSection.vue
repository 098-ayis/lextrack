<template>
  <section class="hero-section">
    <div class="hero-container">
      <div class="hero-text text-left">
      <h1>
          <span class="text-white">BU LEGAL AFFAIRS</span>
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
  padding: 100px 0 40px;
  overflow: hidden;
  position: relative;
}

.hero-container {
  width: 100%;
  max-width: 1280px !important; 
  margin: 0 auto !important;
  padding: 0 40px !important;
  position: relative;
  z-index: 2;
}

.hero-text {
  max-width: 720px;
  margin-left: 0 !important; 
  transform: none !important; 
  position: relative;
  z-index: 2;
}

.hero-text h1 {
  font-size: clamp(36px, 3.1vw, 52px);
  font-weight: 800;
  line-height: 1.15;
  margin-bottom: 30px; 
  letter-spacing: 0.02em;
}

.hero-text h1 > span {
  display: block;
  white-space: nowrap;
}

.text-white { color: #ffffff; }
.text-purple { color: #6b77ff; }

.lead {
  max-width: 620px;
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
  pointer-events: none;
}

/* Scale the artwork down on smaller desktop/tablet widths while keeping it
   beside the copy. */
@media (max-width: 1400px) {
  .hero-section {
    padding: 132px 0 72px;
  }

  .hero-container {
    max-width: 1280px !important;
    padding: 0 32px !important;
    margin-top: 28px !important;
  }

  .hero-text {
    max-width: 56%;
  }

  .hero-image-circle {
    top: 54%;
    right: -25%;
    transform: translateY(-50%);
    width: min(70vw, 980px);
    height: min(70vw, 980px);
    border-width: 22px;
    box-shadow: 0 0 0 22px #ffffff;
  }
}

@media (max-width: 1024px) {
  .hero-text h1 { font-size: clamp(36px, 5vw, 50px); }
  .hero-container { padding: 0 24px !important; }
}

@media (max-width: 900px) {
  .hero-section {
    display: block;
    min-height: auto;
    padding: 120px 0 72px;
  }

  .hero-container {
    max-width: 820px !important;
    margin-top: 0 !important;
    padding: 0 32px !important;
  }

  .hero-text {
    max-width: 760px;
  }

  .hero-text h1 {
    font-size: clamp(36px, 7vw, 52px);
  }

  .hero-image-circle {
    position: relative;
    top: auto;
    right: auto;
    transform: none;
    width: min(72vw, 620px);
    height: min(72vw, 620px);
    margin: 64px auto -180px;
    border-width: 20px;
    box-shadow: 0 0 0 20px #ffffff;
    opacity: 0.9;
  }
}

@media (max-width: 768px) {
  .hero-section {
    padding-top: 96px;
  }

  .hero-image-circle {
    width: min(78vw, 500px);
    height: min(78vw, 500px);
    margin-top: 48px;
    margin-bottom: -120px;
    border-width: 14px;
    box-shadow: 0 0 0 14px #ffffff;
    opacity: 0.3;
  }

  .hero-text {
    max-width: 100%;
    text-align: center;
  }

  .hero-text h1 { font-size: clamp(32px, 9vw, 44px); }
  .lead { font-size: 17px; }
  .hours { margin-bottom: 32px; }

  .hero-ctas {
    justify-content: center;
    flex-direction: column;
    align-items: stretch;
    gap: 12px;
    margin-top: 24px !important;
  }

  .btn {
    width: 100%;
    padding-left: 20px !important;
    padding-right: 20px !important;
  }
}

@media (max-width: 480px) {
  .hero-container { padding: 0 18px !important; }
  .hero-text h1 { font-size: clamp(26px, 8vw, 30px); }
  .lead { font-size: 16px; }
  .hero-image-circle {
    width: min(82vw, 380px);
    height: min(82vw, 380px);
    margin-bottom: -80px;
  }
}
</style>
