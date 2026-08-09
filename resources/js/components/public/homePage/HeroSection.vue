<!-- Hero Section -->
<template>
  <section
    class="hero"
    :style="{ backgroundImage: `url(${bgImage})` }"
  >
    <div class="hero-overlay"></div>

    <div class="container">
      <h1>BU LEGAL AFFAIRS OFFICE PORTAL</h1>
      <p class="lead">
        Submit documents, track requests in real time, and get official
        updates from the BU Legal Affairs Office.
      </p>
      <p class="hours">Office hours: Monday – Friday, 8:00 AM – 5:00 PM</p>

      <div class="hero-ctas">
        <button class="btn btn-outline" type="button" @click="goTo('/login')">
          SUBMIT A DOCUMENT
        </button>
        <button class="btn btn-solid" type="button" @click="goTo('/track')">
          TRACK A DOCUMENT
        </button>
      </div>
    </div>
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
/* ---------- Hero shell ---------- */
.hero {
  position: relative;
  height: 100vh;
  background-size: cover;
  background-position: center 30%;
  background-repeat: no-repeat;
  display: flex;
  align-items: center;
  justify-content: center;
}

.hero-overlay {
  position: absolute;
  inset: 0;
  z-index: 1;
  background: linear-gradient(
    180deg,
    rgba(255, 255, 255, 0.55) 0%,
    rgba(255, 255, 255, 0.94) 100%
  );
  pointer-events: none;
}

/* ---------- Content container ---------- */
.container {
  position: relative;
  z-index: 2;
  width: 100%;
  max-width: 1000px;
  margin: 100px auto;
  padding: 0 24px;
  display: flex;
  flex-direction: column;
  align-items: center;
  text-align: center;
}

.container h1 {
  font-size: 50px;
  font-weight: 800;
  color: var(--navy);
  letter-spacing: 0.02em;
  margin: 0 0 16px;
  text-shadow: 0 1px 0 rgba(255, 255, 255, 0.4);
}

.container p.lead {
  font-size: 20px;
  color: #2c3e50;
  margin: 0 0 6px;
  max-width: 620px;
}

.container p.hours {
  font-size: 13px;
  color: #5b6b7c;
  margin: 0 0 100px;
}

.hero-ctas {
  display: flex;
  gap: 16px;
  justify-content: center;
  flex-wrap: wrap;
  width: 100%;
}

/* ---------- Buttons ---------- */
.btn {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  min-width: 200px;
  padding: 14px 28px;
  border-radius: 6px;
  border: 1.5px solid transparent;
  font-size: 13px;
  font-weight: 700;
  letter-spacing: 0.04em;
  text-align: center;
  text-decoration: none;
  cursor: pointer;
  transition: transform 0.12s ease, box-shadow 0.12s ease,
    background-color 0.15s ease, border-color 0.15s ease;
}

.btn:hover {
  transform: translateY(-2px);
}

.btn:active {
  transform: translateY(0);
}

.btn:focus-visible {
  outline: 2px solid var(--navy);
  outline-offset: 2px;
}

.btn-outline {
  background: var(--sky);
  border-color: var(--sky-border);
  color: var(--navy);
}

.btn-outline:hover {
  background: #dcedf9;
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
}

.btn-solid {
  background: var(--orange);
  color: var(--white);
  box-shadow: 0 2px 8px rgba(232, 147, 90, 0.35);
}

.btn-solid:hover {
  background: var(--orange-dark);
  box-shadow: 0 6px 16px rgba(232, 147, 90, 0.45);
}

/* ---------- Responsive ---------- */
@media (max-width: 480px) {
  .container h1 {
    font-size: 26px;
  }

  .hero-ctas {
    flex-direction: column;
    align-items: stretch;
  }

  .btn {
    width: 100%;
  }
}
</style>