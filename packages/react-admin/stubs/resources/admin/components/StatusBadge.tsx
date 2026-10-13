import { statusLabel } from '../i18n';

const colours: Record<string, string> = {
    new: 'bg-blue-100 text-blue-800',
    contacted: 'bg-amber-100 text-amber-800',
    qualified: 'bg-violet-100 text-violet-800',
    won: 'bg-green-100 text-green-800',
    lost: 'bg-slate-200 text-slate-700',
};

export function StatusBadge({ status }: { status: string }) {
    return (
        <span className={`inline-block rounded-full px-2 py-0.5 text-xs font-medium ${colours[status] ?? 'bg-slate-100 text-slate-700'}`}>
            {statusLabel(status)}
        </span>
    );
}
