import { useEffect, useState } from 'react';
import { Link, useNavigate, useParams } from 'react-router';
import { ErrorState } from '../components/Empty';
import { Spinner } from '../components/Spinner';
import { StatusBadge } from '../components/StatusBadge';
import { useToast } from '../components/Toast';
import { getConfig } from '../config';
import { useLead } from '../hooks/useLead';
import { statusLabel, t } from '../i18n';

export function LeadDetail() {
    const { id = '' } = useParams();
    const navigate = useNavigate();
    const toast = useToast();
    const { data: lead, loading, error, reload, update, remove } = useLead(id);
    const [notes, setNotes] = useState('');

    useEffect(() => setNotes(lead?.notes ?? ''), [lead?.notes]);

    if (loading && !lead) {
        return <Spinner />;
    }

    if (error || !lead) {
        return <ErrorState message={error ?? undefined} onRetry={reload} />;
    }

    const save = (changes: Parameters<typeof update>[0]) =>
        update(changes).then(
            () => toast(t().saved),
            (e: Error) => toast(e.message, 'error'),
        );

    const destroy = async () => {
        if (!window.confirm(t().confirmDelete)) {
            return;
        }

        try {
            await remove();
            toast(t().deleted);
            navigate('/admin/leads');
        } catch (e) {
            toast((e as Error).message, 'error');
        }
    };

    return (
        <div className="max-w-2xl space-y-4">
            <Link to="/admin/leads" className="text-sm text-indigo-700 hover:underline">← {t().back}</Link>

            <div className="space-y-4 rounded bg-white p-6 shadow-sm">
                <div className="flex items-start justify-between gap-3">
                    <div>
                        <h1 className="text-2xl font-semibold">{lead.name}</h1>
                        <a href={`mailto:${lead.email}`} className="text-indigo-700 hover:underline">{lead.email}</a>
                    </div>
                    <StatusBadge status={lead.status} />
                </div>

                <dl className="grid grid-cols-[8rem_1fr] gap-y-2 text-sm">
                    <dt className="text-slate-500">{t().phone}</dt><dd>{lead.phone ?? '—'}</dd>
                    <dt className="text-slate-500">{t().source}</dt><dd>{lead.source ?? '—'}</dd>
                    <dt className="text-slate-500">{t().createdAt}</dt><dd>{new Date(lead.created_at).toLocaleString('pt-PT')}</dd>
                    <dt className="text-slate-500">{t().message}</dt><dd className="whitespace-pre-wrap">{lead.message ?? '—'}</dd>
                </dl>

                <label className="block text-sm">
                    <span className="mb-1 block text-slate-500">{t().status}</span>
                    <select
                        value={lead.status}
                        onChange={(event) => save({ status: event.target.value })}
                        className="rounded border border-slate-300 px-3 py-2"
                    >
                        {getConfig().statuses.map((status) => (
                            <option key={status} value={status}>{statusLabel(status)}</option>
                        ))}
                    </select>
                </label>

                <label className="block text-sm">
                    <span className="mb-1 block text-slate-500">{t().notes}</span>
                    <textarea
                        value={notes}
                        onChange={(event) => setNotes(event.target.value)}
                        rows={5}
                        className="w-full rounded border border-slate-300 px-3 py-2"
                    />
                </label>

                <div className="flex justify-between">
                    <button
                        type="button"
                        disabled={notes === (lead.notes ?? '')}
                        onClick={() => save({ notes })}
                        className="rounded bg-indigo-600 px-4 py-2 text-white disabled:opacity-40"
                    >
                        {t().save}
                    </button>
                    <button type="button" onClick={destroy} className="rounded border border-red-300 px-4 py-2 text-red-700 hover:bg-red-50">
                        {t().delete}
                    </button>
                </div>
            </div>
        </div>
    );
}
