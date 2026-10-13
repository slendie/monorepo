// i18n simples: um objecto por idioma. Acrescenta um idioma copiando `pt` e muda `locale` em AdminController.
const pt = {
    appName: 'Admin',
    dashboard: 'Dashboard',
    leads: 'Leads',
    logout: 'Sair',
    total: 'Total',
    last7Days: 'Últimos 7 dias',
    byStatus: 'Por estado',
    search: 'Pesquisar por nome ou email…',
    allStatuses: 'Todos os estados',
    name: 'Nome',
    email: 'Email',
    phone: 'Telefone',
    message: 'Mensagem',
    source: 'Origem',
    status: 'Estado',
    notes: 'Notas',
    createdAt: 'Criado em',
    save: 'Guardar notas',
    delete: 'Eliminar',
    confirmDelete: 'Eliminar este lead?',
    back: 'Voltar',
    previous: 'Anterior',
    next: 'Seguinte',
    page: 'Página',
    of: 'de',
    results: 'resultados',
    empty: 'Sem resultados.',
    loading: 'A carregar…',
    error: 'Algo correu mal.',
    retry: 'Tentar de novo',
    notFound: 'Página não encontrada.',
    saved: 'Guardado.',
    deleted: 'Lead eliminado.',
    statusLabels: {
        new: 'Novo',
        contacted: 'Contactado',
        qualified: 'Qualificado',
        lost: 'Perdido',
        won: 'Ganho',
    } as Record<string, string>,
};

export type Messages = typeof pt;

const catalogs: Record<string, Messages> = { pt };

export function t(): Messages {
    return catalogs.pt;
}

export const statusLabel = (status: string): string => t().statusLabels[status] ?? status;
