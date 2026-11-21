/**
 * Logika command palette pencarian menu.
 *
 * Modul ini sengaja bebas dari Vue dan Inertia supaya aturan pencarian bisa
 * diuji langsung. Sumber entri harus menu yang sudah disaring permission
 * (lihat `useNavigation`), bukan definisi mentah di `navigation.js`.
 */

const normalizeText = (value) =>
    String(value ?? '')
        .toLowerCase()
        .replace(/\s+/g, ' ')
        .trim();

/**
 * Menggabungkan menu sidebar dan aksi topbar menjadi daftar datar.
 *
 * @param {{
 *     groups?: Array<{ title: string, items: Array<Record<string, any>> }>,
 *     topbarActions?: Array<Record<string, any>>,
 *     t?: (key: string) => string,
 * }} params
 * @returns {Array<{
 *     key: string,
 *     kind: 'menu' | 'action',
 *     href: string,
 *     icon: any,
 *     label: string,
 *     rawLabel: string,
 *     groupKey: string,
 *     groupLabel: string,
 * }>}
 */
export function buildMenuEntries({ groups = [], topbarActions = [], t = (key) => key } = {}) {
    const entries = [];

    groups.forEach((group) => {
        group.items.forEach((item) => {
            entries.push({
                key: item.key,
                kind: 'menu',
                href: item.href,
                icon: item.icon,
                label: t(item.label),
                rawLabel: item.label,
                groupKey: group.title,
                groupLabel: t(group.title),
            });
        });
    });

    // Aksi topbar tidak punya grup di navigation.js; diberi grup sendiri agar
    // hasil pencarian tetap memberi konteks "ini bukan menu sidebar".
    topbarActions.forEach((action) => {
        entries.push({
            key: action.key,
            kind: 'action',
            href: action.href,
            icon: action.icon,
            label: t(action.label),
            rawLabel: action.label,
            groupKey: 'Aksi',
            groupLabel: t('Aksi'),
        });
    });

    return entries;
}

/**
 * Field yang ikut dicocokkan, dari yang paling relevan.
 *
 * @param {Record<string, any>} entry
 * @returns {string}
 */
const haystack = (entry) =>
    [entry.label, entry.rawLabel, entry.groupLabel, entry.groupKey, entry.key]
        .map(normalizeText)
        .join(' ');

/**
 * Peringkat hasil: makin kecil makin relevan. `Infinity` berarti tidak cocok.
 *
 * @param {Record<string, any>} entry
 * @param {string} query sudah dinormalisasi
 * @param {string[]} tokens
 * @returns {number}
 */
const rankEntry = (entry, query, tokens) => {
    const fields = haystack(entry);

    // Setiap kata wajib muncul (AND), bukan salah satu (OR).
    if (!tokens.every((token) => fields.includes(token))) {
        return Infinity;
    }

    const label = normalizeText(entry.label);

    if (label === query) {
        return 0;
    }

    if (label.startsWith(query)) {
        return 1;
    }

    if (label.split(' ').some((word) => word.startsWith(query))) {
        return 2;
    }

    return label.includes(query) ? 3 : 4;
};

/**
 * Menyaring dan mengurutkan entri. Query kosong menghasilkan daftar kosong;
 * palette menampilkan seluruh entri tanpa filter pada kondisi itu.
 *
 * @param {Array<Record<string, any>>} entries
 * @param {string} query
 * @returns {Array<Record<string, any>>}
 */
export function searchEntries(entries = [], query = '') {
    const normalized = normalizeText(query);

    if (normalized === '') {
        return [];
    }

    const tokens = normalized.split(' ');

    return entries
        .map((entry, index) => ({ entry, index, rank: rankEntry(entry, normalized, tokens) }))
        .filter((result) => result.rank !== Infinity)
        .sort((a, b) => a.rank - b.rank || a.index - b.index)
        .map((result) => result.entry);
}

/**
 * Mengelompokkan hasil per grup untuk ditampilkan sebagai seksi. Grup yang
 * sama digabung pada kemunculan pertamanya supaya judul seksi tidak berulang.
 *
 * @param {Array<Record<string, any>>} entries
 * @returns {Array<{ key: string, label: string, items: Array<Record<string, any>> }>}
 */
export function groupEntries(entries = []) {
    const sections = [];
    const byKey = new Map();

    entries.forEach((entry) => {
        let section = byKey.get(entry.groupKey);

        if (!section) {
            section = { key: entry.groupKey, label: entry.groupLabel, items: [] };
            byKey.set(entry.groupKey, section);
            sections.push(section);
        }

        section.items.push(entry);
    });

    return sections;
}

/**
 * Memindah indeks aktif dengan pembungkus (wrap) agar panah bawah di hasil
 * terakhir kembali ke atas.
 *
 * @param {number} index
 * @param {number} delta
 * @param {number} length
 * @returns {number} `-1` bila tidak ada hasil
 */
export function stepIndex(index, delta, length) {
    if (!Number.isInteger(length) || length <= 0) {
        return -1;
    }

    if (!Number.isInteger(index) || index < 0) {
        return delta > 0 ? 0 : length - 1;
    }

    return (((index + delta) % length) + length) % length;
}

/**
 * Menjaga indeks tetap valid saat jumlah hasil menyusut.
 *
 * @param {number} index
 * @param {number} length
 * @returns {number}
 */
export function clampIndex(index, length) {
    if (!Number.isInteger(length) || length <= 0) {
        return -1;
    }

    if (!Number.isInteger(index) || index < 0) {
        return 0;
    }

    return Math.min(index, length - 1);
}
