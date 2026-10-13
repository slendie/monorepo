export interface AdminConfig {
    user: { id: number | null; name: string | null; email: string | null };
    statuses: string[];
    locale: string;
    base: string;
    api: string;
}

let config: AdminConfig = {
    user: { id: null, name: null, email: null },
    statuses: ['new', 'contacted', 'qualified', 'lost', 'won'],
    locale: 'pt',
    base: '/admin',
    api: '/api/admin',
};

/** Lê o JSON de `<div id="app" data-config='...'>` escrito pela view `react-admin::app`. */
export function readConfig(el: HTMLElement): AdminConfig {
    try {
        config = { ...config, ...JSON.parse(el.dataset.config ?? '{}') };
    } catch {
        // mantém os valores por omissão
    }

    return config;
}

export const getConfig = (): AdminConfig => config;
