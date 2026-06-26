<script setup>
defineProps({
    open: Boolean,
    title: { type: String, default: '' },
    subtitle: { type: String, default: '' },
})
defineEmits(['close'])
</script>

<template>
  <Teleport to="body">
    <template v-if="open">
      <div class="pwa-sheet-scrim" @click="$emit('close')" />
      <div class="pwa-sheet">
        <div class="pwa-sheet-grab" />
        <div class="pwa-sheet-head">
          <div>
            <div class="pwa-sheet-title">{{ title }}</div>
            <div v-if="subtitle" class="pwa-sheet-sub">{{ subtitle }}</div>
          </div>
          <button class="pwa-sheet-x" @click="$emit('close')">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
          </button>
        </div>
        <div class="pwa-sheet-body">
          <slot />
        </div>
        <div v-if="$slots.footer" class="pwa-sheet-foot">
          <slot name="footer" />
        </div>
      </div>
    </template>
  </Teleport>
</template>

<style>
.pwa-sheet-scrim { position: fixed; inset: 0; background: rgba(38,34,30,.42); backdrop-filter: blur(2px); z-index: 70; animation: pwa-fade .2s; }
@keyframes pwa-fade { from { opacity: 0; } to { opacity: 1; } }
.pwa-sheet {
  position: fixed; left: 0; right: 0; bottom: 0; z-index: 71; background: var(--pwa-canvas, #f6f1e9);
  border-radius: 26px 26px 0 0; max-height: 90%; display: flex; flex-direction: column;
  box-shadow: 0 -20px 50px -16px rgba(38,34,30,.4); animation: pwa-sheetIn .36s cubic-bezier(.2,.85,.25,1);
}
@keyframes pwa-sheetIn { from { transform: translateY(100%); } to { transform: none; } }
.pwa-sheet-grab { width: 40px; height: 5px; border-radius: 5px; background: var(--pwa-line-2, #ddd4c2); margin: 10px auto 4px; flex: none; }
.pwa-sheet-head { display: flex; align-items: flex-start; gap: 12px; padding: 8px 18px 14px; }
.pwa-sheet-title { font-family: 'Instrument Serif', Georgia, serif; font-size: 24px; line-height: 1.05; }
.pwa-sheet-sub { font-size: 12.5px; color: var(--pwa-ink-2, #6c665c); margin-top: 3px; }
.pwa-sheet-x {
  margin-left: auto; width: 32px; height: 32px; border-radius: 10px; background: var(--pwa-surface, #fff);
  border: 1px solid var(--pwa-line, #ebe4d6); display: grid; place-items: center; color: var(--pwa-ink-2, #6c665c);
  flex: none; cursor: pointer;
}
.pwa-sheet-x svg { width: 16px; height: 16px; }
.pwa-sheet-body { flex: 1; overflow-y: auto; padding: 0 18px 18px; }
.pwa-sheet-foot {
  padding: 14px 18px calc(env(safe-area-inset-bottom, 0px) + 18px); border-top: 1px solid var(--pwa-line, #ebe4d6);
  display: flex; gap: 10px; background: var(--pwa-surface, #fff);
}
</style>
