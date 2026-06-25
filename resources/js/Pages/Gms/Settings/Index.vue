<script setup>
import { ref, inject, computed, watch } from 'vue'
import { useForm } from '@inertiajs/vue3'
import GmsLayout from '@/Layouts/GmsLayout.vue'
import GmsIcon from '@/Components/Gms/GmsIcon.vue'
import GmsBtn from '@/Components/Gms/GmsBtn.vue'
import GmsAvatar from '@/Components/Gms/GmsAvatar.vue'

defineOptions({ layout: GmsLayout })

const props = defineProps({
    user: { type: Object, default: () => ({}) },
    event: { type: Object, default: () => ({}) },
    teamUsers: { type: Array, default: () => ([]) },
    emailTemplates: { type: Array, default: () => ([]) },
    portalSettings: { type: Object, default: () => ({ enabled: false, authMode: 'signed_url' }) },
})

const toast = inject('toast')

const activeSection = ref('team')

// Account / profile
const profileName = ref('Layla Hassan')
const profileEmail = ref('layla.hassan@protocol.qa')
const profileRole = ref('administrator')
const avatarColor = ref('#8a1f3d')

const avatarColors = [
    '#8a1f3d', '#3B82F6', '#8B5CF6', '#EC4899', '#F59E0B',
    '#10B981', '#06B6D4', '#6366F1', '#EF4444', '#F97316',
]

// Email templates
const localEmailTemplates = ref(props.emailTemplates.map((t, idx) => ({ 
    ...t, 
    active: idx === 0 
})))
const activeTemplate = ref(localEmailTemplates.value.find(t => t.active) || localEmailTemplates.value[0])
const templateName = ref(activeTemplate.value?.name || '')
const templateSubject = ref(activeTemplate.value?.subject || '')
const templateBody = ref(activeTemplate.value?.body || '')
const templateTags = ['guest_name', 'guest_title', 'event_name', 'event_date', 'venue', 'tier_name', 'rsvp_deadline', 'flight_details', 'accommodation_details', 'transport_details']
const bodyTextarea = ref(null)

// Forms for template operations
const saveForm = useForm({
    name: '',
    subject: '',
    body: ''
})
const createForm = useForm({
    name: 'New Template',
    subject: 'Welcome to {{ event_name }}',
    body: 'Dear {{ guest_name }},\n\nWe are pleased to invite you to {{ event_name }}.\n\nBest regards'
})
const deleteForm = useForm({})
const isSendingTest = ref(false)

// Watch for changes to email templates from server (after create/delete)
watch(() => props.emailTemplates, (newTemplates) => {
    localEmailTemplates.value = newTemplates.map((t, idx) => ({ 
        ...t, 
        active: idx === 0 
    }))
    activeTemplate.value = localEmailTemplates.value[0]
    if (activeTemplate.value) {
        templateName.value = activeTemplate.value.name
        templateSubject.value = activeTemplate.value.subject
        templateBody.value = activeTemplate.value.body
    }
}, { deep: true })

// Team members (from database)
const roleLabels = { admin: 'Admin', coordinator: 'Coordinator', protocol: 'Protocol', viewer: 'Viewer' }
const teamSearch = ref('')

const filteredTeamUsers = computed(() => {
    if (!teamSearch.value) return props.teamUsers
    const query = teamSearch.value.toLowerCase()
    return props.teamUsers.filter(member => 
        member.name.toLowerCase().includes(query) ||
        member.email.toLowerCase().includes(query)
    )
})

// Role permissions matrix
const capabilities = [
    { label: 'Manage guests',    admin: true,  coordinator: true,  protocol: true,  viewer: false },
    { label: 'Send invitations', admin: true,  coordinator: true,  protocol: true,  viewer: false },
    { label: 'Edit seating',     admin: true,  coordinator: true,  protocol: false, viewer: false },
    { label: 'Manage modules',   admin: true,  coordinator: false, protocol: false, viewer: false },
    { label: 'Manage team',      admin: true,  coordinator: false, protocol: false, viewer: false },
    { label: 'Export data',      admin: true,  coordinator: true,  protocol: false, viewer: false },
]

// Branding
const primaryColor = ref('#8a1f3d')
const brandColors = [
    { name: 'Maroon',  hex: '#8a1f3d' },
    { name: 'Blue',    hex: '#3B82F6' },
    { name: 'Pink',    hex: '#EC4899' },
    { name: 'Green',   hex: '#10B981' },
    { name: 'Purple',  hex: '#8B5CF6' },
    { name: 'Orange',  hex: '#F97316' },
]

// General / notifications
const notificationsEnabled = ref(true)
const emailReminders = ref(true)
const autoAssignSeats = ref(false)

// Guest Portal
const guestPortalEnabled = ref(props.portalSettings.enabled)
const portalAuthMode = ref(props.portalSettings.authMode)

const portalForm = useForm({
    enabled: props.portalSettings.enabled,
    authMode: props.portalSettings.authMode,
})

function togglePortal() {
    guestPortalEnabled.value = !guestPortalEnabled.value
    savePortalSettings()
}

function savePortalSettings() {
    portalForm.enabled = guestPortalEnabled.value
    portalForm.authMode = portalAuthMode.value
    
    portalForm.post(route('gms.settings.portal'), {
        preserveScroll: true,
        onSuccess: () => {
            toast('Portal settings saved')
        },
        onError: () => {
            toast('Failed to save portal settings', 'error')
        }
    })
}

function selectTemplate(template) {
    activeTemplate.value = template
    localEmailTemplates.value.forEach(t => t.active = false)
    template.active = true
    templateName.value = template.name
    templateSubject.value = template.subject
    templateBody.value = template.body
}

function insertTag(tag) {
    const el = bodyTextarea.value
    const insert = `{{${tag}}}`
    if (!el) { templateBody.value += insert; return }
    const start = el.selectionStart
    const end = el.selectionEnd
    templateBody.value = templateBody.value.slice(0, start) + insert + templateBody.value.slice(end)
    // restore cursor after the inserted tag
    requestAnimationFrame(() => {
        el.selectionStart = el.selectionEnd = start + insert.length
        el.focus()
    })
}

function createNewTemplate() {
    createForm.post(route('gms.email-templates.store'), {
        onSuccess: () => {
            toast('New template created')
            // Reset form for next use
            createForm.reset()
        },
        onError: () => {
            toast('Failed to create template', 'error')
        }
    })
}

function deleteTemplate() {
    if (localEmailTemplates.value.length <= 1) {
        toast('Cannot delete the last template', 'error')
        return
    }
    
    deleteForm.delete(route('gms.email-templates.destroy', activeTemplate.value.id), {
        onSuccess: () => {
            toast('Template deleted')
        },
        onError: () => {
            toast('Failed to delete template', 'error')
        }
    })
}

function saveTemplate() {
    if (!templateName.value.trim()) {
        toast('Template name is required', 'error')
        return
    }
    if (!templateSubject.value.trim()) {
        toast('Subject is required', 'error')
        return
    }
    if (!templateBody.value.trim()) {
        toast('Body is required', 'error')
        return
    }
    
    // Update form data
    saveForm.name = templateName.value
    saveForm.subject = templateSubject.value
    saveForm.body = templateBody.value
    
    saveForm.put(route('gms.email-templates.update', activeTemplate.value.id), {
        preserveScroll: true,
        onSuccess: () => {
            toast('Template saved')
            // Update local state with saved values
            const templateIndex = localEmailTemplates.value.findIndex(t => t.id === activeTemplate.value.id)
            if (templateIndex !== -1) {
                localEmailTemplates.value[templateIndex].name = templateName.value
                localEmailTemplates.value[templateIndex].subject = templateSubject.value
                localEmailTemplates.value[templateIndex].body = templateBody.value
            }
        },
        onError: () => {
            toast('Failed to save template', 'error')
        }
    })
}

function sendTestEmail() {
    isSendingTest.value = true
    
    // Simulate async send operation
    setTimeout(() => {
        isSendingTest.value = false
        toast('Test email sent', 'info')
    }, 1000)
}
</script>

<template>
  <div class="gms-view">
    <div class="gms-view-header">
      <div>
        <h1 class="gms-view-title">Settings</h1>
        <p class="gms-view-subtitle">Configure {{ event?.name || "Doha Cup '26" }} and your GMS workspace.</p>
      </div>
    </div>

    <div class="set-grid">
      <!-- Left Navigation -->
      <nav class="set-nav">
        <button class="set-nav-item" :class="{ on: activeSection === 'general' }" @click="activeSection = 'general'">
          <span class="ic"><GmsIcon name="settings" :size="16" /></span>
          General
        </button>
        <button class="set-nav-item" :class="{ on: activeSection === 'modules' }" @click="activeSection = 'modules'">
          <span class="ic"><GmsIcon name="grid" :size="16" /></span>
          Modules
        </button>
        <button class="set-nav-item" :class="{ on: activeSection === 'account' }" @click="activeSection = 'account'">
          <span class="ic"><GmsIcon name="user" :size="16" /></span>
          Account & profile
        </button>
        <button class="set-nav-item" :class="{ on: activeSection === 'team' }" @click="activeSection = 'team'">
          <span class="ic"><GmsIcon name="users" :size="16" /></span>
          Users & roles
        </button>
        <button class="set-nav-item" :class="{ on: activeSection === 'notifications' }" @click="activeSection = 'notifications'">
          <span class="ic"><GmsIcon name="bell" :size="16" /></span>
          Notifications
        </button>
        <button class="set-nav-item" :class="{ on: activeSection === 'email' }" @click="activeSection = 'email'">
          <span class="ic"><GmsIcon name="mail" :size="16" /></span>
          Email templates
        </button>
        <button class="set-nav-item" :class="{ on: activeSection === 'branding' }" @click="activeSection = 'branding'">
          <span class="ic"><GmsIcon name="palette" :size="16" /></span>
          Branding
        </button>
        <button class="set-nav-item" :class="{ on: activeSection === 'export' }" @click="activeSection = 'export'">
          <span class="ic"><GmsIcon name="download" :size="16" /></span>
          Data & export
        </button>
      </nav>

      <!-- Right Content Area -->
      <div class="set-body">

        <!-- ── Team & Roles ───────────────────────────────────── -->
        <div v-if="activeSection === 'team'">

          <!-- Team members card -->
          <div class="set-panel team-panel-scrollable" style="margin-bottom: 24px;">
            <div class="set-panel-h sticky-header">
              <div style="flex: 1;">
                <h2 class="set-panel-t">Users</h2>
                <p class="set-panel-d">People with access to this GMS workspace.</p>
              </div>
              <div class="team-search-trigger">
                <GmsIcon name="search" :size="14" />
                <input 
                  v-model="teamSearch" 
                  type="text" 
                  placeholder="Search members..." 
                  class="team-search-input"
                />
              </div>
            </div>

            <div class="team-list-scrollable">
              <div class="team-list">
              <div v-for="member in filteredTeamUsers" :key="member.id" class="team-row">
                <GmsAvatar :name="member.name" size="md" />
                <div style="flex:1;min-width:0;">
                  <div class="team-nm">
                    {{ member.name }}
                    <span v-if="member.id === user?.id" class="you-tag">You</span>
                  </div>
                  <div class="team-em">{{ member.email }}</div>
                </div>
                <span class="role-pill" :class="member.role || 'viewer'">{{ roleLabels[member.role || 'viewer'] }}</span>
                <button class="gms-btn gms-btn-ghost gms-btn-sm gms-btn-icon" style="margin-left:4px;">
                  <GmsIcon name="more-vertical" :size="14" />
                </button>
              </div>
            </div>
            </div>

            <div class="set-panel-f" style="justify-content:flex-start;">
              <button class="gms-btn gms-btn-primary">
                <GmsIcon name="plus" :size="13" />
                Invite member
              </button>
            </div>
          </div>

          <!-- Role permissions card -->
          <div class="set-panel" style="overflow: visible;">
            <div class="set-panel-h">
              <h2 class="set-panel-t">Role permissions</h2>
              <p class="set-panel-d">What each role can do. Admin always has full access. Tap a cell to change.</p>
            </div>
            <div class="set-panel-b" style="overflow-x:auto; padding-bottom: 32px;">
              <table class="matrix" style="min-width:560px; margin-bottom: 0;">
                <thead>
                  <tr>
                    <th style="text-align:left;padding:14px 24px;width:42%;">Capability</th>
                    <th style="text-align:center;">Admin</th>
                    <th style="text-align:center;">Coordinator</th>
                    <th style="text-align:center;">Protocol</th>
                    <th style="text-align:center;">Viewer</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="cap in capabilities" :key="cap.label">
                    <td class="cap" style="padding:12px 24px;">{{ cap.label }}</td>
                    <td style="text-align:center;">
                      <button class="mx-cell" :class="{ on: cap.admin }">
                        <GmsIcon v-if="cap.admin" name="check" :size="12" />
                        <span v-else style="font-size:13px;line-height:1;">—</span>
                      </button>
                    </td>
                    <td style="text-align:center;">
                      <button class="mx-cell" :class="{ on: cap.coordinator }">
                        <GmsIcon v-if="cap.coordinator" name="check" :size="12" />
                        <span v-else style="font-size:13px;line-height:1;">—</span>
                      </button>
                    </td>
                    <td style="text-align:center;">
                      <button class="mx-cell" :class="{ on: cap.protocol }">
                        <GmsIcon v-if="cap.protocol" name="check" :size="12" />
                        <span v-else style="font-size:13px;line-height:1;">—</span>
                      </button>
                    </td>
                    <td style="text-align:center;">
                      <button class="mx-cell" :class="{ on: cap.viewer }">
                        <GmsIcon v-if="cap.viewer" name="check" :size="12" />
                        <span v-else style="font-size:13px;line-height:1;">—</span>
                      </button>
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>
        </div>

        <!-- ── Account & Profile ──────────────────────────────── -->
        <div v-if="activeSection === 'account'" class="set-panel">
          <div class="set-panel-h">
            <h2 class="set-panel-t">Account & profile</h2>
            <p class="set-panel-d">Update your personal information and avatar</p>
          </div>
          <div class="set-panel-b">
            <div class="prof-head">
              <GmsAvatar :name="profileName" size="xl" class="prof-av" />
              <div style="flex:1;">
                <div class="gms-field">
                  <label class="gms-label">Full Name</label>
                  <input v-model="profileName" type="text" class="gms-input" />
                </div>
                <div style="margin-top:12px;">
                  <label class="gms-label">Avatar Color</label>
                  <div class="avc-row">
                    <button
                      v-for="color in avatarColors"
                      :key="color"
                      class="avc"
                      :class="{ on: avatarColor === color }"
                      :style="{ background: color }"
                      @click="avatarColor = color"
                    ></button>
                  </div>
                </div>
              </div>
            </div>
            <div class="gms-field">
              <label class="gms-label">Email</label>
              <input v-model="profileEmail" type="email" class="gms-input" />
            </div>
            <div class="gms-field">
              <label class="gms-label">Role</label>
              <select v-model="profileRole" class="gms-input gms-select">
                <option value="administrator">Administrator</option>
                <option value="manager">Manager</option>
                <option value="coordinator">Coordinator</option>
              </select>
            </div>
          </div>
          <div class="set-panel-f">
            <button class="gms-btn gms-btn-ghost">Cancel</button>
            <button class="gms-btn gms-btn-primary" @click="toast('Profile saved')">Save Changes</button>
          </div>
        </div>

        <!-- ── General ────────────────────────────────────────── -->
        <div v-if="activeSection === 'general'" class="set-panel">
          <div class="set-panel-h">
            <h2 class="set-panel-t">General</h2>
            <p class="set-panel-d">Configure application-wide behaviour</p>
          </div>
          <div class="set-panel-b" style="padding:0;">
            <div class="set-rows">
              <div class="set-row">
                <div class="set-row-ic"><GmsIcon name="zap" :size="16" /></div>
                <div class="set-row-tx">
                  <div class="set-row-t">Auto-Assign Seats</div>
                  <div class="set-row-d">Automatically assign seats to newly confirmed guests</div>
                </div>
                <button class="sw" :class="{ on: autoAssignSeats }" @click="autoAssignSeats = !autoAssignSeats">
                  <span class="kn"></span>
                </button>
              </div>
            </div>
          </div>
          <div class="set-panel-f">
            <button class="gms-btn gms-btn-primary" @click="toast('Preferences saved')">Save Preferences</button>
          </div>
        </div>

        <!-- ── Modules ────────────────────────────────────────── -->
        <div v-if="activeSection === 'modules'" class="set-panel">
          <div class="set-panel-h">
            <h2 class="set-panel-t">Modules</h2>
            <p class="set-panel-d">Enable or disable GMS modules for this event</p>
          </div>
          <div class="set-panel-b" style="padding:0;">
            <div class="set-rows">
              <div v-for="mod in [
                { icon: 'plane',     label: 'Flights',           desc: 'Flight request management',               on: true  },
                { icon: 'building',  label: 'Accommodation',     desc: 'Hotel and accommodation requests',        on: true  },
                { icon: 'car',       label: 'Transport',         desc: 'Ground transport coordination',           on: true  },
                { icon: 'arrivals',  label: 'Arrival & Departure', desc: 'Airport and terminal handling',         on: false },
              ]" :key="mod.label" class="set-row">
                <div class="set-row-ic"><GmsIcon :name="mod.icon" :size="16" /></div>
                <div class="set-row-tx">
                  <div class="set-row-t">{{ mod.label }}</div>
                  <div class="set-row-d">{{ mod.desc }}</div>
                </div>
                <button class="sw" :class="{ on: mod.on }" @click="mod.on = !mod.on">
                  <span class="kn"></span>
                </button>
              </div>
              
              <!-- Guest Portal Toggle -->
              <div class="set-row" style="border-top: 1px solid var(--gms-border); margin-top: 16px; padding-top: 16px;">
                <div class="set-row-ic"><GmsIcon name="globe" :size="16" /></div>
                <div class="set-row-tx">
                  <div class="set-row-t">Guest Self-Service Portal</div>
                  <div class="set-row-d">Allow guests to view itineraries and submit service requests</div>
                </div>
                <button class="sw" :class="{ on: guestPortalEnabled }" :disabled="portalForm.processing" @click="togglePortal">
                  <span class="kn"></span>
                </button>
              </div>
              
              <!-- Portal Authentication Mode (nested, only if enabled) -->
              <div v-if="guestPortalEnabled" class="set-row" style="margin-left: 32px; background: var(--gms-bg); border-radius: 6px;">
                <div class="set-row-tx">
                  <div class="set-row-t" style="font-size: 13px;">Portal Authentication</div>
                  <select v-model="portalAuthMode" class="gms-input gms-select" style="margin-top: 8px; max-width: 280px;" :disabled="portalForm.processing" @change="savePortalSettings">
                    <option value="signed_url">Signed URL (Basic)</option>
                    <option value="magic_link">Magic Link (Enhanced Security)</option>
                    <option value="full_auth">Full Authentication</option>
                  </select>
                  <div style="font-size: 11px; color: var(--gms-text-3); margin-top: 6px;">
                    <span v-if="portalAuthMode === 'signed_url'">Time-limited, cryptographically signed URLs</span>
                    <span v-else-if="portalAuthMode === 'magic_link'">Email verification required for each session</span>
                    <span v-else>Password-based authentication with session management</span>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- ── Notifications ──────────────────────────────────── -->
        <div v-if="activeSection === 'notifications'" class="set-panel">
          <div class="set-panel-h">
            <h2 class="set-panel-t">Notifications</h2>
            <p class="set-panel-d">Control when and how you receive alerts</p>
          </div>
          <div class="set-panel-b" style="padding:0;">
            <div class="set-rows">
              <div class="set-row">
                <div class="set-row-ic"><GmsIcon name="bell" :size="16" /></div>
                <div class="set-row-tx">
                  <div class="set-row-t">Email Notifications</div>
                  <div class="set-row-d">Receive email alerts for guest updates</div>
                </div>
                <button class="sw" :class="{ on: notificationsEnabled }" @click="notificationsEnabled = !notificationsEnabled">
                  <span class="kn"></span>
                </button>
              </div>
              <div class="set-row">
                <div class="set-row-ic"><GmsIcon name="clock" :size="16" /></div>
                <div class="set-row-tx">
                  <div class="set-row-t">Automatic Reminders</div>
                  <div class="set-row-d">Send reminder emails 24 h before events</div>
                </div>
                <button class="sw" :class="{ on: emailReminders }" @click="emailReminders = !emailReminders">
                  <span class="kn"></span>
                </button>
              </div>
            </div>
          </div>
          <div class="set-panel-f">
            <button class="gms-btn gms-btn-primary" @click="toast('Notification preferences saved')">Save Preferences</button>
          </div>
        </div>

        <!-- ── Email Templates ────────────────────────────────── -->
        <div v-if="activeSection === 'email'" class="email-two-col">

          <!-- Left: template list card -->
          <div class="set-panel email-list-panel">
            <div class="email-sidebar-h">Templates</div>
            <div class="email-sidebar-list">
              <button
                v-for="template in localEmailTemplates"
                :key="template.id"
                class="etpl-item"
                :class="{ on: template.active }"
                @click="selectTemplate(template)"
              >
                <GmsIcon name="mail" :size="15" />
                <span class="etpl-name">{{ template.name }}</span>
                <GmsIcon v-if="template.active" name="chevron-right" :size="14" class="etpl-chev" />
              </button>
            </div>
            <button class="etpl-new" @click="createNewTemplate">
              <GmsIcon name="plus" :size="14" />
              New template
            </button>
          </div>

          <!-- Right: editor card -->
          <div class="set-panel email-editor-panel">
            <div class="email-editor-h">
              <div class="email-editor-t">Edit template</div>
              <div class="email-editor-d">Used by the invitation wizard. Merge tags resolve per-recipient when the invitation is sent.</div>
            </div>
            <div class="email-editor-b">
              <div class="gms-field">
                <label class="gms-label">Template name</label>
                <input v-model="templateName" type="text" class="gms-input" />
              </div>
              <div class="gms-field">
                <label class="gms-label">Subject</label>
                <input v-model="templateSubject" type="text" class="gms-input" />
              </div>
              <div class="gms-field">
                <label class="gms-label">Body</label>
                <textarea ref="bodyTextarea" v-model="templateBody" class="email-body" rows="10"></textarea>
              </div>
              <div class="mtag-row">
                <span class="mtag-label">Insert:</span>
                <button v-for="tag in templateTags" :key="tag" class="mtag" @click="insertTag(tag)">
                  {{ tag.replace(/_/g, ' ').replace(/\b\w/g, c => c.toUpperCase()) }}
                </button>
              </div>
            </div>
            <div class="email-footer">
              <GmsBtn 
                variant="ghost" 
                icon="trash" 
                :icon-size="13"
                :processing="deleteForm.processing"
                class="email-delete-btn"
                @click="deleteTemplate"
              >
                Delete
              </GmsBtn>
              <div style="display:flex;gap:8px;">
                <GmsBtn 
                  variant="ghost" 
                  icon="send" 
                  :icon-size="13"
                  :processing="isSendingTest"
                  @click="sendTestEmail"
                >
                  Send test
                </GmsBtn>
                <GmsBtn 
                  variant="primary" 
                  icon="check" 
                  :icon-size="13"
                  :processing="saveForm.processing"
                  @click="saveTemplate"
                >
                  Save template
                </GmsBtn>
              </div>
            </div>
          </div>

        </div>

        <!-- ── Branding ───────────────────────────────────────── -->
        <div v-if="activeSection === 'branding'" class="set-panel">
          <div class="set-panel-h">
            <h2 class="set-panel-t">Branding</h2>
            <p class="set-panel-d">Customize colors and branding elements</p>
          </div>
          <div class="set-panel-b">
            <div style="margin-bottom:24px;">
              <label class="gms-label" style="margin-bottom:12px;display:block;">Primary Color</label>
              <div class="accent-grid">
                <button
                  v-for="color in brandColors"
                  :key="color.hex"
                  class="accent"
                  :class="{ on: primaryColor === color.hex }"
                  @click="primaryColor = color.hex"
                >
                  <div class="accent-sw" :style="{ background: color.hex }">
                    <GmsIcon v-if="primaryColor === color.hex" name="check" :size="16" />
                  </div>
                  <div>
                    <div class="accent-nm">{{ color.name }}</div>
                    <div class="set-row-d">{{ color.hex }}</div>
                  </div>
                </button>
              </div>
            </div>
            <div class="brand-row">
              <div>
                <label class="gms-label">Event Logo</label>
                <p style="font-size:12px;color:var(--gms-text-3);margin-top:4px;">Emoji or single character for event branding</p>
              </div>
              <div class="gms-field" style="margin-bottom:0;">
                <input type="text" class="gms-input" value="⚽" style="text-align:center;font-size:24px;" maxlength="2" />
              </div>
            </div>
          </div>
          <div class="set-panel-f">
            <button class="gms-btn gms-btn-ghost">Reset to Default</button>
            <button class="gms-btn gms-btn-primary" @click="toast('Branding saved')">Save Changes</button>
          </div>
        </div>

        <!-- ── Data & Export ──────────────────────────────────── -->
        <div v-if="activeSection === 'export'" class="set-panel danger">
          <div class="set-panel-h">
            <h2 class="set-panel-t">Data & export</h2>
            <p class="set-panel-d">Irreversible actions that affect your data</p>
          </div>
          <div class="set-panel-b" style="padding:0;">
            <div class="set-rows">
              <div class="set-row">
                <div class="set-row-tx">
                  <div class="set-row-t">Export All Data</div>
                  <div class="set-row-d">Download a complete copy of your GMS data</div>
                </div>
                <button class="gms-btn gms-btn-ghost">
                  <GmsIcon name="download" :size="13" />
                  Export
                </button>
              </div>
              <div class="set-row">
                <div class="set-row-tx">
                  <div class="set-row-t">Clear All Guest Data</div>
                  <div class="set-row-d">Permanently delete all guest records for this event</div>
                </div>
                <button class="gms-btn gms-btn-ghost danger-btn">
                  <GmsIcon name="trash" :size="13" />
                  Clear Data
                </button>
              </div>
            </div>
          </div>
        </div>

      </div>
    </div>
  </div>
</template>
