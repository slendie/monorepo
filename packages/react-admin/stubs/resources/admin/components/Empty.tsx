import type { ReactNode } from 'react';
import { t } from '../i18n';

export function Empty({ children }: { children?: ReactNode }) {
    return <p className="p-8 text-center text-slate-500">{children ?? t().empty}</p>;
}

export function ErrorState({ message, onRetry }: { message?: string; onRetry?: () => void }) {
    return (
        <div role="alert" className="p-8 text-center text-red-700">
            <p>{message ?? t().error}</p>
            {onRetry && (
                <button type="button" onClick={onRetry} className="mt-3 rounded border border-red-300 px-3 py-1 hover:bg-red-50">
                    {t().retry}
                </button>
            )}
        </div>
    );
}
