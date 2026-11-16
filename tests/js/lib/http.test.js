import { afterEach, describe, expect, it, vi } from 'vitest';
import { RequestError, requestJson } from '@/lib/http';

const jsonResponse = (body, status = 200) =>
    new Response(body === null ? null : JSON.stringify(body), {
        status,
        headers: { 'Content-Type': 'application/json' },
    });

describe('lib/http', () => {
    afterEach(() => {
        vi.unstubAllGlobals();
        document.cookie = 'XSRF-TOKEN=; expires=Thu, 01 Jan 1970 00:00:00 GMT';
    });

    it('mengirim header JSON dan token CSRF dari cookie', async () => {
        document.cookie = 'XSRF-TOKEN=token%3D123';
        const fetchMock = vi.fn().mockResolvedValue(jsonResponse({ status: 'ok' }));
        vi.stubGlobal('fetch', fetchMock);

        const data = await requestJson('/user/passkeys', {
            method: 'POST',
            body: { name: 'Laptop' },
        });

        expect(data).toEqual({ status: 'ok' });
        expect(fetchMock).toHaveBeenCalledWith(
            '/user/passkeys',
            expect.objectContaining({
                method: 'POST',
                credentials: 'same-origin',
                body: JSON.stringify({ name: 'Laptop' }),
                headers: expect.objectContaining({
                    Accept: 'application/json',
                    'Content-Type': 'application/json',
                    'X-XSRF-TOKEN': 'token=123',
                }),
            }),
        );
    });

    it('mengembalikan null untuk respons tanpa body', async () => {
        vi.stubGlobal('fetch', vi.fn().mockResolvedValue(new Response(null, { status: 204 })));

        await expect(requestJson('/user/passkeys/pk-1', { method: 'DELETE' })).resolves.toBeNull();
    });

    it('melempar RequestError berisi error validasi', async () => {
        vi.stubGlobal(
            'fetch',
            vi.fn().mockResolvedValue(
                jsonResponse(
                    {
                        message: 'Data yang diberikan tidak valid.',
                        errors: { password: ['Password yang diberikan tidak cocok.'] },
                    },
                    422,
                ),
            ),
        );

        const error = await requestJson('/user/confirm-password', {
            method: 'POST',
            body: { password: 'salah' },
        }).catch((caught) => caught);

        expect(error).toBeInstanceOf(RequestError);
        expect(error.status).toBe(422);
        expect(error.fieldError('password')).toBe('Password yang diberikan tidak cocok.');
        expect(error.fieldError('name')).toBeNull();
    });

    it('memakai pesan default bila server tidak mengirim pesan', async () => {
        vi.stubGlobal('fetch', vi.fn().mockResolvedValue(new Response('', { status: 423 })));

        await expect(requestJson('/user/passkeys/options')).rejects.toThrow('Permintaan gagal diproses.');
    });
});
