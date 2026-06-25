<script setup>
import { computed, ref, provide } from 'vue'
import { Link } from '@inertiajs/vue3'

const props = defineProps({
    event: { type: Object, required: true },
    guest: { type: Object, required: true },
})

const initials = computed(() => {
    const parts = props.guest.name.split(' ')
    return parts.length >= 2
        ? `${parts[0][0]}${parts[parts.length - 1][0]}`.toUpperCase()
        : props.guest.name.substring(0, 2).toUpperCase()
})

// Toast system
const toasts = ref([])
let toastIdCounter = 0

function addToast(message, type = 'success') {
    const id = toastIdCounter++
    toasts.value.push({ id, message, type })
    setTimeout(() => {
        removeToast(id)
    }, 4000)
}

function removeToast(id) {
    const index = toasts.value.findIndex(t => t.id === id)
    if (index > -1) toasts.value.splice(index, 1)
}

provide('toast', addToast)
</script>

<template>
  <div class="portal-shell">
    <!-- Header -->
    <header class="portal-header">
      <div class="portal-container">
        <div class="portal-header-inner">
          <div class="portal-logo">
            <span class="portal-logo-icon">{{ event.logo || '⚽' }}</span>
            <div class="portal-logo-text">
              <div class="portal-logo-title">{{ event.name }}</div>
              <div class="portal-logo-subtitle">Guest Portal</div>
            </div>
          </div>
          
          <div class="portal-user">
            <div class="portal-avatar">{{ initials }}</div>
            <div class="portal-user-info">
              <div class="portal-user-name">{{ guest.name }}</div>
              <div class="portal-user-ref">Ref: {{ guest.reference_number }}</div>
            </div>
          </div>
        </div>
      </div>
    </header>

    <!-- Main content -->
    <main class="portal-main">
      <div class="portal-container">
        <slot />
      </div>
    </main>

    <!-- Footer -->
    <footer class="portal-footer">
      <div class="portal-container">
        <p>{{ event.name }} · {{ event.location }}</p>
        <p>For assistance, please contact your protocol officer.</p>
      </div>
    </footer>

    <!-- Toast notifications -->
    <Teleport to="body">
      <div class="portal-toast-container">
        <div v-for="toast in toasts" :key="toast.id" class="portal-toast" :class="toast.type">
          {{ toast.message }}
        </div>
      </div>
    </Teleport>
  </div>
</template>

<style>
/* Portal Design System */
:root {
    --portal-maroon: #8a1f3d;
    --portal-gold: #c4973a;
    --portal-bg: #fafaf9;
    --portal-surface: #ffffff;
    --portal-text: #1a1210;
    --portal-text-2: #6b5c53;
    --portal-text-3: #a09488;
    --portal-border: rgba(26, 18, 16, 0.1);
}

.portal-shell {
    min-height: 100vh;
    background: var(--portal-bg);
    display: flex;
    flex-direction: column;
    font-family: system-ui, -apple-system, sans-serif;
}

.portal-header {
    background: var(--portal-surface);
    border-bottom: 1px solid var(--portal-border);
    padding: 16px 0;
    position: sticky;
    top: 0;
    z-index: 100;
}

.portal-container {
    max-width: 1200px;
    margin: 0 auto;
    padding: 0 24px;
}

.portal-header-inner {
    display: flex;
    align-items: center;
    justify-content: space-between;
}

.portal-logo {
    display: flex;
    align-items: center;
    gap: 12px;
}

.portal-logo-icon {
    font-size: 32px;
    line-height: 1;
}

.portal-logo-title {
    font-size: 18px;
    font-weight: 600;
    color: var(--portal-text);
    line-height: 1.2;
}

.portal-logo-subtitle {
    font-size: 12px;
    color: var(--portal-text-2);
}

.portal-user {
    display: flex;
    align-items: center;
    gap: 12px;
}

.portal-avatar {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    background: var(--portal-maroon);
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 600;
    font-size: 14px;
}

.portal-user-name {
    font-size: 14px;
    font-weight: 500;
    color: var(--portal-text);
}

.portal-user-ref {
    font-size: 11px;
    color: var(--portal-text-3);
}

.portal-main {
    flex: 1;
    padding: 32px 0;
}

.portal-footer {
    background: var(--portal-surface);
    border-top: 1px solid var(--portal-border);
    padding: 24px 0;
    text-align: center;
    color: var(--portal-text-2);
    font-size: 12px;
}

.portal-footer p {
    margin: 4px 0;
}

/* Card components */
.portal-card {
    background: var(--portal-surface);
    border: 1px solid var(--portal-border);
    border-radius: 8px;
    padding: 24px;
    margin-bottom: 24px;
}

.portal-card-title {
    font-size: 18px;
    font-weight: 600;
    color: var(--portal-text);
    margin-bottom: 16px;
}

.portal-card-section {
    margin-bottom: 20px;
}

.portal-card-section:last-child {
    margin-bottom: 0;
}

.portal-label {
    font-size: 12px;
    font-weight: 500;
    color: var(--portal-text-2);
    text-transform: uppercase;
    letter-spacing: 0.05em;
    margin-bottom: 6px;
}

.portal-value {
    font-size: 15px;
    color: var(--portal-text);
}

.portal-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
    gap: 24px;
}

.portal-badge {
    display: inline-block;
    padding: 4px 10px;
    border-radius: 12px;
    font-size: 12px;
    font-weight: 500;
}

.portal-badge.success {
    background: #dcfce7;
    color: #166534;
}

.portal-badge.warning {
    background: #fef3c7;
    color: #92400e;
}

.portal-badge.info {
    background: #dbeafe;
    color: #1e40af;
}

.portal-btn {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 10px 16px;
    border: 1px solid var(--portal-border);
    border-radius: 6px;
    background: var(--portal-surface);
    color: var(--portal-text);
    font-size: 14px;
    font-weight: 500;
    cursor: pointer;
    transition: all 0.2s;
}

.portal-btn:hover {
    background: var(--portal-bg);
    border-color: var(--portal-text-3);
}

.portal-btn-primary {
    background: var(--portal-maroon);
    color: white;
    border-color: var(--portal-maroon);
}

.portal-btn-primary:hover {
    background: #701832;
    border-color: #701832;
}

/* Form elements */
.portal-form-group {
    margin-bottom: 20px;
}

.portal-form-label {
    display: block;
    font-size: 13px;
    font-weight: 500;
    color: var(--portal-text);
    margin-bottom: 6px;
}

.portal-form-label .required {
    color: #dc2626;
    margin-left: 2px;
}

.portal-input,
.portal-select,
.portal-textarea {
    width: 100%;
    padding: 10px 12px;
    border: 1px solid var(--portal-border);
    border-radius: 6px;
    background: var(--portal-surface);
    color: var(--portal-text);
    font-size: 14px;
    font-family: inherit;
    transition: all 0.2s;
}

.portal-input:focus,
.portal-select:focus,
.portal-textarea:focus {
    outline: none;
    border-color: var(--portal-maroon);
    box-shadow: 0 0 0 3px rgba(138, 31, 61, 0.1);
}

.portal-input::placeholder,
.portal-textarea::placeholder {
    color: var(--portal-text-3);
}

.portal-select {
    cursor: pointer;
}

.portal-textarea {
    resize: vertical;
    min-height: 80px;
}

.portal-form-help {
    font-size: 12px;
    color: var(--portal-text-3);
    margin-top: 4px;
}

.portal-form-error {
    font-size: 12px;
    color: #dc2626;
    margin-top: 4px;
}

/* Modal */
.portal-modal-overlay {
    position: fixed;
    inset: 0;
    background: rgba(0, 0, 0, 0.5);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 1000;
    padding: 24px;
}

.portal-modal {
    background: var(--portal-surface);
    border-radius: 12px;
    max-width: 600px;
    width: 100%;
    max-height: 90vh;
    overflow: hidden;
    display: flex;
    flex-direction: column;
}

.portal-modal-header {
    padding: 20px 24px;
    border-bottom: 1px solid var(--portal-border);
}

.portal-modal-title {
    font-size: 20px;
    font-weight: 600;
    color: var(--portal-text);
}

.portal-modal-subtitle {
    font-size: 13px;
    color: var(--portal-text-2);
    margin-top: 4px;
}

.portal-modal-body {
    padding: 24px;
    overflow-y: auto;
    flex: 1;
}

.portal-modal-footer {
    padding: 16px 24px;
    border-top: 1px solid var(--portal-border);
    display: flex;
    align-items: center;
    justify-content: flex-end;
    gap: 12px;
}

.portal-form-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 16px;
}

@media (max-width: 640px) {
    .portal-form-row {
        grid-template-columns: 1fr;
    }
}

/* Toast notifications */
.portal-toast-container {
    position: fixed;
    bottom: 24px;
    right: 24px;
    z-index: 9999;
    display: flex;
    flex-direction: column;
    gap: 12px;
    pointer-events: none;
}

.portal-toast {
    background: var(--portal-surface);
    border: 1px solid var(--portal-border);
    border-radius: 8px;
    padding: 14px 18px;
    min-width: 300px;
    max-width: 400px;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
    font-size: 14px;
    color: var(--portal-text);
    pointer-events: auto;
    animation: portalToastSlide 0.3s ease;
    border-left: 3px solid var(--portal-maroon);
}

.portal-toast.success {
    border-left-color: #10b981;
}

.portal-toast.error {
    border-left-color: #ef4444;
    background: #fef2f2;
}

.portal-toast.info {
    border-left-color: #3b82f6;
}

@keyframes portalToastSlide {
    from {
        opacity: 0;
        transform: translateX(100px);
    }
    to {
        opacity: 1;
        transform: translateX(0);
    }
}

@media (max-width: 640px) {
    .portal-toast-container {
        left: 16px;
        right: 16px;
        bottom: 16px;
    }
    
    .portal-toast {
        min-width: unset;
    }
}

/* Flatpickr portal overrides */
.flatpickr-calendar {
    font-family: system-ui, -apple-system, sans-serif;
}

.flatpickr-day.selected,
.flatpickr-day.startRange,
.flatpickr-day.endRange,
.flatpickr-day.selected:hover,
.flatpickr-day.startRange:hover,
.flatpickr-day.endRange:hover {
    background: var(--portal-maroon);
    border-color: var(--portal-maroon);
}

.flatpickr-day.today {
    border-color: var(--portal-maroon);
}

.flatpickr-day.today:hover {
    border-color: var(--portal-maroon);
}
</style>
