import { createBrowserRouter } from 'react-router';
import { Layout } from './components/Layout';
import { Dashboard } from './pages/Dashboard';
import { LeadDetail } from './pages/LeadDetail';
import { Leads } from './pages/Leads';
import { NotFound } from './pages/NotFound';

export const router = createBrowserRouter([
    {
        path: '/admin',
        element: <Layout />,
        children: [
            { index: true, element: <Dashboard /> },
            { path: 'leads', element: <Leads /> },
            { path: 'leads/:id', element: <LeadDetail /> },
            { path: '*', element: <NotFound /> },
        ],
    },
]);
