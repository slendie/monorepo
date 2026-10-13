import { ErrorState } from '../components/Empty';
import { Spinner } from '../components/Spinner';
import { useStats } from '../hooks/useStats';
import { statusLabel, t } from '../i18n';

export function Dashboard() {
    const { data, loading, error, reload } = useStats();

    if (loading && !data) {
        return <Spinner />;
    }

    if (error || !data) {
        return <ErrorState message={error ?? undefined} onRetry={reload} />;
    }

    const max = Math.max(1, ...data.last_7_days.map((day) => day.count));

    return (
        <div className="space-y-6">
            <h1 className="text-2xl font-semibold">{t().dashboard}</h1>

            <section className="grid gap-4 sm:grid-cols-3 lg:grid-cols-6">
                <div className="rounded bg-white p-4 shadow-sm">
                    <p className="text-sm text-slate-500">{t().total}</p>
                    <p className="text-2xl font-semibold">{data.total}</p>
                </div>
                {Object.entries(data.by_status).map(([status, count]) => (
                    <div key={status} className="rounded bg-white p-4 shadow-sm">
                        <p className="text-sm text-slate-500">{statusLabel(status)}</p>
                        <p className="text-2xl font-semibold">{count}</p>
                    </div>
                ))}
            </section>

            <section className="rounded bg-white p-4 shadow-sm">
                <h2 className="mb-3 font-medium">{t().last7Days}</h2>
                <ul className="flex h-40 items-end gap-2">
                    {data.last_7_days.map((day) => (
                        <li key={day.date} className="flex flex-1 flex-col items-center justify-end gap-1">
                            <span className="text-xs text-slate-500">{day.count}</span>
                            <div className="w-full rounded-t bg-indigo-500" style={{ height: `${(day.count / max) * 100}%`, minHeight: 2 }} />
                            <span className="text-xs text-slate-400">{day.date.slice(5)}</span>
                        </li>
                    ))}
                </ul>
            </section>
        </div>
    );
}
