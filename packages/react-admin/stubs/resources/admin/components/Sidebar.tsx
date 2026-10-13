import { NavLink } from 'react-router';
import { csrfToken } from '../api';
import { getConfig } from '../config';
import { t } from '../i18n';

const link = ({ isActive }: { isActive: boolean }) =>
    `block rounded px-3 py-2 ${isActive ? 'bg-indigo-600 text-white' : 'text-slate-700 hover:bg-slate-200'}`;

export function Sidebar() {
    const { user } = getConfig();

    return (
        <aside className="flex w-56 shrink-0 flex-col gap-1 border-r bg-white p-3">
            <p className="px-3 pb-3 text-lg font-semibold">{t().appName}</p>
            <NavLink to="/admin" end className={link}>{t().dashboard}</NavLink>
            <NavLink to="/admin/leads" className={link}>{t().leads}</NavLink>
            <div className="mt-auto border-t pt-3">
                {user.name && <p className="truncate px-3 text-sm text-slate-500">{user.name}</p>}
                {/* O logout é um POST normal (sessão): a resposta redirecciona para fora da SPA. */}
                <form method="POST" action="/logout">
                    <input type="hidden" name="_token" value={csrfToken()} />
                    <button type="submit" className="w-full rounded px-3 py-2 text-left text-slate-700 hover:bg-slate-200">
                        {t().logout}
                    </button>
                </form>
            </div>
        </aside>
    );
}
