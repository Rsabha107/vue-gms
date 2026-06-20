<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';

defineProps({
    canResetPassword: {
        type: Boolean,
    },
    status: {
        type: String,
    },
});

const form = useForm({
    email: '',
    password: '',
    remember: false,
});

const submit = () => {
    form.post(route('login'), {
        onFinish: () => form.reset('password'),
    });
};
</script>

<template>
    <Head title="Log in — GMS" />

    <div class="gms-login-page">
        <div class="gms-login-container">
            <!-- Logo Section -->
            <div class="gms-login-header">
                <div class="gms-login-logo">
                    <div class="gms-login-monogram">
                        <svg width="40" height="40" viewBox="0 0 40 40" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <circle cx="20" cy="20" r="18" fill="var(--gms-maroon)" opacity="0.08"/>
                            <text x="20" y="27" font-family="var(--f-display)" font-size="20" font-weight="600" fill="var(--gms-maroon)" text-anchor="middle">G</text>
                        </svg>
                    </div>
                    <h1 class="gms-login-title">Guest Management System</h1>
                    <p class="gms-login-subtitle">VIP Protocol & Event Management</p>
                </div>
            </div>

            <!-- Status Message -->
            <div v-if="status" class="gms-login-status">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/>
                    <polyline points="22 4 12 14.01 9 11.01"/>
                </svg>
                {{ status }}
            </div>

            <!-- Login Form -->
            <form @submit.prevent="submit" class="gms-login-form">
                <!-- Email Field -->
                <div class="gms-login-field">
                    <label for="email" class="gms-login-label">Email</label>
                    <input
                        id="email"
                        type="email"
                        class="gms-login-input"
                        :class="{ 'gms-login-input-error': form.errors.email }"
                        v-model="form.email"
                        required
                        autofocus
                        autocomplete="username"
                        placeholder="you@example.com"
                    />
                    <span v-if="form.errors.email" class="gms-login-error">{{ form.errors.email }}</span>
                </div>

                <!-- Password Field -->
                <div class="gms-login-field">
                    <label for="password" class="gms-login-label">Password</label>
                    <input
                        id="password"
                        type="password"
                        class="gms-login-input"
                        :class="{ 'gms-login-input-error': form.errors.password }"
                        v-model="form.password"
                        required
                        autocomplete="current-password"
                        placeholder="Enter your password"
                    />
                    <span v-if="form.errors.password" class="gms-login-error">{{ form.errors.password }}</span>
                </div>

                <!-- Remember Me & Forgot Password -->
                <div class="gms-login-options">
                    <label class="gms-login-checkbox">
                        <input type="checkbox" v-model="form.remember" />
                        <span class="gms-login-checkbox-label">Remember me</span>
                    </label>
                    <Link
                        v-if="canResetPassword"
                        :href="route('password.request')"
                        class="gms-login-link"
                    >
                        Forgot password?
                    </Link>
                </div>

                <!-- Submit Button -->
                <button
                    type="submit"
                    class="gms-login-btn"
                    :disabled="form.processing"
                >
                    <span v-if="!form.processing">Log in</span>
                    <span v-else class="gms-login-spinner">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M21 12a9 9 0 1 1-6.219-8.56"/>
                        </svg>
                        Logging in...
                    </span>
                </button>
            </form>

            <!-- Footer -->
            <div class="gms-login-footer">
                <p class="gms-login-footer-text">
                    Qatar Protocol Services · Confidential
                </p>
            </div>
        </div>
    </div>
</template>

<style scoped>
.gms-login-page {
    min-height: 100vh;
    display: flex;
    align-items: center;
    justify-content: center;
    background: var(--gms-bg, #f5f0eb);
    padding: 24px;
    font-family: var(--f-ui, 'Hanken Grotesk', sans-serif);
}

.gms-login-container {
    width: 100%;
    max-width: 420px;
    background: var(--gms-surface, #ffffff);
    border-radius: var(--gms-radius-lg, 14px);
    box-shadow: var(--gms-shadow-lg, 0 8px 32px rgba(26,18,16,0.14));
    padding: 40px;
}

/* Header */
.gms-login-header {
    text-align: center;
    margin-bottom: 32px;
}

.gms-login-logo {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 12px;
}

.gms-login-monogram {
    margin-bottom: 4px;
}

.gms-login-title {
    font-family: var(--f-display, 'Instrument Serif', serif);
    font-size: 24px;
    font-weight: 600;
    color: var(--gms-text, #1a1210);
    margin: 0;
    line-height: 1.2;
}

.gms-login-subtitle {
    font-size: 13px;
    color: var(--gms-text-3, #a09488);
    margin: 0;
    font-weight: 500;
}

/* Status Message */
.gms-login-status {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 12px 16px;
    background: var(--good-soft, #e6f0e7);
    color: var(--good, #3f7d52);
    border-radius: var(--gms-radius-sm, 6px);
    font-size: 13px;
    font-weight: 500;
    margin-bottom: 24px;
}

/* Form */
.gms-login-form {
    display: flex;
    flex-direction: column;
    gap: 20px;
}

.gms-login-field {
    display: flex;
    flex-direction: column;
    gap: 6px;
}

.gms-login-label {
    font-size: 13px;
    font-weight: 600;
    color: var(--gms-text, #1a1210);
    letter-spacing: -0.01em;
}

.gms-login-input {
    width: 100%;
    padding: 11px 14px;
    font-size: 14px;
    font-family: inherit;
    color: var(--gms-text, #1a1210);
    background: var(--gms-surface, #ffffff);
    border: 1.5px solid var(--gms-border, rgba(26,18,16,0.10));
    border-radius: var(--gms-radius-sm, 6px);
    transition: all 150ms;
    outline: none;
}

.gms-login-input:focus {
    border-color: var(--gms-maroon, #8a1f3d);
    box-shadow: 0 0 0 3px var(--gms-maroon-light, rgba(138, 31, 61, 0.10));
}

.gms-login-input::placeholder {
    color: var(--gms-text-3, #a09488);
}

.gms-login-input-error {
    border-color: var(--bad, #b14638);
}

.gms-login-input-error:focus {
    border-color: var(--bad, #b14638);
    box-shadow: 0 0 0 3px rgba(177, 70, 56, 0.10);
}

.gms-login-error {
    font-size: 12px;
    color: var(--bad, #b14638);
    font-weight: 500;
}

/* Options */
.gms-login-options {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-top: 4px;
}

.gms-login-checkbox {
    display: flex;
    align-items: center;
    gap: 8px;
    cursor: pointer;
}

.gms-login-checkbox input[type="checkbox"] {
    width: 16px;
    height: 16px;
    border-radius: 4px;
    border: 1.5px solid var(--gms-border, rgba(26,18,16,0.10));
    cursor: pointer;
    accent-color: var(--gms-maroon, #8a1f3d);
}

.gms-login-checkbox-label {
    font-size: 13px;
    color: var(--gms-text-2, #6b5c53);
    font-weight: 500;
}

.gms-login-link {
    font-size: 13px;
    color: var(--gms-maroon, #8a1f3d);
    text-decoration: none;
    font-weight: 600;
    transition: color 150ms;
}

.gms-login-link:hover {
    color: var(--gms-maroon-hover, #a32448);
}

/* Submit Button */
.gms-login-btn {
    width: 100%;
    padding: 12px 20px;
    margin-top: 8px;
    font-size: 14px;
    font-weight: 600;
    font-family: inherit;
    color: #ffffff;
    background: var(--gms-maroon, #8a1f3d);
    border: none;
    border-radius: var(--gms-radius-sm, 6px);
    cursor: pointer;
    transition: all 150ms;
    box-shadow: 0 1px 3px rgba(138, 31, 61, 0.20);
}

.gms-login-btn:hover:not(:disabled) {
    background: var(--gms-maroon-hover, #a32448);
    box-shadow: 0 2px 8px rgba(138, 31, 61, 0.30);
}

.gms-login-btn:active:not(:disabled) {
    transform: translateY(1px);
}

.gms-login-btn:disabled {
    opacity: 0.6;
    cursor: not-allowed;
}

.gms-login-spinner {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
}

.gms-login-spinner svg {
    animation: spin 1s linear infinite;
}

@keyframes spin {
    from {
        transform: rotate(0deg);
    }
    to {
        transform: rotate(360deg);
    }
}

/* Footer */
.gms-login-footer {
    margin-top: 32px;
    padding-top: 24px;
    border-top: 1px solid var(--gms-border, rgba(26,18,16,0.10));
    text-align: center;
}

.gms-login-footer-text {
    font-size: 11.5px;
    color: var(--gms-text-3, #a09488);
    margin: 0;
    font-weight: 500;
    letter-spacing: 0.02em;
}

/* Responsive */
@media (max-width: 480px) {
    .gms-login-container {
        padding: 32px 24px;
    }

    .gms-login-title {
        font-size: 21px;
    }

    .gms-login-subtitle {
        font-size: 12px;
    }
}
</style>
