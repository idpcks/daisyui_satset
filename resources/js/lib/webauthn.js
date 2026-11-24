/**
 * Utilitas WebAuthn untuk pendaftaran passkey.
 *
 * Server mengirim opsi dalam bentuk JSON dengan nilai biner ber-encoding
 * base64url; browser meminta ArrayBuffer. Fungsi di sini mengubah keduanya
 * tanpa dependensi tambahan.
 */

/**
 * @param {ArrayBuffer|Uint8Array} buffer
 * @returns {string}
 */
export const toBase64Url = (buffer) => {
    const bytes = buffer instanceof Uint8Array ? buffer : new Uint8Array(buffer);
    let binary = '';

    for (const byte of bytes) {
        binary += String.fromCharCode(byte);
    }

    return btoa(binary).replace(/\+/g, '-').replace(/\//g, '_').replace(/=+$/, '');
};

/**
 * @param {string} value
 * @returns {ArrayBuffer}
 */
export const fromBase64Url = (value) => {
    const normalized = value.replace(/-/g, '+').replace(/_/g, '/');
    const padded = normalized.padEnd(normalized.length + ((4 - (normalized.length % 4)) % 4), '=');
    const binary = atob(padded);
    const bytes = new Uint8Array(binary.length);

    for (let index = 0; index < binary.length; index += 1) {
        bytes[index] = binary.charCodeAt(index);
    }

    return bytes.buffer;
};

/**
 * Apakah browser ini punya WebAuthn dan bisa membuat passkey.
 *
 * @returns {boolean}
 */
export const isPasskeySupported = () =>
    typeof window !== 'undefined' &&
    typeof window.PublicKeyCredential !== 'undefined' &&
    typeof navigator !== 'undefined' &&
    typeof navigator.credentials === 'object' &&
    navigator.credentials !== null;

/**
 * Ubah opsi dari server menjadi `PublicKeyCredentialCreationOptions` browser.
 *
 * @param {Record<string, any>} options
 * @returns {Record<string, any>}
 */
export const toCreationOptions = (options) => ({
    ...options,
    challenge: fromBase64Url(options.challenge),
    user: {
        ...options.user,
        id: fromBase64Url(options.user.id),
    },
    ...(Array.isArray(options.excludeCredentials)
        ? {
              excludeCredentials: options.excludeCredentials.map((credential) => ({
                  ...credential,
                  id: fromBase64Url(credential.id),
              })),
          }
        : {}),
});

/**
 * Serialisasi `PublicKeyCredential` menjadi JSON yang diterima laravel/passkeys.
 *
 * @param {PublicKeyCredential} credential
 * @returns {Record<string, any>}
 */
export const serializeCredential = (credential) => {
    const response = credential.response;

    return {
        id: credential.id,
        rawId: toBase64Url(credential.rawId),
        type: credential.type,
        ...(credential.authenticatorAttachment
            ? { authenticatorAttachment: credential.authenticatorAttachment }
            : {}),
        clientExtensionResults:
            typeof credential.getClientExtensionResults === 'function'
                ? credential.getClientExtensionResults()
                : {},
        response: {
            clientDataJSON: toBase64Url(response.clientDataJSON),
            attestationObject: toBase64Url(response.attestationObject),
            transports:
                typeof response.getTransports === 'function' ? response.getTransports() : [],
            ...(typeof response.getPublicKeyAlgorithm === 'function'
                ? { publicKeyAlgorithm: response.getPublicKeyAlgorithm() }
                : {}),
            ...(typeof response.getPublicKey === 'function' && response.getPublicKey()
                ? { publicKey: toBase64Url(response.getPublicKey()) }
                : {}),
        },
    };
};

/**
 * Ubah opsi login dari server menjadi `PublicKeyCredentialRequestOptions`.
 *
 * @param {Record<string, any>} options
 * @returns {Record<string, any>}
 */
export const toRequestOptions = (options) => ({
    ...options,
    challenge: fromBase64Url(options.challenge),
    ...(Array.isArray(options.allowCredentials)
        ? {
              allowCredentials: options.allowCredentials.map((credential) => ({
                  ...credential,
                  id: fromBase64Url(credential.id),
              })),
          }
        : {}),
});

/**
 * Serialisasi `PublicKeyCredential` hasil login menjadi JSON untuk server.
 *
 * @param {PublicKeyCredential} credential
 * @returns {Record<string, any>}
 */
export const serializeAssertionCredential = (credential) => {
    const response = credential.response;

    return {
        id: credential.id,
        rawId: toBase64Url(credential.rawId),
        type: credential.type,
        ...(credential.authenticatorAttachment
            ? { authenticatorAttachment: credential.authenticatorAttachment }
            : {}),
        clientExtensionResults:
            typeof credential.getClientExtensionResults === 'function'
                ? credential.getClientExtensionResults()
                : {},
        response: {
            clientDataJSON: toBase64Url(response.clientDataJSON),
            authenticatorData: toBase64Url(response.authenticatorData),
            signature: toBase64Url(response.signature),
            userHandle: response.userHandle ? toBase64Url(response.userHandle) : null,
        },
    };
};

/**
 * Minta browser membuat passkey baru dan kembalikan payload siap kirim.
 *
 * @param {Record<string, any>} options opsi dari `GET /user/passkeys/options`
 * @returns {Promise<Record<string, any>>}
 */
export const createPasskeyCredential = async (options) => {
    if (!isPasskeySupported()) {
        throw new Error('Browser ini tidak mendukung passkey.');
    }

    const credential = await navigator.credentials.create({
        publicKey: toCreationOptions(options),
    });

    if (!credential) {
        throw new Error('Pendaftaran passkey dibatalkan.');
    }

    return serializeCredential(credential);
};

/**
 * Minta browser memilih passkey terdaftar dan kembalikan payload siap kirim.
 *
 * @param {Record<string, any>} options opsi dari `GET /passkeys/login/options`
 * @returns {Promise<Record<string, any>>}
 */
export const getPasskeyCredential = async (options) => {
    if (!isPasskeySupported()) {
        throw new Error('Browser ini tidak mendukung passkey.');
    }

    const credential = await navigator.credentials.get({
        publicKey: toRequestOptions(options),
    });

    if (!credential) {
        throw new Error('Masuk dengan passkey dibatalkan.');
    }

    return serializeAssertionCredential(credential);
};
