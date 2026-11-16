const detectBrowser = (userAgent) => {
    if (userAgent.includes('Edg/')) {
        return 'Edge';
    }

    if (userAgent.includes('OPR/')) {
        return 'Opera';
    }

    if (userAgent.includes('Firefox/')) {
        return 'Firefox';
    }

    if (userAgent.includes('Chrome/') || userAgent.includes('CriOS/')) {
        return 'Chrome';
    }

    return userAgent.includes('Safari/') ? 'Safari' : 'Browser';
};

const detectPlatform = (userAgent) => {
    if (userAgent.includes('Windows')) {
        return 'Windows';
    }

    if (userAgent.includes('Android')) {
        return 'Android';
    }

    if (userAgent.includes('iPhone') || userAgent.includes('iPad')) {
        return 'iOS';
    }

    if (userAgent.includes('Macintosh')) {
        return 'macOS';
    }

    return userAgent.includes('Linux') ? 'Linux' : null;
};

export const formatDevice = (userAgent) => {
    if (!userAgent) {
        return 'Tidak diketahui';
    }

    const browser = detectBrowser(userAgent);
    const platform = detectPlatform(userAgent);

    return platform ? `${browser} · ${platform}` : browser;
};
