import { Outlet } from 'react-router';
import { Sidebar } from './Sidebar';

export function Layout() {
    return (
        <div className="flex min-h-screen bg-slate-100 text-slate-900">
            <Sidebar />
            <main className="min-w-0 flex-1 p-6">
                <Outlet />
            </main>
        </div>
    );
}
