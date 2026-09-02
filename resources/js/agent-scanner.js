import { Html5Qrcode } from 'html5-qrcode';
import { resolveAgentVerificationUrl } from './agent-scanner-url';

const readerId = 'agent-qr-reader';

function setMessage(element, message, isError = false) {
    element.textContent = message;
    element.classList.toggle('bg-red-50', isError);
    element.classList.toggle('text-red-700', isError);
    element.classList.toggle('bg-gray-50', ! isError);
    element.classList.toggle('text-gray-700', ! isError);
}

async function stopScanner(scanner, state, messageElement) {
    if (! state.running) {
        return;
    }

    try {
        await scanner.stop();
        await scanner.clear();
        state.running = false;
        setMessage(messageElement, 'Scanner arrêté.');
    } catch {
        state.running = false;
        setMessage(messageElement, 'La caméra a été arrêtée ou n’était plus disponible.', true);
    }
}

function initAgentScanner(container) {
    const startButton = container.querySelector('[data-scanner-start]');
    const stopButton = container.querySelector('[data-scanner-stop]');
    const messageElement = container.querySelector('[data-scanner-message]');
    const scanner = new Html5Qrcode(readerId);
    const state = { running: false, redirecting: false };

    startButton?.addEventListener('click', async () => {
        if (state.running || state.redirecting) {
            return;
        }

        try {
            await scanner.start(
                { facingMode: 'environment' },
                { fps: 10, qrbox: { width: 250, height: 250 } },
                async (decodedText) => {
                    if (state.redirecting) {
                        return;
                    }

                    const result = resolveAgentVerificationUrl(decodedText);

                    if (! result.valid) {
                        setMessage(messageElement, result.error, true);
                        return;
                    }

                    state.redirecting = true;
                    await stopScanner(scanner, state, messageElement);
                    window.location.assign(result.url);
                },
            );

            state.running = true;
            setMessage(messageElement, 'Scanner actif. Présentez le QR Code devant la caméra.');
        } catch {
            state.running = false;
            setMessage(
                messageElement,
                'Impossible de démarrer la caméra. Vérifiez l’autorisation, la disponibilité de la caméra ou fermez les autres applications qui l’utilisent.',
                true,
            );
        }
    });

    stopButton?.addEventListener('click', () => stopScanner(scanner, state, messageElement));

    window.addEventListener('beforeunload', () => {
        if (state.running) {
            scanner.stop().catch(() => {});
        }
    });
}

document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('[data-agent-scanner]').forEach(initAgentScanner);
});
