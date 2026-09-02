import assert from 'node:assert/strict';
import { resolveAgentVerificationUrl } from '../../resources/js/agent-scanner-url.js';

const origin = 'http://127.0.0.1:8000';

assert.deepEqual(
    resolveAgentVerificationUrl('http://127.0.0.1:8000/agent/rdvs/abc123DEF/verification', origin),
    {
        valid: true,
        url: 'http://127.0.0.1:8000/agent/rdvs/abc123DEF/verification',
        error: null,
    },
);

assert.equal(
    resolveAgentVerificationUrl('/agent/rdvs/abc123DEF/verification', origin).valid,
    true,
);

assert.equal(
    resolveAgentVerificationUrl('https://example.com/agent/rdvs/abc123DEF/verification', origin).valid,
    false,
);

assert.equal(
    resolveAgentVerificationUrl('http://127.0.0.1:8000/souscripteur/rdvs/abc123DEF/fiche', origin).valid,
    false,
);

assert.equal(
    resolveAgentVerificationUrl('plain text', origin).valid,
    false,
);
