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
    typeof navigator.credentials?.create === 'function';

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
            transports: typeof response.getTransports === 'function' ? response.getTransports() : [],
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
