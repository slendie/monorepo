import { useCallback, useEffect, useState } from 'react';

export interface RequestState<T> {
    data: T | null;
    loading: boolean;
    error: string | null;
    reload: () => void;
    setData: (data: T | null) => void;
}

/** GET com estados loading/erro; volta a pedir quando `deps` mudam. Ignora respostas de pedidos antigos. */
export function useRequest<T>(load: () => Promise<T>, deps: unknown[]): RequestState<T> {
    const [data, setData] = useState<T | null>(null);
    const [loading, setLoading] = useState(true);
    const [error, setError] = useState<string | null>(null);
    const [tick, setTick] = useState(0);

    useEffect(() => {
        let current = true;
        setLoading(true);
        setError(null);

        load()
            .then((result) => current && setData(result))
            .catch((e: Error) => current && setError(e.message))
            .finally(() => current && setLoading(false));

        return () => {
            current = false;
        };
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [...deps, tick]);

    const reload = useCallback(() => setTick((value) => value + 1), []);

    return { data, loading, error, reload, setData };
}
