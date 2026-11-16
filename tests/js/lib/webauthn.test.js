import { afterEach, describe, expect, it, vi } from 'vitest';
import {
    createPasskeyCredential,
    fromBase64Url,
    isPasskeySupported,
    serializeCredential,
    toBase64Url,
    toCreationOptions,
} from '@/lib/webauthn';

const buffer = (bytes) => new Uint8Array(bytes).buffer;

const credential = () => ({
    id: 'credential-id',
    rawId: buffer([1, 2, 3]),
    type: 'public-key',
    authenticatorAttachment: 'platform',
    getClientExtensionResults: () => ({ credProps: { rk: true } }),
    response: {
        clientDataJSON: buffer([4, 5]),
        attestationObject: buffer([6, 7]),
        getTransports: () => ['internal', 'hybrid'],
        getPublicKeyAlgorithm: () => -7,
        getPublicKey: () => buffer([8, 9]),
    },
});

describe('lib/webauthn', () => {
    afterEach(() => {
        vi.unstubAllGlobals();
    });

    it('mengubah ArrayBuffer ke base64url tanpa padding', () => {
        expect(toBase64Url(buffer([0xfb, 0xff]))).toBe('-_8');
    });

    it('mengembalikan base64url ke ArrayBuffer yang sama', () => {
        const bytes = new Uint8Array([0, 1, 2, 250, 251, 255]);

        expect(new Uint8Array(fromBase64Url(toBase64Url(bytes)))).toEqual(bytes);
    });

    it('mengubah opsi server menjadi opsi browser', () => {
        const options = toCreationOptions({
            challenge: 'AQID',
            rp: { id: 'example.test', name: 'Starterkit' },
            user: { id: 'BAUG', name: 'siti', displayName: 'Siti' },
            excludeCredentials: [{ id: 'BwgJ', type: 'public-key' }],
        });

        expect(new Uint8Array(options.challenge)).toEqual(new Uint8Array([1, 2, 3]));
        expect(new Uint8Array(options.user.id)).toEqual(new Uint8Array([4, 5, 6]));
        expect(options.user.name).toBe('siti');
        expect(options.rp).toEqual({ id: 'example.test', name: 'Starterkit' });
        expect(new Uint8Array(options.excludeCredentials[0].id)).toEqual(new Uint8Array([7, 8, 9]));
    });

    it('menyerialkan kredensial menjadi JSON base64url', () => {
        expect(serializeCredential(credential())).toEqual({
            id: 'credential-id',
            rawId: 'AQID',
            type: 'public-key',
            authenticatorAttachment: 'platform',
            clientExtensionResults: { credProps: { rk: true } },
            response: {
                clientDataJSON: 'BAU',
                attestationObject: 'Bgc',
                transports: ['internal', 'hybrid'],
                publicKeyAlgorithm: -7,
                publicKey: 'CAk',
            },
        });
    });

    it('memakai nilai default saat metode opsional tidak tersedia', () => {
        const minimal = {
            id: 'credential-id',
            rawId: buffer([1]),
            type: 'public-key',
            response: {
                clientDataJSON: buffer([2]),
                attestationObject: buffer([3]),
            },
        };

        const serialized = serializeCredential(minimal);

        expect(serialized).not.toHaveProperty('authenticatorAttachment');
        expect(serialized.clientExtensionResults).toEqual({});
        expect(serialized.response.transports).toEqual([]);
        expect(serialized.response).not.toHaveProperty('publicKey');
        expect(serialized.response).not.toHaveProperty('publicKeyAlgorithm');
    });

    it('mendeteksi dukungan browser', () => {
        expect(isPasskeySupported()).toBe(false);

        vi.stubGlobal('PublicKeyCredential', class {});
        Object.defineProperty(navigator, 'credentials', {
            configurable: true,
            value: { create: vi.fn() },
        });

        expect(isPasskeySupported()).toBe(true);

        delete navigator.credentials;
    });

    it('meminta browser membuat passkey dan mengembalikan payload', async () => {
        vi.stubGlobal('PublicKeyCredential', class {});
        const create = vi.fn().mockResolvedValue(credential());

        Object.defineProperty(navigator, 'credentials', {
            configurable: true,
            value: { create },
        });

        const payload = await createPasskeyCredential({ challenge: 'AQID', user: { id: 'BAU' } });

        expect(create).toHaveBeenCalledTimes(1);
        expect(create.mock.calls[0][0].publicKey.challenge).toBeInstanceOf(ArrayBuffer);
        expect(payload.rawId).toBe('AQID');

        delete navigator.credentials;
    });

    it('melempar error bila browser tidak mendukung passkey', async () => {
        await expect(createPasskeyCredential({ challenge: 'AQID', user: { id: 'BAU' } })).rejects.toThrow(
            'Browser ini tidak mendukung passkey.',
        );
    });
});
