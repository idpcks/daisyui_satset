<script setup>
import { Palette, PanelLeft, PanelTop, Smartphone } from 'lucide-vue-next';
import { useI18n } from '@/lib/i18n';
import { useNavigationLayout } from '@/lib/useNavigationLayout';

const { layout, setLayout } = useNavigationLayout();
const { t } = useI18n();
</script>

<template>
    <div class="card bg-base-100 shadow">
        <div class="card-body gap-4">
            <div class="flex items-start gap-3">
                <div class="grid size-10 shrink-0 place-items-center rounded-lg bg-base-200">
                    <Palette class="size-5" aria-hidden="true" />
                </div>
                <div>
                    <h2 class="card-title text-base">{{ t('Tampilan') }}</h2>
                    <p class="text-sm text-base-content/60">
                        {{ t('Pilih tampilan menu yang paling nyaman untuk Anda.') }}
                    </p>
                </div>
            </div>

            <fieldset class="grid gap-3 md:grid-cols-2">
                <legend class="sr-only">{{ t('Tampilan menu') }}</legend>

                <label
                    class="card cursor-pointer border-2 bg-base-100 shadow-sm transition-colors"
                    :class="
                        layout === 'sidebar'
                            ? 'border-primary'
                            : 'border-base-300 hover:border-base-content/20'
                    "
                >
                    <div class="card-body gap-3">
                        <div class="flex items-start justify-between gap-3">
                            <div class="flex min-w-0 items-center gap-3">
                                <div
                                    class="grid size-10 shrink-0 place-items-center rounded-lg bg-base-200"
                                >
                                    <PanelLeft class="size-5" aria-hidden="true" />
                                </div>
                                <div class="min-w-0">
                                    <h3 class="font-semibold">{{ t('Menu vertikal') }}</h3>
                                    <p class="text-sm text-base-content/60">
                                        {{ t('Berlaku di layar lebar.') }}
                                    </p>
                                </div>
                            </div>
                            <input
                                type="radio"
                                name="navigation-layout"
                                value="sidebar"
                                class="radio radio-primary shrink-0"
                                :checked="layout === 'sidebar'"
                                @change="setLayout('sidebar')"
                            />
                        </div>

                        <div
                            class="grid h-24 grid-cols-[3rem_1fr] gap-2 rounded-lg bg-base-200 p-2"
                            aria-hidden="true"
                        >
                            <div class="flex flex-col gap-1 rounded bg-base-100 p-1.5">
                                <div class="h-1.5 rounded-full bg-primary/60"></div>
                                <div class="h-1.5 rounded-full bg-base-content/15"></div>
                                <div class="h-1.5 rounded-full bg-base-content/15"></div>
                            </div>
                            <div class="rounded bg-base-100 p-2">
                                <div class="mb-2 h-1.5 w-2/3 rounded-full bg-base-content/20"></div>
                                <div class="h-1.5 w-full rounded-full bg-base-content/10"></div>
                                <div class="mt-1 h-1.5 w-4/5 rounded-full bg-base-content/10"></div>
                            </div>
                        </div>
                    </div>
                </label>

                <label
                    class="card cursor-pointer border-2 bg-base-100 shadow-sm transition-colors"
                    :class="
                        layout === 'topbar'
                            ? 'border-primary'
                            : 'border-base-300 hover:border-base-content/20'
                    "
                >
                    <div class="card-body gap-3">
                        <div class="flex items-start justify-between gap-3">
                            <div class="flex min-w-0 items-center gap-3">
                                <div
                                    class="grid size-10 shrink-0 place-items-center rounded-lg bg-base-200"
                                >
                                    <PanelTop class="size-5" aria-hidden="true" />
                                </div>
                                <div class="min-w-0">
                                    <h3 class="font-semibold">{{ t('Menu horizontal') }}</h3>
                                    <p class="text-sm text-base-content/60">
                                        {{ t('Berlaku di layar lebar.') }}
                                    </p>
                                </div>
                            </div>
                            <input
                                type="radio"
                                name="navigation-layout"
                                value="topbar"
                                class="radio radio-primary shrink-0"
                                :checked="layout === 'topbar'"
                                @change="setLayout('topbar')"
                            />
                        </div>

                        <div
                            class="flex h-24 flex-col gap-2 rounded-lg bg-base-200 p-2"
                            aria-hidden="true"
                        >
                            <div class="flex items-center gap-1 rounded bg-base-100 px-2 py-1.5">
                                <div class="h-1.5 w-8 rounded-full bg-primary/60"></div>
                                <div class="h-1.5 w-6 rounded-full bg-base-content/15"></div>
                                <div class="h-1.5 w-6 rounded-full bg-base-content/15"></div>
                                <div
                                    class="ml-auto h-1.5 w-4 rounded-full bg-base-content/15"
                                ></div>
                            </div>
                            <div class="flex-1 rounded bg-base-100 p-2">
                                <div class="h-1.5 w-2/3 rounded-full bg-base-content/20"></div>
                                <div
                                    class="mt-2 h-1.5 w-full rounded-full bg-base-content/10"
                                ></div>
                                <div class="mt-1 h-1.5 w-4/5 rounded-full bg-base-content/10"></div>
                            </div>
                        </div>
                    </div>
                </label>
            </fieldset>

            <p class="flex items-start gap-2 text-sm text-base-content/60">
                <Smartphone class="mt-0.5 size-4 shrink-0" aria-hidden="true" />
                <span>{{ t('Pada layar kecil, navigasi tetap memakai drawer.') }}</span>
            </p>
        </div>
    </div>
</template>
