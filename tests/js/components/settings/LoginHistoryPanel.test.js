import { mount } from '@vue/test-utils';
import { describe, expect, it } from 'vitest';
import LoginHistoryPanel from '@/components/settings/LoginHistoryPanel.vue';

const mountPanel = (entries = []) =>
    mount(LoginHistoryPanel, {
        props: { entries },
    });

describe('komponen settings/LoginHistoryPanel', () => {
    it('menampilkan status, IP, dan perangkat dari riwayat login', () => {
        const wrapper = mountPanel([
            {
                id: 1,
                successful: true,
                ip_address: '203.0.113.10',
                user_agent:
                    'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
                created_at: '2026-11-15T10:30:00+07:00',
            },
            {
                id: 2,
                successful: false,
                ip_address: '203.0.113.11',
                user_agent:
                    'Mozilla/5.0 (iPhone; CPU iPhone OS 17_0 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/17.0 Mobile/15E148 Safari/604.1',
                created_at: '2026-11-14T09:00:00+07:00',
            },
        ]);

        expect(wrapper.text()).toContain('Berhasil');
        expect(wrapper.text()).toContain('Gagal');
        expect(wrapper.text()).toContain('203.0.113.10');
        expect(wrapper.text()).toContain('203.0.113.11');
        expect(wrapper.text()).toContain('Chrome · Windows');
        expect(wrapper.text()).toContain('Safari · iOS');
    });

    it('menampilkan perangkat tidak diketahui saat user agent kosong', () => {
        const wrapper = mountPanel([
            {
                id: 1,
                successful: false,
                ip_address: null,
                user_agent: null,
                created_at: '2026-11-15T10:30:00+07:00',
            },
        ]);

        expect(wrapper.text()).toContain('Tidak diketahui');
        expect(wrapper.text()).toContain('—');
    });

    it('menampilkan empty state ketika belum ada riwayat', () => {
        const wrapper = mountPanel();

        expect(wrapper.text()).toContain('Belum ada riwayat login.');
        expect(wrapper.find('table').exists()).toBe(false);
    });
});
