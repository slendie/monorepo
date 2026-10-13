import { useCallback } from 'react';
import { api } from '../api';
import type { Lead } from '../types';
import { useRequest } from './useRequest';

export function useLead(id: string) {
    const request = useRequest(() => api<{ data: Lead }>(`/leads/${id}`).then((r) => r.data), [id]);
    const { data, setData } = request;

    /** Actualização optimista: mostra já o novo valor e volta atrás se o servidor recusar. */
    const update = useCallback(
        async (changes: Partial<Pick<Lead, 'status' | 'notes'>>) => {
            if (!data) {
                return;
            }

            setData({ ...data, ...changes });

            try {
                const response = await api<{ data: Lead }>(`/leads/${id}`, { method: 'PATCH', body: changes });
                setData(response.data);
            } catch (error) {
                setData(data);
                throw error;
            }
        },
        [data, id, setData],
    );

    const remove = useCallback(() => api<void>(`/leads/${id}`, { method: 'DELETE' }), [id]);

    return { ...request, update, remove };
}
