<script setup>
import { ref, watch, onMounted, onUnmounted } from 'vue'
import GmsIcon from './GmsIcon.vue'
import axios from 'axios'

const props = defineProps({
    modelValue: { type: String, default: '' },
    placeholder: { type: String, default: 'Search airport…' },
    readonly: { type: Boolean, default: false },
})

const emit = defineEmits(['update:modelValue', 'select'])

const query = ref('')
const results = ref([])
const isOpen = ref(false)
const isLoading = ref(false)
const selectedLabel = ref('')
const wrapRef = ref(null)
let debounce = null

watch(() => props.modelValue, (val) => {
    if (!val) { query.value = ''; selectedLabel.value = '' }
}, { immediate: true })

function search(q) {
    if (q.length < 2) { results.value = []; isOpen.value = false; return }
    clearTimeout(debounce)
    debounce = setTimeout(async () => {
        isLoading.value = true
        try {
            const { data } = await axios.get(route('gms.api.airports'), { params: { q } })
            results.value = data
            isOpen.value = data.length > 0
        } catch { results.value = [] }
        isLoading.value = false
    }, 200)
}

function onInput(e) {
    query.value = e.target.value
    selectedLabel.value = ''
    emit('update:modelValue', '')
    search(query.value)
}

function pick(airport) {
    emit('update:modelValue', airport.code)
    emit('select', airport)
    selectedLabel.value = `${airport.code} — ${airport.city || airport.name}`
    query.value = ''
    results.value = []
    isOpen.value = false
}

function onFocus() {
    if (selectedLabel.value) {
        query.value = ''
        selectedLabel.value = ''
        emit('update:modelValue', '')
    }
    if (query.value.length >= 2) search(query.value)
}

function handleClickOutside(e) {
    if (wrapRef.value && !wrapRef.value.contains(e.target)) {
        isOpen.value = false
    }
}

onMounted(() => document.addEventListener('mousedown', handleClickOutside))
onUnmounted(() => document.removeEventListener('mousedown', handleClickOutside))
</script>

<template>
  <div ref="wrapRef" class="ap-wrap">
    <div class="ap-input-wrap">
      <input
        v-if="!readonly"
        type="text"
        class="gms-input ap-input"
        :placeholder="placeholder"
        :value="selectedLabel || query"
        @input="onInput"
        @focus="onFocus"
      />
      <input
        v-else
        type="text"
        class="gms-input"
        :value="modelValue"
        readonly
        style="background:var(--gms-surface-2);cursor:not-allowed;"
      />
      <GmsIcon v-if="isLoading" name="loader" :size="14" class="ap-spinner" />
      <span v-else-if="modelValue && !readonly" class="ap-code">{{ modelValue }}</span>
    </div>

    <div v-if="isOpen && results.length > 0" class="ap-dropdown">
      <button
        v-for="a in results" :key="a.code"
        class="ap-option"
        @mousedown.prevent="pick(a)"
      >
        <span class="ap-opt-code">{{ a.code }}</span>
        <span class="ap-opt-info">
          <span class="ap-opt-city">{{ a.city || a.name }}</span>
          <span class="ap-opt-name">{{ a.name }}</span>
        </span>
        <span class="ap-opt-country">{{ a.country }}</span>
      </button>
    </div>
  </div>
</template>

<style scoped>
.ap-wrap { position: relative; }
.ap-input-wrap { position: relative; }
.ap-input { font-family: var(--gms-font-ui) !important; text-transform: none !important; }
.ap-spinner { position: absolute; right: 10px; top: 50%; transform: translateY(-50%); color: var(--gms-text-3); }
.ap-code {
  position: absolute; right: 10px; top: 50%; transform: translateY(-50%);
  font-family: var(--gms-font-mono); font-size: 11px; font-weight: 700;
  color: var(--gms-maroon); background: rgba(138,31,61,.06);
  padding: 2px 6px; border-radius: 4px;
}

.ap-dropdown {
  position: absolute; top: 100%; left: 0; right: 0; z-index: 50;
  background: var(--gms-surface); border: 1px solid var(--gms-border);
  border-radius: 10px; box-shadow: 0 8px 24px rgba(0,0,0,.12);
  max-height: 280px; overflow-y: auto; margin-top: 4px;
}
.ap-option {
  display: flex; align-items: center; gap: 10px; width: 100%;
  padding: 10px 14px; border: none; background: none; cursor: pointer;
  text-align: left; transition: .08s; font-size: 13px;
  border-bottom: 1px solid var(--gms-border);
}
.ap-option:last-child { border-bottom: none; }
.ap-option:hover { background: var(--gms-bg); }

.ap-opt-code {
  font-family: var(--gms-font-mono); font-weight: 700; font-size: 14px;
  color: var(--gms-maroon); min-width: 36px;
}
.ap-opt-info { flex: 1; min-width: 0; display: flex; flex-direction: column; }
.ap-opt-city { font-weight: 600; font-size: 13px; }
.ap-opt-name { font-size: 11px; color: var(--gms-text-3); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.ap-opt-country { font-size: 11px; font-weight: 600; color: var(--gms-text-3); font-family: var(--gms-font-mono); }
</style>
