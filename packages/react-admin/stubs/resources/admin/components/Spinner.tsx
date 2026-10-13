import { t } from '../i18n';

export function Spinner() {
    return (
        <div role="status" className="flex items-center gap-2 p-6 text-slate-500">
            <span className="size-4 animate-spin rounded-full border-2 border-slate-300 border-t-indigo-600" />
            {t().loading}
        </div>
    );
}
