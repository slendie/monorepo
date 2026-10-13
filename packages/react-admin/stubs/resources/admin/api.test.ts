import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';

// Ambiente "node": simulamos só o que api.ts toca no browser.
const assign = vi.fn();

beforeEach(() => {
    vi.stubGlobal('window', { location: { origin: 'http://localhost', assign } });
    vi.stubGlobal('document', { querySelector: () => ({ content: 'tok-123' }) });
});

afterEach(() => {
    vi.unstubAllGlobals();
    vi.resetModules();
    assign.mockClear();
});

const respond = (status: number, body: unknown = {}) =>
    vi.fn().mockResolvedValue({ status, ok: status < 400, json: () => Promise.resolve(body) });

describe('api()', () => {
    it('envia o token CSRF e a query sem valores vazios', async () => {
        const fetchMock = respond(200, { ok: true });
        vi.stubGlobal('fetch', fetchMock);
        const { api } = await import('./api');

        await api('/leads', { query: { q: 'ana', status: '', page: 2 } });

        const [url, init] = fetchMock.mock.calls[0];
        expect(String(url)).toBe('http://localhost/api/admin/leads?q=ana&page=2');
        expect(init.headers['X-CSRF-TOKEN']).toBe('tok-123');
    });

    it('401 redirecciona para /login', async () => {
        vi.stubGlobal('fetch', respond(401));
        const { api } = await import('./api');

        await expect(api('/me')).rejects.toMatchObject({ status: 401 });
        expect(assign).toHaveBeenCalledWith('/login');
    });

    it('erros de validação chegam em ApiError.errors', async () => {
        vi.stubGlobal('fetch', respond(422, { message: 'Inválido', errors: { status: ['x'] } }));
        const { api } = await import('./api');

        await expect(api('/leads/1', { method: 'PATCH', body: { status: 'x' } })).rejects.toMatchObject({
            status: 422,
            errors: { status: ['x'] },
        });
    });
});
