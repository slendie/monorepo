import { RouterProvider } from 'react-router';
import { ToastProvider } from './components/Toast';
import { router } from './router';

/** Raiz da SPA (usada por main.tsx e útil em testes). */
export function App() {
    return (
        <ToastProvider>
            <RouterProvider router={router} />
        </ToastProvider>
    );
}
