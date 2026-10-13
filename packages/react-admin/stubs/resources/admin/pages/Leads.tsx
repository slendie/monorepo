import { useCallback } from 'react';
import { Link, useSearchParams } from 'react-router';
import { Empty, ErrorState } from '../components/Empty';
import { Pagination } from '../components/Pagination';
import { SearchInput } from '../components/SearchInput';
import { Spinner } from '../components/Spinner';
import { StatusBadge } from '../components/StatusBadge';
import { Table, type Column } from '../components/Table';
import { getConfig } from '../config';
import { useLeads } from '../hooks/useLeads';
import { statusLabel, t } from '../i18n';
import type { Lead } from '../types';

const columns: Column<Lead>[] = [
    {
        key: 'name',
        header: t().name,
        render: (lead) => (
            <Link to={`/admin/leads/${lead.id}`} className="font-medium text-indigo-700 hover:underline">
                {lead.name}
            </Link>
        ),
    },
    { key: 'email', header: t().email, render: (lead) => lead.email },
    { key: 'status', header: t().status, render: (lead) => <StatusBadge status={lead.status} /> },
    { key: 'created_at', header: t().createdAt, render: (lead) => new Date(lead.created_at).toLocaleDateString('pt-PT') },
];

/** Filtros e página vivem no URL (?q=&status=&page=): o botão "voltar" e os links directos funcionam. */
export function Leads() {
    const [params, setParams] = useSearchParams();
    const filters = { q: params.get('q') ?? '', status: params.get('status') ?? '', page: Number(params.get('page') ?? 1) || 1 };
    const { data, loading, error, reload } = useLeads(filters);

    const update = useCallback(
        (changes: Partial<typeof filters>) => {
            const next = { ...filters, page: 1, ...changes };
            const search = new URLSearchParams();
            if (next.q) search.set('q', next.q);
            if (next.status) search.set('status', next.status);
            if (next.page > 1) search.set('page', String(next.page));
            setParams(search);
        },
        // eslint-disable-next-line react-hooks/exhaustive-deps
        [filters.q, filters.status, filters.page, setParams],
    );

    const onSearch = useCallback((q: string) => update({ q }), [update]);

    return (
        <div className="space-y-4">
            <h1 className="text-2xl font-semibold">{t().leads}</h1>

            <div className="flex flex-wrap gap-3">
                <SearchInput value={filters.q} onChange={onSearch} />
                <select
                    value={filters.status}
                    onChange={(event) => update({ status: event.target.value })}
                    aria-label={t().status}
                    className="rounded border border-slate-300 px-3 py-2"
                >
                    <option value="">{t().allStatuses}</option>
                    {getConfig().statuses.map((status) => (
                        <option key={status} value={status}>{statusLabel(status)}</option>
                    ))}
                </select>
            </div>

            <div className="rounded bg-white shadow-sm">
                {loading && !data ? (
                    <Spinner />
                ) : error ? (
                    <ErrorState message={error} onRetry={reload} />
                ) : data && data.data.length > 0 ? (
                    <>
                        <Table columns={columns} rows={data.data} rowKey={(lead) => lead.id} />
                        <Pagination page={data.current_page} lastPage={data.last_page} total={data.total} onChange={(page) => update({ page })} />
                    </>
                ) : (
                    <Empty />
                )}
            </div>
        </div>
    );
}
