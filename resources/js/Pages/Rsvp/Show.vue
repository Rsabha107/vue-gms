<script setup>
import { reactive, computed } from 'vue'
import { Head, useForm } from '@inertiajs/vue3'

/**
 * Public guest-facing RSVP page.
 * Accessed via token from invitation email - no authentication required.
 */
const props = defineProps({
  token:     { type: String, required: true },
  event:     { type: Object, required: true },   // { name, venue, dates }
  guest:     { type: Object, required: true },
  subject:   { type: String, default: '' },
  body:      { type: String, default: '' },
  matches:   { type: Array, required: true },
  submitted: { type: Boolean, default: false },
})

// Local response state, seeded from any saved pivot responses
const resp = reactive(Object.fromEntries(props.matches.map((m) => [m.id, m.response || null])))
const form = useForm({ responses: resp })

const set = (id, v) => (resp[id] = resp[id] === v ? null : v)
const yesCount = computed(() => props.matches.filter((m) => resp[m.id] === 'yes').length)
const decided = computed(() => props.matches.filter((m) => resp[m.id]).length)
const lastName = computed(() => props.guest.name.split(' ').slice(-1)[0])

function confirm() {
  form.responses = { ...resp }
  form.post(`/rsvp/${props.token}`, { preserveScroll: true })
}
</script>

<template>
  <Head :title="`RSVP — ${event.name}`" />

  <div class="ginv-scrim">
    <div class="ginv">
      <div class="ginv-head">
        <div class="crest">🏆</div>
        <div class="ev">{{ event.name }}</div>
        <div class="dt">{{ event.venue }} · {{ event.dates }}</div>
      </div>

      <div v-if="!submitted && !form.recentlySuccessful" class="ginv-body">
        <div class="ginv-greet">{{ body }}</div>

        <div class="ginv-sec-h">Select the matches you'll attend</div>
        <div class="ginv-sec-sub">Tap "Attending" for each match you can join.</div>

        <div
          v-for="m in matches"
          :key="m.id"
          class="rsvp-match"
          :class="{ yes: resp[m.id] === 'yes', no: resp[m.id] === 'no' }"
        >
          <div class="rsvp-top">
            <span class="mc-stage" :class="{ final: m.stage === 'Final' }">{{ m.stage }}</span>
          </div>
          <div class="rsvp-teams">
            <span v-if="m.homeFlag" class="fl">{{ m.homeFlag }}</span>
            {{ m.homeTeam }}
            <span class="vs">v</span>
            <span v-if="m.awayFlag" class="fl">{{ m.awayFlag }}</span>
            {{ m.awayTeam }}
          </div>
          <div class="rsvp-when">{{ m.date }} · {{ m.time }} · {{ m.venue }}</div>
          <div class="rsvp-toggle">
            <button class="rsvp-btn" :class="{ 'on-yes': resp[m.id] === 'yes' }" @click="set(m.id, 'yes')">✓ Attending</button>
            <button class="rsvp-btn" :class="{ 'on-no': resp[m.id] === 'no' }" @click="set(m.id, 'no')">✕ Can't make it</button>
          </div>
        </div>

        <div class="ginv-bar">
          <div><b>{{ yesCount }}</b> <span class="muted">attending · {{ decided }}/{{ matches.length }} answered</span></div>
          <button class="btn pri push" :disabled="decided === 0 || form.processing" @click="confirm">Confirm my selection</button>
        </div>
      </div>

      <div v-else class="ginv-body">
        <div class="ginv-success">
          <div class="ck">✓</div>
          <h3>Thank you, {{ lastName }}!</h3>
          <div class="muted narrow">
            Your selection is confirmed. You'll attend <b>{{ yesCount }}</b> match{{ yesCount === 1 ? '' : 'es' }}.
            Tickets & logistics will follow by email.
          </div>
          <div class="confirmed-matches">
            <div
              v-for="m in matches.filter((x) => resp[x.id] === 'yes')"
              :key="m.id"
              class="conf-match"
            >
              <div class="mc-stage">{{ m.stage }}</div>
              <div class="conf-teams">
                <span v-if="m.homeFlag" class="fl">{{ m.homeFlag }}</span>
                {{ m.homeTeam }} v {{ m.awayTeam }}
              </div>
              <div class="conf-meta">{{ m.date }} · {{ m.time }} · {{ m.venue }}</div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
/* Additional styles specific to RSVP page */
.ginv-greet {
  font-size: 14px;
  line-height: 1.65;
  white-space: pre-wrap;
  margin-bottom: 22px;
}

.ginv-sec-h {
  font-family: var(--gms-font-display);
  font-size: 23px;
  margin: 22px 0 6px;
}

.ginv-sec-sub {
  font-size: 13px;
  color: var(--gms-text-3);
  margin-bottom: 16px;
}

.rsvp-top {
  display: flex;
  align-items: center;
  gap: 8px;
  margin-bottom: 6px;
}

.mc-stage {
  font-size: 10px;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 0.5px;
  color: var(--gms-maroon);
  background: rgba(138, 31, 61, 0.1);
  padding: 3px 8px;
  border-radius: 6px;
}

.mc-stage.final {
  background: var(--gms-maroon);
  color: #fff;
}

.rsvp-teams {
  font-weight: 700;
  font-size: 15px;
  margin-bottom: 4px;
}

.fl {
  font-size: 16px;
  margin: 0 4px;
}

.vs {
  margin: 0 6px;
  font-weight: 400;
  color: var(--gms-text-3);
}

.rsvp-when {
  font-size: 12px;
  color: var(--gms-text-2);
  margin-bottom: 12px;
}

.rsvp-toggle {
  display: flex;
  gap: 8px;
}

.ginv-bar {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-top: 24px;
  padding-top: 20px;
  border-top: 1px solid var(--gms-border);
}

.btn {
  padding: 11px 20px;
  border-radius: 10px;
  font-weight: 600;
  font-size: 14px;
  border: none;
  cursor: pointer;
  transition: all 0.15s;
}

.btn.pri {
  background: var(--gms-maroon);
  color: #fff;
}

.btn.pri:hover:not(:disabled) {
  background: var(--gms-maroon-600);
}

.btn:disabled {
  opacity: 0.4;
  cursor: not-allowed;
}

.muted {
  color: var(--gms-text-3);
}

.ginv-success {
  text-align: center;
  padding: 40px 20px;
}

.ck {
  font-size: 48px;
  color: #3f7d52;
  margin-bottom: 16px;
}

.ginv-success h3 {
  font-family: var(--gms-font-display);
  font-size: 32px;
  margin-bottom: 12px;
}

.narrow {
  max-width: 400px;
  margin: 0 auto 28px;
}

.confirmed-matches {
  display: flex;
  flex-direction: column;
  gap: 10px;
  max-width: 500px;
  margin: 0 auto;
  text-align: left;
}

.conf-match {
  border: 1px solid var(--gms-border);
  border-radius: 10px;
  padding: 12px;
}

.conf-teams {
  font-weight: 600;
  font-size: 15px;
  margin: 4px 0;
}

.conf-meta {
  font-size: 12px;
  color: var(--gms-text-2);
}
</style>
