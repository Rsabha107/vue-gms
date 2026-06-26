<script setup>
import { ref } from 'vue'
import GmsIcon from './GmsIcon.vue'
import axios from 'axios'

const props = defineProps({
    modelValue: { type: String, default: '' },
    label: { type: String, default: 'Upload' },
    folder: { type: String, default: 'general' },
    accept: { type: String, default: 'image/jpeg,image/png,application/pdf' },
})

const emit = defineEmits(['update:modelValue'])

const isUploading = ref(false)
const fileInput = ref(null)

function triggerPick() {
    fileInput.value?.click()
}

async function onFileChange(e) {
    const file = e.target.files[0]
    if (!file) return

    isUploading.value = true
    const formData = new FormData()
    formData.append('file', file)
    formData.append('folder', props.folder)

    try {
        const { data } = await axios.post(route('gms.api.upload-document'), formData, {
            headers: { 'Content-Type': 'multipart/form-data' },
        })
        emit('update:modelValue', data.path)
    } catch {
        console.error('Upload failed')
    }
    isUploading.value = false
    e.target.value = ''
}

function clear() {
    emit('update:modelValue', '')
}

function viewUrl(path) {
    return route('gms.api.document', { path })
}
</script>

<template>
  <div class="doc-up">
    <input ref="fileInput" type="file" :accept="accept" style="display:none;" @change="onFileChange" />

    <!-- Empty state -->
    <button v-if="!modelValue && !isUploading" type="button" class="doc-up-btn" @click="triggerPick">
      <GmsIcon name="upload" :size="14" />
      <span>{{ label }}</span>
    </button>

    <!-- Uploading -->
    <div v-else-if="isUploading" class="doc-up-loading">
      <GmsIcon name="loader" :size="14" />
      <span>Uploading…</span>
    </div>

    <!-- Uploaded -->
    <div v-else class="doc-up-done">
      <a :href="viewUrl(modelValue)" target="_blank" class="doc-up-preview" title="View file">
        <img v-if="modelValue.match(/\.(jpg|jpeg|png)$/i)" :src="viewUrl(modelValue)" class="doc-up-thumb" />
        <span v-else class="doc-up-file-icon">📄</span>
      </a>
      <span class="doc-up-ok"><GmsIcon name="check" :size="11" /> Uploaded</span>
      <div class="doc-up-acts">
        <button type="button" class="doc-up-act" title="Replace" @click="triggerPick"><GmsIcon name="upload" :size="12" /></button>
        <button type="button" class="doc-up-act" title="Remove" @click="clear"><GmsIcon name="x" :size="12" /></button>
      </div>
    </div>
  </div>
</template>

<style scoped>
.doc-up { width: 100%; }
.doc-up-btn {
  display: flex; align-items: center; gap: 6px; width: 100%;
  padding: 10px 14px; border: 1px dashed var(--gms-border); border-radius: 8px;
  background: none; cursor: pointer; font-size: 12.5px; color: var(--gms-text-3);
  transition: .12s;
}
.doc-up-btn:hover { border-color: var(--gms-maroon); color: var(--gms-maroon); background: rgba(138,31,61,.03); }

.doc-up-loading {
  display: flex; align-items: center; gap: 6px;
  padding: 10px 14px; border: 1px solid var(--gms-border); border-radius: 8px;
  font-size: 12.5px; color: var(--gms-text-3);
}

.doc-up-done {
  display: flex; align-items: center; gap: 8px;
  padding: 6px 8px; border: 1px solid var(--gms-border); border-radius: 8px;
  background: var(--gms-bg);
}
.doc-up-preview {
  display: flex; align-items: center; text-decoration: none; flex-shrink: 0;
}
.doc-up-thumb { width: 32px; height: 32px; border-radius: 5px; object-fit: cover; }
.doc-up-file-icon { font-size: 20px; }
.doc-up-ok { font-size: 11px; font-weight: 600; color: #16a34a; display: flex; align-items: center; gap: 3px; }
.doc-up-acts { display: flex; gap: 2px; margin-left: auto; flex-shrink: 0; }
.doc-up-act {
  background: none; border: none; cursor: pointer; padding: 4px;
  color: var(--gms-text-3); border-radius: 4px; transition: .1s;
}
.doc-up-act:hover { color: var(--gms-text); background: var(--gms-border); }
</style>
