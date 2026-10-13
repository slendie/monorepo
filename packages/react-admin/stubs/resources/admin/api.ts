import { getConfig } from './config';

export class ApiError extends Error {
    constructor(
        public status: number,
        message: string,
        public errors: Record<string, string[]> = {},
    ) {
        super(message);
    }
}

export const csrfToken = (): string =>
    document.querySelector<HTMLMetaElement>('meta[name="csrf-token"]')?.content ?? '';

/** Redirecciona para o login (sessão expirada). Separado para os testes o poderem observar. */
export const redirectToLogin = (): void => {
    window.location.assign('/login');
};

/**
 * Wrapper de fetch para /api/admin: JSON, cookies de sessão, `X-CSRF-TOKEN` e 401 → /login.
 * Não usa tokens Bearer: a SPA corre no mesmo domínio que a sessão.
 */
export async function api<T>(path: string, options: { method?: string; body?: unknown; query?: Record<string, unknown> } = {}): Promise<T> {
    const url = new URL(getConfig().api + path, window.location.origin);

    for (const [key, value] of Object.entries(options.query ?? {})) {
        if (value !== undefined && value !== null && value !== '') {
            url.searchParams.set(key, String(value));
        }
    }

    const response = await fetch(url, {
        method: options.method ?? 'GET',
        credentials: 'same-origin',
        headers: {
            Accept: 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
            'X-CSRF-TOKEN': csrfToken(),
            ...(options.body !== undefined ? { 'Content-Type': 'application/json' } : {}),
        },
        body: options.body !== undefined ? JSON.stringify(options.body) : undefined,
    });

    if (response.status === 401) {
        redirectToLogin();
        throw new ApiError(401, 'Unauthenticated.');
    }

    if (response.status === 204) {
        return undefined as T;
    }

    const data = await response.json().catch(() => ({}));

    if (!response.ok) {
        throw new ApiError(response.status, data.message ?? 'Erro', data.errors ?? {});
    }

    return data as T;
}
