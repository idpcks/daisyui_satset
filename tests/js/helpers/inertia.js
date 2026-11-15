import { h, reactive } from 'vue';
import { vi } from 'vitest';

const defaultProps = () => ({
    appName: 'Starterkit',
    auth: { user: null, roles: [], permissions: [] },
    flash: {},
});

export const page = reactive({ url: '/dashboard', props: defaultProps() });

export function resetPage() {
    page.url = '/dashboard';
    page.props = defaultProps();
}

export function setPage({ url, props } = {}) {
    if (url !== undefined) {
        page.url = url;
    }

    if (props !== undefined) {
        page.props = { ...defaultProps(), ...props };
    }
}

const routerHandlers = new Map();

export const router = {
    get: vi.fn(),
    reload: vi.fn(),
    post: vi.fn(),
    put: vi.fn(),
    delete: vi.fn(),
    visit: vi.fn(),
    on: vi.fn((event, handler) => {
        const list = routerHandlers.get(event) ?? [];
        routerHandlers.set(event, [...list, handler]);

        return () => {
            routerHandlers.set(
                event,
                (routerHandlers.get(event) ?? []).filter((item) => item !== handler),
            );
        };
    }),
};

export function emitRouter(event, payload) {
    (routerHandlers.get(event) ?? []).forEach((handler) => handler(payload));
}

export function resetRouter() {
    routerHandlers.clear();
    ['get', 'reload', 'post', 'put', 'delete', 'visit', 'on'].forEach((method) => router[method].mockClear());
}

export function usePage() {
    return page;
}

export const useForm = vi.fn((initial) => {
    const form = reactive({
        ...initial,
        errors: {},
        processing: false,
        recentlySuccessful: false,
    });

    form.post = vi.fn();
    form.put = vi.fn();
    form.delete = vi.fn();
    form.reset = vi.fn((...fields) => {
        fields.forEach((field) => {
            form[field] = '';
        });
    });
    form.clearErrors = vi.fn();

    return form;
});

export const Head = {
    name: 'Head',
    props: { title: [String, Number] },
    render: () => null,
};

export const Link = {
    name: 'Link',
    props: { href: [String, Object] },
    render() {
        return h('a', { href: typeof this.href === 'string' ? this.href : '#' }, this.$slots.default?.());
    },
};
