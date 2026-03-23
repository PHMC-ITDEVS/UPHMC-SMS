<template>
    <div class="gs-root" :class="{ 'gs-root--open': isOpen, 'gs-root--loading': isLoading }">
        <!-- Trigger -->
        <button class="gs-trigger" @click="open" aria-label="Open search">
            <div class="d-flex flex-row gap-1 align-items-center">
                <svg class="gs-trigger__icon" viewBox="0 0 20 20" fill="none">
                    <circle cx="8.5" cy="8.5" r="5.5" stroke="currentColor" stroke-width="1.5"/>
                    <path d="M13 13l3.5 3.5" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
                </svg>
                <span class="gs-trigger__label">Search</span>
            </div>
            
            <kbd class="gs-trigger__kbd">⌘K</kbd>
        </button>

        <Teleport to="body">
        <div v-if="isOpen" class="gs-overlay">
            <!-- Backdrop -->
            <div class="gs-backdrop" @click="close" />

            <!-- Modal -->
            <Transition name="gs-modal">
            <div class="gs-modal" role="dialog" aria-modal="true" aria-label="Global search">
                <!-- Search input -->
                <div class="gs-input-wrap">
                    <svg class="gs-input-wrap__icon" viewBox="0 0 20 20" fill="none">
                        <circle cx="8.5" cy="8.5" r="5.5" stroke="currentColor" stroke-width="1.5"/>
                        <path d="M13 13l3.5 3.5" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
                    </svg>
                    <input
                        ref="inputRef"
                        v-model="query"
                        class="gs-input"
                        type="text"
                        placeholder="Search everything..."
                        autocomplete="off"
                        spellcheck="false"
                        @keydown="onKeydown"
                        @input="onInput"
                    />
                    <Transition name="gs-fade">
                        <div v-if="isLoading" class="gs-spinner" aria-hidden="true">
                            <span /><span /><span />
                        </div>
                        <button v-else-if="query" class="gs-clear" @click="clearQuery" aria-label="Clear search">
                            <svg viewBox="0 0 16 16" fill="none">
                                <path d="M4 4l8 8M12 4l-8 8" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
                            </svg>
                        </button>
                    </Transition>
                </div>

                <!-- Results -->
                <div class="gs-body" ref="bodyRef">
                    <template v-if="hasResults">
                        <template v-for="section in visibleSections" :key="section.key">
                            <div class="gs-section">
                                <div class="gs-section__header">
                                    <component :is="section.icon" class="gs-section__icon" />
                                    <span>{{ section.label }}</span>
                                    <span class="gs-section__count">{{ section.items.length }}</span>
                                </div>
                                <ul class="gs-section__list" role="listbox">
                                    <li
                                        v-for="(item, idx) in section.items"
                                        :key="item.id"
                                        class="gs-item"
                                        :class="{ 'gs-item--active': isFocused(section.key, idx) }"
                                        role="option"
                                        :aria-selected="isFocused(section.key, idx)"
                                        @mouseenter="setFocus(section.key, idx)"
                                        @click="navigate(item.url)"
                                    >
                                        <div class="gs-item__body">
                                            <span class="gs-item__title" v-html="highlight(item.title)" />
                                            <span v-if="item.subtitle" class="gs-item__subtitle">{{ item.subtitle }}</span>
                                        </div>
                                        <svg class="gs-item__arrow" viewBox="0 0 16 16" fill="none">
                                            <path d="M6 4l4 4-4 4" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                                        </svg>
                                    </li>
                                </ul>
                            </div>
                        </template>
                    </template>

                    <!-- Empty state -->
                    <div v-else-if="query && !isLoading" class="gs-empty">
                        <svg viewBox="0 0 48 48" fill="none">
                            <circle cx="21" cy="21" r="13" stroke="currentColor" stroke-width="1.5"/>
                            <path d="M31 31l8 8" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
                            <path d="M17 21h8M21 17v8" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
                        </svg>
                        <p>No results for <strong>"{{ query }}"</strong></p>
                    </div>

                    <!-- Idle state -->
                    <div v-else-if="!query" class="gs-idle">
                        <div class="gs-idle__grid">
                            <div v-for="hint in idleHints" :key="hint.label" class="gs-idle__chip">
                                <component :is="hint.icon" />
                                <span>{{ hint.label }}</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Footer -->
                <div class="gs-footer">
                    <span><kbd>↑</kbd><kbd>↓</kbd> navigate</span>
                    <span><kbd>↵</kbd> open</span>
                    <span><kbd>Esc</kbd> close</span>
                </div>
            </div>
            </Transition>
        </div>
        </Teleport>
    </div>
</template>

<script>
import axios from 'axios'
import { defineComponent, ref, computed, watch, nextTick, onMounted, onBeforeUnmount, h } from 'vue'

// ── Inline SVG icon components ────────────────────────────────────────────────
const icons = {
    accounts:     (props) => h('svg', { viewBox: '0 0 16 16', fill: 'none', ...props }, [
        h('circle', { cx: 8, cy: 5, r: 2.5, stroke: 'currentColor', 'stroke-width': 1.5 }),
        h('path', { d: 'M2.5 13.5c0-3 2.5-5 5.5-5s5.5 2 5.5 5', stroke: 'currentColor', 'stroke-width': 1.5, 'stroke-linecap': 'round' }),
    ]),
    roles:        (props) => h('svg', { viewBox: '0 0 16 16', fill: 'none', ...props }, [
        h('rect', { x: 2, y: 3, width: 12, height: 10, rx: 2, stroke: 'currentColor', 'stroke-width': 1.5 }),
        h('path', { d: 'M5 7h6M5 10h4', stroke: 'currentColor', 'stroke-width': 1.5, 'stroke-linecap': 'round' }),
    ]),
    departments:  (props) => h('svg', { viewBox: '0 0 16 16', fill: 'none', ...props }, [
        h('path', { d: 'M2 13V7l6-4 6 4v6', stroke: 'currentColor', 'stroke-width': 1.5, 'stroke-linejoin': 'round' }),
        h('rect', { x: 6, y: 9, width: 4, height: 4, stroke: 'currentColor', 'stroke-width': 1.5 }),
    ]),
    positions:    (props) => h('svg', { viewBox: '0 0 16 16', fill: 'none', ...props }, [
        h('rect', { x: 5, y: 2, width: 6, height: 5, rx: 1, stroke: 'currentColor', 'stroke-width': 1.5 }),
        h('path', { d: 'M3 14v-2a2 2 0 012-2h6a2 2 0 012 2v2', stroke: 'currentColor', 'stroke-width': 1.5, 'stroke-linecap': 'round' }),
    ]),
    contacts:     (props) => h('svg', { viewBox: '0 0 16 16', fill: 'none', ...props }, [
        h('rect', { x: 2, y: 1, width: 12, height: 14, rx: 2, stroke: 'currentColor', 'stroke-width': 1.5 }),
        h('circle', { cx: 8, cy: 6, r: 2, stroke: 'currentColor', 'stroke-width': 1.5 }),
        h('path', { d: 'M4.5 13c0-2 1.5-3 3.5-3s3.5 1 3.5 3', stroke: 'currentColor', 'stroke-width': 1.5, 'stroke-linecap': 'round' }),
    ]),
    groups:       (props) => h('svg', { viewBox: '0 0 16 16', fill: 'none', ...props }, [
        h('circle', { cx: 5.5, cy: 5, r: 2, stroke: 'currentColor', 'stroke-width': 1.5 }),
        h('circle', { cx: 10.5, cy: 5, r: 2, stroke: 'currentColor', 'stroke-width': 1.5 }),
        h('path', { d: 'M1 13c0-2 2-3 4.5-3M15 13c0-2-2-3-4.5-3M5.5 13c0-2 1.3-3 3-3s3 1 3 3', stroke: 'currentColor', 'stroke-width': 1.5, 'stroke-linecap': 'round' }),
    ]),
    sms:          (props) => h('svg', { viewBox: '0 0 16 16', fill: 'none', ...props }, [
        h('path', { d: 'M2 2h12a1 1 0 011 1v8a1 1 0 01-1 1H5l-3 2V3a1 1 0 011-1z', stroke: 'currentColor', 'stroke-width': 1.5, 'stroke-linejoin': 'round' }),
    ]),
    audit_trails: (props) => h('svg', { viewBox: '0 0 16 16', fill: 'none', ...props }, [
        h('path', { d: 'M8 2v4l2.5 2.5', stroke: 'currentColor', 'stroke-width': 1.5, 'stroke-linecap': 'round' }),
        h('circle', { cx: 8, cy: 8, r: 6, stroke: 'currentColor', 'stroke-width': 1.5 }),
    ]),
}

const SECTION_META = [
    { key: 'accounts',     label: 'Accounts',    icon: icons.accounts     },
    { key: 'roles',        label: 'Roles',        icon: icons.roles        },
    { key: 'departments',  label: 'Departments',  icon: icons.departments  },
    { key: 'positions',    label: 'Positions',    icon: icons.positions    },
    { key: 'contacts',     label: 'Contacts',     icon: icons.contacts     },
    { key: 'groups',       label: 'Groups',       icon: icons.groups       },
    { key: 'sms',          label: 'SMS',          icon: icons.sms          },
    { key: 'audit_trails', label: 'Audit Trail',  icon: icons.audit_trails },
]

export default defineComponent({
    name: 'GlobalSearch',

    setup() {
        const isOpen    = ref(false)
        const query     = ref('')
        const results   = ref({})
        const isLoading = ref(false)
        const inputRef  = ref(null)
        const bodyRef   = ref(null)

        // Keyboard focus tracking: { sectionKey, itemIndex }
        const focused = ref(null)

        let debounceTimer = null
        let lastQuery     = ''

        // ── Sections ────────────────────────────────────────────────────────
        const visibleSections = computed(() =>
            SECTION_META
                .map(meta => ({ ...meta, items: results.value[meta.key] || [] }))
                .filter(s => s.items.length > 0)
        )

        const hasResults = computed(() => visibleSections.value.length > 0)

        // Flat list of all [sectionKey, itemIndex] pairs for keyboard nav
        const flatItems = computed(() => {
            const list = []
            for (const s of visibleSections.value) {
                for (let i = 0; i < s.items.length; i++) {
                    list.push({ sectionKey: s.key, itemIndex: i, item: s.items[i] })
                }
            }
            return list
        })

        // ── Idle hints ──────────────────────────────────────────────────────
        const idleHints = [
            { label: 'Accounts',   icon: icons.accounts     },
            { label: 'Contacts',   icon: icons.contacts     },
            { label: 'SMS',        icon: icons.sms          },
            { label: 'Roles',      icon: icons.roles        },
            { label: 'Audit Trail',icon: icons.audit_trails },
            { label: 'Departments',icon: icons.departments  },
        ]

        // ── Fetch ────────────────────────────────────────────────────────────
        async function fetchResults(q) {
            if (!q.trim()) { results.value = {}; return }
            isLoading.value = true
            try {
                const { data } = await axios.get(route('search.autocomplete'), { params: { q, limit: 5 } })
                if (q === lastQuery) results.value = data || {}
            } catch (e) {
                console.error('[global-search]', e)
                results.value = {}
            } finally {
                isLoading.value = false
            }
        }

        function onInput() {
            focused.value = null
            clearTimeout(debounceTimer)
            lastQuery = query.value
            debounceTimer = setTimeout(() => fetchResults(query.value), 250)
        }

        // ── Open / close ─────────────────────────────────────────────────────
        function open() {
            isOpen.value = true
            nextTick(() => inputRef.value?.focus())
        }

        function close() {
            isOpen.value = false
            query.value  = ''
            results.value = {}
            focused.value = null
        }

        function clearQuery() {
            query.value   = ''
            results.value = {}
            focused.value = null
            inputRef.value?.focus()
        }

        // ── Keyboard navigation ───────────────────────────────────────────────
        function onKeydown(e) {
            if (e.key === 'Escape') { close(); return }
            if (!flatItems.value.length) return

            const currentIdx = focused.value === null ? -1
                : flatItems.value.findIndex(f => f.sectionKey === focused.value.sectionKey && f.itemIndex === focused.value.itemIndex)

            if (e.key === 'ArrowDown') {
                e.preventDefault()
                const next = (currentIdx + 1) % flatItems.value.length
                focused.value = { sectionKey: flatItems.value[next].sectionKey, itemIndex: flatItems.value[next].itemIndex }
                scrollFocusedIntoView()
            } else if (e.key === 'ArrowUp') {
                e.preventDefault()
                const prev = currentIdx <= 0 ? flatItems.value.length - 1 : currentIdx - 1
                focused.value = { sectionKey: flatItems.value[prev].sectionKey, itemIndex: flatItems.value[prev].itemIndex }
                scrollFocusedIntoView()
            } else if (e.key === 'Enter' && focused.value !== null) {
                const f = flatItems.value[currentIdx]
                if (f) navigate(f.item.url)
            }
        }

        function scrollFocusedIntoView() {
            nextTick(() => {
                bodyRef.value?.querySelector('.gs-item--active')?.scrollIntoView({ block: 'nearest' })
            })
        }

        function isFocused(sectionKey, idx) {
            return focused.value?.sectionKey === sectionKey && focused.value?.itemIndex === idx
        }

        function setFocus(sectionKey, idx) {
            focused.value = { sectionKey, itemIndex: idx }
        }

        function navigate(url) {
            if (url) window.location.assign(url)
        }

        // ── Highlight matched text ─────────────────────────────────────────
        function highlight(text) {
            if (!query.value.trim()) return text
            const escaped = query.value.replace(/[.*+?^${}()|[\]\\]/g, '\\$&')
            return text.replace(new RegExp(`(${escaped})`, 'gi'), '<mark>$1</mark>')
        }

        // ── Global shortcut (⌘K / Ctrl+K) ────────────────────────────────
        function onGlobalKey(e) {
            if ((e.metaKey || e.ctrlKey) && e.key === 'k') {
                e.preventDefault()
                isOpen.value ? close() : open()
            }
        }

        onMounted(() => window.addEventListener('keydown', onGlobalKey))
        onBeforeUnmount(() => window.removeEventListener('keydown', onGlobalKey))

        return {
            isOpen, query, results, isLoading, inputRef, bodyRef,
            focused, visibleSections, hasResults, idleHints,
            open, close, clearQuery, onInput, onKeydown,
            isFocused, setFocus, navigate, highlight,
        }
    },
})
</script>

<style>
/* ── Tokens ─────────────────────────────────────────────────────────────── */
:root {
    --gs-bg:         #f5f6f8;
    --gs-surface:    #ffffff;
    --gs-border:     rgba(0,0,0,0.08);
    --gs-accent:     #2563eb;
    --gs-accent-dim: rgba(37,99,235,0.07);
    --gs-text:       #111827;
    --gs-muted:      #9ca3af;
    --gs-mark-bg:    rgba(37,99,235,0.12);
    --gs-mark-color: #1d4ed8;
    --gs-radius:     14px;
    --gs-radius-sm:  8px;
    --gs-shadow:     0 24px 60px rgba(0,0,0,0.12), 0 0 0 1px rgba(0,0,0,0.06);
    font-family: 'DM Sans', 'Helvetica Neue', sans-serif;
}

/* ── Trigger button ─────────────────────────────────────────────────────── */
.gs-trigger {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 7px 14px;
    background: #f1f3f6;
    border: 1px solid var(--gs-border);
    border-radius: 999px;
    color: var(--gs-muted);
    font-size: 13.5px;
    cursor: pointer;
    transition: background 0.15s, border-color 0.15s, color 0.15s;
    white-space: nowrap;
    justify-content: space-between;
    width: 100%;
}
.gs-trigger:hover {
    background: #e8ebf0;
    border-color: rgba(0,0,0,0.12);
    color: var(--gs-text);
}
.gs-trigger__icon { width: 15px; height: 15px; flex-shrink: 0; }
.gs-trigger__kbd {
    margin-left: 4px;
    padding: 1px 6px;
    background: #fff;
    border: 1px solid rgba(0,0,0,0.1);
    border-radius: 5px;
    font-size: 11px;
    font-family: inherit;
    letter-spacing: 0.02em;
    color: var(--gs-muted);
}

/* ── Overlay (backdrop + centering container) ───────────────────────────── */
.gs-overlay {
    position: fixed !important;
    inset: 0 !important;
    z-index: 1010 !important;
    display: flex !important;
    align-items: flex-start !important;
    justify-content: center !important;
    padding-top: 12vh !important;
    box-sizing: border-box !important;
}

/* ── Backdrop ───────────────────────────────────────────────────────────── */
.gs-backdrop {
    position: absolute !important;
    inset: 0 !important;
    background: rgba(0,0,0,0.25) !important;
    backdrop-filter: blur(3px) !important;
    z-index: 0 !important;
}

/* ── Modal ──────────────────────────────────────────────────────────────── */
.gs-modal {
    position: relative !important;
    z-index: 1 !important;
    width: min(660px, calc(100vw - 32px)) !important;
    max-height: 76vh !important;
    background: var(--gs-surface) !important;
    border: 1px solid var(--gs-border) !important;
    border-radius: var(--gs-radius) !important;
    box-shadow: var(--gs-shadow) !important;
    overflow: hidden !important;
    display: flex !important;
    flex-direction: column !important;
    flex-shrink: 0 !important;
}
.gs-modal-enter-active { transition: opacity 0.18s, transform 0.18s cubic-bezier(0.34,1.56,0.64,1); }
.gs-modal-leave-active { transition: opacity 0.14s, transform 0.14s ease-in; }
.gs-modal-enter-from  { opacity: 0; transform: translateY(-12px) scale(0.97); }
.gs-modal-leave-to    { opacity: 0; transform: translateY(-6px) scale(0.98); }

/* ── Input row ──────────────────────────────────────────────────────────── */
.gs-input-wrap {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 14px 18px;
    border-bottom: 1px solid var(--gs-border);
    flex-shrink: 0;
}
.gs-input-wrap__icon { width: 18px; height: 18px; color: var(--gs-muted); flex-shrink: 0; }
.gs-input {
    flex: 1;
    background: none;
    border: none;
    outline: none;
    color: var(--gs-text);
    font-size: 15px;
    font-family: inherit;
    caret-color: var(--gs-accent);
}
.gs-input::placeholder { color: var(--gs-muted); }

.gs-clear {
    width: 26px; height: 26px;
    border: none; background: rgba(0,0,0,0.06);
    border-radius: 50%;
    color: var(--gs-muted);
    cursor: pointer;
    display: grid; place-items: center;
    transition: background 0.15s, color 0.15s;
    flex-shrink: 0;
}
.gs-clear:hover { background: rgba(0,0,0,0.1); color: var(--gs-text); }
.gs-clear svg { width: 12px; height: 12px; }

/* ── Spinner ─────────────────────────────────────────────────────────────── */
.gs-spinner {
    display: flex; gap: 4px; align-items: center;
    flex-shrink: 0;
}
.gs-spinner span {
    width: 5px; height: 5px;
    border-radius: 50%;
    background: var(--gs-accent);
    animation: gs-bounce 0.8s ease-in-out infinite;
}
.gs-spinner span:nth-child(2) { animation-delay: 0.15s; }
.gs-spinner span:nth-child(3) { animation-delay: 0.3s; }
@keyframes gs-bounce {
    0%, 80%, 100% { transform: scale(0.6); opacity: 0.4; }
    40%            { transform: scale(1);   opacity: 1;   }
}

.gs-fade-enter-active, .gs-fade-leave-active { transition: opacity 0.15s; }
.gs-fade-enter-from, .gs-fade-leave-to { opacity: 0; }

/* ── Body ────────────────────────────────────────────────────────────────── */
.gs-body {
    overflow-y: auto;
    overscroll-behavior: contain;
    flex: 1;
    padding: 8px 0;
    scrollbar-width: thin;
    scrollbar-color: rgba(0,0,0,0.08) transparent;
}
.gs-body::-webkit-scrollbar { width: 4px; }
.gs-body::-webkit-scrollbar-thumb { background: rgba(0,0,0,0.08); border-radius: 2px; }

/* ── Section ─────────────────────────────────────────────────────────────── */
.gs-section { padding: 4px 0; }
.gs-section + .gs-section {
    border-top: 1px solid var(--gs-border);
    margin-top: 4px;
    padding-top: 8px;
}
.gs-section__header {
    display: flex;
    align-items: center;
    gap: 6px;
    padding: 4px 18px 6px;
    color: var(--gs-muted);
    font-size: 11px;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.08em;
}
.gs-section__icon { width: 13px; height: 13px; flex-shrink: 0; }
.gs-section__count {
    margin-left: auto;
    background: rgba(0,0,0,0.06);
    padding: 1px 6px;
    border-radius: 999px;
    font-size: 10px;
}
.gs-section__list { list-style: none; margin: 0; padding: 0; }

/* ── Item ────────────────────────────────────────────────────────────────── */
.gs-item {
    display: flex;
    align-items: center;
    padding: 9px 18px;
    cursor: pointer;
    transition: background 0.1s;
    gap: 10px;
}
.gs-item--active,
.gs-item:hover {
    background: var(--gs-accent-dim);
}
.gs-item--active .gs-item__arrow,
.gs-item:hover .gs-item__arrow {
    opacity: 1;
    transform: translateX(0);
}
.gs-item__body { flex: 1; min-width: 0; }
.gs-item__title {
    display: block;
    font-size: 13.5px;
    font-weight: 500;
    color: var(--gs-text);
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}
.gs-item__title :deep(mark) {
    background: var(--gs-mark-bg);
    color: var(--gs-mark-color);
    border-radius: 3px;
    padding: 0 2px;
}
.gs-item__subtitle {
    display: block;
    font-size: 11.5px;
    color: var(--gs-muted);
    margin-top: 1px;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}
.gs-item__arrow {
    width: 14px; height: 14px;
    color: var(--gs-accent);
    opacity: 0;
    transform: translateX(-4px);
    transition: opacity 0.15s, transform 0.15s;
    flex-shrink: 0;
}

/* ── Empty / idle ────────────────────────────────────────────────────────── */
.gs-empty {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 12px;
    padding: 48px 24px;
    color: var(--gs-muted);
    text-align: center;
}
.gs-empty svg { width: 44px; height: 44px; opacity: 0.3; }
.gs-empty p { font-size: 14px; margin: 0; }
.gs-empty strong { color: var(--gs-text); }

.gs-idle { padding: 20px 18px; }
.gs-idle__grid {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
}
.gs-idle__chip {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 6px 12px;
    background: #f1f3f6;
    border: 1px solid var(--gs-border);
    border-radius: 999px;
    font-size: 12px;
    color: var(--gs-muted);
    cursor: default;
}
.gs-idle__chip svg { width: 13px; height: 13px; }

/* ── Footer ──────────────────────────────────────────────────────────────── */
.gs-footer {
    border-top: 1px solid var(--gs-border);
    padding: 10px 18px;
    display: flex;
    gap: 18px;
    color: var(--gs-muted);
    font-size: 11px;
    flex-shrink: 0;
}
.gs-footer kbd {
    display: inline-block;
    padding: 1px 5px;
    background: #f1f3f6;
    border: 1px solid rgba(0,0,0,0.1);
    border-radius: 4px;
    font-family: inherit;
    font-size: 10px;
    margin-right: 3px;
    color: var(--gs-muted);
}
</style>