<script setup>
import { computed } from 'vue';
import { useForm } from '@inertiajs/vue3';
import { AppWindow, Globe, Wrench } from 'lucide-vue-next';
import { routes } from '@/lib/routes';
import { appUrl } from '@/lib/url';

const props = defineProps({
    settings: { type: Object, required: true },
    locales: { type: Array, default: () => [] },
    timezones: { type: Array, default: () => [] },
});

const form = useForm({
    app_name: props.settings.app_name ?? '',
    locale: props.settings.locale ?? 'id',
    timezone: props.settings.timezone ?? 'Asia/Jakarta',
    maintenance_enabled: props.settings.maintenance_enabled ?? false,
    maintenance_message: props.settings.maintenance_message ?? '',
});

const messageLength = computed(() => form.maintenance_message.length);

const submit = () => {
    form.put(appUrl(routes.settingsSystem), { preserveScroll: true });
};
</script>

<template>
    <div class="card bg-base-100 shadow">
        <div class="card-body gap-5">
            <div class="flex items-start gap-3">
                <div class="grid size-10 shrink-0 place-items-center rounded-lg bg-base-200">
                    <AppWindow class="size-5" />
                </div>
                <div>
                    <h2 class="card-title text-base">Konfigurasi Sistem</h2>
                    <p class="text-sm text-base-content/60">
                        Setelan level aplikasi yang berlaku untuk semua pengguna. Perubahan di sini tidak mengubah akun
                        Anda.
                    </p>
                </div>
            </div>

            <form class="space-y-5" @submit.prevent="submit">
                <!-- Identitas aplikasi -->
                <label class="flex flex-col gap-1">
                    <span class="text-sm">
                        Nama aplikasi <span class="text-error" aria-hidden="true">*</span>
                    </span>
                    <input
                        v-model="form.app_name"
                        type="text"
                        maxlength="60"
                        aria-required="true"
                        class="input input-bordered w-full"
                        :class="{ 'input-error': form.errors.app_name }"
                    />
                    <span v-if="form.errors.app_name" class="text-sm text-error">{{ form.errors.app_name }}</span>
                    <span class="text-sm text-base-content/60">
                        Dipakai di judul tab browser, brand sidebar, dan halaman maintenance. {{ form.app_name.length }}/60
                        karakter.
                    </span>
                </label>

                <div class="divider my-0"></div>

                <!-- Regional -->
                <div class="flex items-start gap-3">
                    <Globe class="mt-0.5 size-5 shrink-0 text-base-content/60" />
                    <div class="w-full">
                        <h3 class="text-sm font-medium">Bahasa &amp; Zona Waktu</h3>
                        <p class="mb-3 text-sm text-base-content/60">
                            Bahasa memengaruhi pesan validasi; zona waktu dipakai saat menampilkan dan menyimpan waktu.
                        </p>

                        <div class="grid gap-4 md:grid-cols-2">
                            <label class="flex flex-col gap-1">
                                <span class="text-sm">
                                    Bahasa <span class="text-error" aria-hidden="true">*</span>
                                </span>
                                <select
                                    v-model="form.locale"
                                    class="select select-bordered w-full"
                                    :class="{ 'select-error': form.errors.locale }"
                                >
                                    <option v-for="locale in locales" :key="locale.value" :value="locale.value">
                                        {{ locale.label }}
                                    </option>
                                </select>
                                <span v-if="form.errors.locale" class="text-sm text-error">{{ form.errors.locale }}</span>
                            </label>

                            <label class="flex flex-col gap-1">
                                <span class="text-sm">
                                    Zona waktu <span class="text-error" aria-hidden="true">*</span>
                                </span>
                                <select
                                    v-model="form.timezone"
                                    class="select select-bordered w-full"
                                    :class="{ 'select-error': form.errors.timezone }"
                                >
                                    <option v-for="timezone in timezones" :key="timezone" :value="timezone">
                                        {{ timezone }}
                                    </option>
                                </select>
                                <span v-if="form.errors.timezone" class="text-sm text-error">
                                    {{ form.errors.timezone }}
                                </span>
                            </label>
                        </div>
                        <span class="mt-1 block text-sm text-base-content/60">
                            Mengubah zona waktu hanya memengaruhi data baru, bukan data yang sudah tersimpan.
                        </span>
                    </div>
                </div>

                <div class="divider my-0"></div>

                <!-- Maintenance -->
                <div class="flex items-start gap-3">
                    <Wrench class="mt-0.5 size-5 shrink-0 text-base-content/60" />
                    <div class="w-full">
                        <div class="flex flex-wrap items-start justify-between gap-3">
                            <div>
                                <h3 class="text-sm font-medium">Mode Maintenance</h3>
                                <p class="text-sm text-base-content/60">
                                    Saat aktif, pengguna lain menerima halaman 503 beserta pesan di bawah.
                                </p>
                            </div>

                            <label class="flex cursor-pointer items-center gap-2">
                                <span class="text-sm">{{ form.maintenance_enabled ? 'Aktif' : 'Nonaktif' }}</span>
                                <input
                                    v-model="form.maintenance_enabled"
                                    type="checkbox"
                                    class="toggle toggle-warning"
                                    aria-label="Aktifkan mode maintenance"
                                />
                            </label>
                        </div>

                        <div v-if="form.maintenance_enabled" role="alert" class="alert alert-warning mt-3 text-sm">
                            <Wrench class="size-5 shrink-0" />
                            <span>
                                Superadmin tetap bisa masuk, dan halaman login tetap terbuka agar mode ini bisa dimatikan
                                kembali.
                            </span>
                        </div>

                        <label class="mt-3 flex flex-col gap-1">
                            <span class="text-sm">Pesan maintenance</span>
                            <textarea
                                v-model="form.maintenance_message"
                                rows="3"
                                maxlength="500"
                                :disabled="!form.maintenance_enabled"
                                class="textarea textarea-bordered w-full"
                                :class="{ 'textarea-error': form.errors.maintenance_message }"
                            ></textarea>
                            <span v-if="form.errors.maintenance_message" class="text-sm text-error">
                                {{ form.errors.maintenance_message }}
                            </span>
                            <span class="text-sm text-base-content/60">{{ messageLength }}/500 karakter</span>
                        </label>
                    </div>
                </div>

                <div class="flex justify-end">
                    <button type="submit" class="btn btn-primary" :disabled="form.processing">
                        <span v-if="form.processing" class="loading loading-spinner loading-sm"></span>
                        Simpan
                    </button>
                </div>
            </form>
        </div>
    </div>
</template>
