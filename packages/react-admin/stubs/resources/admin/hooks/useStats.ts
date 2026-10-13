import { api } from '../api';
import type { Stats } from '../types';
import { useRequest } from './useRequest';

export const useStats = () => useRequest(() => api<Stats>('/stats'), []);
