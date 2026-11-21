<script setup>
import { computed, nextTick, onMounted, ref, useId, watch } from 'vue';
import { Search } from 'lucide-vue-next';
import { useI18n } from '@/lib/i18n';
import { clampIndex, groupEntries, searchEntries, stepIndex } from '@/lib/menuSearch';

const props = defineProps({
    modelValue: { type: Boolean, default: false },
    entries: { type: Array, default: () => [] },
});

const emit = defineEmits(['update:modelValue', 'select']);

const { t } = useI18n();

const dialog = ref(null);
const input = ref(null);
const list = ref(null);
const query = ref('');
const activeIndex = ref(0);
const baseId = useId();

const listId = `${baseId}-list`;
const optionId = (index) => `${baseId}-option-${index}`;

// Query kosong menampilkan seluruh entri (gaya command palette), bukan layar
// kosong: pengguna bisa langsung menelusuri dengan panah.
const results = computed(() =>
    query.value.trim() === '' ? props.entries : searchEntries(props.entries, query.value),
);

const sections = computed(() => {
    let index = 0;

    return groupEntries(results.value).map((section) => ({
        ...section,
        items: section.items.map((entry) => ({ entry, index: index++ })),
    }));
});

const hasResults = computed(() => results.value.length > 0);
const activeEntry = computed(() => results.value[activeIndex.value] ?? null);

const syncDialog = (open) => {
    const element = dialog.value;

    if (!element) {
        return;
    }

    if (open && !element.open) {
        element.showModal();
    }

    if (!open && element.open) {
        element.close();
    }
};

const focusInput = () => nextTick(() => input.value?.focus());

const reset = () => {
    query.value = '';
    activeIndex.value = props.entries.length > 0 ? 0 : -1;
};

const close = () => emit('update:modelValue', false);

const select = (entry) => {
    if (!entry) {
        return;
    }

    emit('select', entry);
    close();
};

// Escape ditahan lalu ditutup lewat model agar state (query, indeks) ikut
// direset; `closedby` belum didukung semua browser, jadi ini jalur utama.
const onCancel = (event) => {
    event.preventDefault();
    close();
};

// Tombol Escape di browser lama tidak memicu `cancel`; handler ini menjaga
// perilaku yang sama dan dipakai juga oleh unit test.
const onEscape = () => close();

const onClose = () => {
    if (props.modelValue) {
        close();
    }
};

// Klik pada area backdrop menghasilkan event dengan target dialog itu sendiri.
const onBackdropClick = (event) => {
    if (event.target === dialog.value) {
        close();
    }
};

const onKeydown = (event) => {
    const length = results.value.length;
    let moved = false;

    if (event.key === 'ArrowDown') {
        event.preventDefault();
        activeIndex.value = stepIndex(activeIndex.value, 1, length);
        moved = true;
    } else if (event.key === 'ArrowUp') {
        event.preventDefault();
        activeIndex.value = stepIndex(activeIndex.value, -1, length);
        moved = true;
    } else if (event.key === 'Home') {
        event.preventDefault();
        activeIndex.value = length > 0 ? 0 : -1;
        moved = true;
    } else if (event.key === 'End') {
        event.preventDefault();
        activeIndex.value = length > 0 ? length - 1 : -1;
        moved = true;
    } else if (event.key === 'Enter') {
        event.preventDefault();
        select(activeEntry.value);

        return;
    }

    if (moved) {
        scrollActiveIntoView();
    }
};

// `scrollIntoView` tidak ada di semua DOM test; panggilan opsional menjaga
// komponen tetap bisa diuji tanpa memalsukan API browser.
const scrollActiveIntoView = () => {
    nextTick(() => {
        const active = list.value?.querySelector('[aria-selected="true"]');

        active?.scrollIntoView?.({ block: 'nearest' });
    });
};

watch(query, () => {
    activeIndex.value = results.value.length > 0 ? 0 : -1;
});

// Locale atau permission bisa berubah saat palette terbuka, sehingga jumlah
// hasil menyusut dan indeks lama bisa menunjuk entri yang sudah hilang.
watch(
    () => props.entries,
    () => {
        activeIndex.value = clampIndex(activeIndex.value, results.value.length);
    },
);

watch(
    () => props.modelValue,
    (open) => {
        syncDialog(open);

        if (open) {
            reset();
            focusInput();
        }
    },
);

onMounted(() => {
    syncDialog(props.modelValue);

    if (props.modelValue) {
        reset();
        focusInput();
    }
});
</script>

<template>
    <dialog
        ref="dialog"
        class="modal palette"
        :aria-label="t('Cari menu')"
        @cancel="onCancel"
        @close="onClose"
        @click="onBackdropClick"
        @keydown="onKeydown"
        @keydown.esc="onEscape"
    >
        <div class="modal-box" role="document">
            <div class="flex items-center gap-3 border-b border-base-300 px-4">
                <Search class="size-5 shrink-0 text-base-content/50" aria-hidden="true" />
                <input
                    ref="input"
                    v-model="query"
                    type="text"
                    class="input input-ghost w-full px-0 focus:outline-none"
                    role="combobox"
                    aria-autocomplete="list"
                    aria-expanded="true"
                    :aria-controls="listId"
                    :aria-activedescendant="activeIndex >= 0 && hasResults ? optionId(activeIndex) : undefined"
                    :aria-label="t('Cari menu')"
                    :placeholder="t('Cari menu atau aksi…')"
                    autocomplete="off"
                    spellcheck="false"
                />
            </div>

            <ul
                :id="listId"
                ref="list"
                role="listbox"
                :aria-label="t('Hasil pencarian')"
                class="max-h-[60vh] overflow-y-auto p-2"
            >
                <template v-for="section in sections" :key="section.key">
                    <li
                        role="presentation"
                        class="px-3 pt-2 pb-1 text-xs font-semibold tracking-wider text-base-content/50 uppercase"
                    >
                        {{ section.label }}
                    </li>

                    <li
                        v-for="row in section.items"
                        :id="optionId(row.index)"
                        :key="`${section.key}-${row.entry.key}`"
                        role="option"
                        :aria-selected="row.index === activeIndex ? 'true' : 'false'"
                        class="flex cursor-pointer items-center gap-3 rounded-lg px-3 py-2 text-sm"
                        :class="{ 'bg-base-200': row.index === activeIndex }"
                        @click="select(row.entry)"
                        @mousemove="activeIndex = row.index"
                    >
                        <component :is="row.entry.icon" class="size-4 shrink-0" aria-hidden="true" />
                        <span class="truncate">{{ row.entry.label }}</span>
                    </li>
                </template>

                <li
                    v-if="!hasResults"
                    role="status"
                    class="px-3 py-6 text-center text-sm text-base-content/60"
                >
                    {{ t('Tidak ada hasil untuk ":query"', { query }) }}
                </li>
            </ul>

            <div
                class="flex items-center justify-end gap-3 border-t border-base-300 px-4 py-2 text-xs text-base-content/60"
            >
                <span class="flex items-center gap-1">
                    <kbd class="kbd kbd-sm">↑</kbd>
                    <kbd class="kbd kbd-sm">↓</kbd>
                    {{ t('Navigasi') }}
                </span>
                <span class="flex items-center gap-1">
                    <kbd class="kbd kbd-sm">↵</kbd>
                    {{ t('Pilih') }}
                </span>
                <span class="flex items-center gap-1">
                    <kbd class="kbd kbd-sm">esc</kbd>
                </span>
            </div>
        </div>
    </dialog>
</template>

<style scoped>
/* Palette menempel di bagian atas layar supaya terasa seperti command palette,
   bukan modal konfirmasi. Lebarnya dibatasi agar baris hasil mudah dipindai. */
.modal.palette {
    place-items: start;
    padding: 10vh 1rem 0;
}

.modal.palette > .modal-box {
    width: 100%;
    max-width: 36rem;
    max-height: calc(100vh - 12vh);
    margin-inline: auto;
    padding: 0;
}

@media (prefers-reduced-motion: reduce) {
    .modal,
    .modal-box {
        transition: none;
    }
}
</style>
