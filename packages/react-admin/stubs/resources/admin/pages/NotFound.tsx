import { Link } from 'react-router';
import { t } from '../i18n';

export function NotFound() {
    return (
        <div className="space-y-3 p-8 text-center">
            <p className="text-slate-600">{t().notFound}</p>
            <Link to="/admin" className="text-indigo-700 hover:underline">{t().dashboard}</Link>
        </div>
    );
}
