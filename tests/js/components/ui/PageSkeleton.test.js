import { mount } from '@vue/test-utils';
import { describe, expect, it } from 'vitest';
import PageSkeleton from '@/components/ui/PageSkeleton.vue';

describe('PageSkeleton', () => {
    it('menampilkan enam baris skeleton secara bawaan', () => {
        const wrapper = mount(PageSkeleton);

        expect(wrapper.attributes('aria-busy')).toBe('true');
        expect(wrapper.attributes('aria-label')).toBe('Memuat halaman');
        expect(wrapper.findAll('.skeleton')).toHaveLength(36);
    });

    it('mengikuti jumlah baris yang diminta', () => {
        const wrapper = mount(PageSkeleton, { props: { rows: 2 } });

        expect(wrapper.findAll('.skeleton')).toHaveLength(16);
    });
});
