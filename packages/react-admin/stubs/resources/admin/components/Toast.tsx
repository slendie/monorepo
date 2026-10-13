import { createContext, useCallback, useContext, useState, type ReactNode } from 'react';

type Kind = 'success' | 'error';
interface ToastItem {
    id: number;
    kind: Kind;
    message: string;
}

const ToastContext = createContext<(message: string, kind?: Kind) => void>(() => undefined);

export const useToast = () => useContext(ToastContext);

export function ToastProvider({ children }: { children: ReactNode }) {
    const [items, setItems] = useState<ToastItem[]>([]);

    const push = useCallback((message: string, kind: Kind = 'success') => {
        const id = Date.now() + Math.random();
        setItems((current) => [...current, { id, kind, message }]);
        setTimeout(() => setItems((current) => current.filter((item) => item.id !== id)), 3500);
    }, []);

    return (
        <ToastContext.Provider value={push}>
            {children}
            <div aria-live="polite" className="fixed bottom-4 right-4 flex flex-col gap-2">
                {items.map((item) => (
                    <div
                        key={item.id}
                        className={`rounded px-4 py-2 text-sm text-white shadow ${item.kind === 'error' ? 'bg-red-600' : 'bg-slate-800'}`}
                    >
                        {item.message}
                    </div>
                ))}
            </div>
        </ToastContext.Provider>
    );
}
