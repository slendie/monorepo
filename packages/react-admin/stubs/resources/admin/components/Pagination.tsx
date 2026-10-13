import { t } from '../i18n';

interface Props {
    page: number;
    lastPage: number;
    total: number;
    onChange: (page: number) => void;
}

export function Pagination({ page, lastPage, total, onChange }: Props) {
    if (lastPage <= 1) {
        return <p className="p-3 text-sm text-slate-500">{total} {t().results}</p>;
    }

    return (
        <nav aria-label="Paginação" className="flex items-center justify-between gap-3 p-3 text-sm">
            <span className="text-slate-500">
                {t().page} {page} {t().of} {lastPage} · {total} {t().results}
            </span>
            <span className="flex gap-2">
                <button type="button" disabled={page <= 1} onClick={() => onChange(page - 1)} className="rounded border px-3 py-1 disabled:opacity-40">
                    {t().previous}
                </button>
                <button type="button" disabled={page >= lastPage} onClick={() => onChange(page + 1)} className="rounded border px-3 py-1 disabled:opacity-40">
                    {t().next}
                </button>
            </span>
        </nav>
    );
}
