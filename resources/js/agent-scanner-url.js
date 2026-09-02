const verificationPath = /^\/agent\/rdvs\/([A-Za-z0-9]+)\/verification\/?$/;

export function resolveAgentVerificationUrl(content, currentOrigin = window.location.origin) {
    if (typeof content !== 'string' || content.trim() === '') {
        return { valid: false, url: null, error: 'Le QR Code est vide ou invalide.' };
    }

    let url;

    try {
        url = new URL(content.trim(), currentOrigin);
    } catch {
        return { valid: false, url: null, error: 'Le QR Code ne contient pas une URL valide.' };
    }

    if (url.origin !== currentOrigin) {
        return { valid: false, url: null, error: 'Ce QR Code ne correspond pas à cette application.' };
    }

    if (! verificationPath.test(url.pathname)) {
        return { valid: false, url: null, error: 'Ce QR Code ne correspond pas à une vérification de rendez-vous.' };
    }

    return { valid: true, url: url.toString(), error: null };
}
