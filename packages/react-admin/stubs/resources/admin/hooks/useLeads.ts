import { api } from '../api';
import type { Lead, LeadFilters, Page } from '../types';
import { useRequest } from './useRequest';

export function useLeads(filters: LeadFilters) {
    return useRequest(
        () => api<Page<Lead>>('/leads', { query: { q: filters.q, status: filters.status, page: filters.page } }),
        [filters.q, filters.status, filters.page],
    );
}
