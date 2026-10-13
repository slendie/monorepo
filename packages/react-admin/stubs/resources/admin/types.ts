export interface Lead {
    id: number;
    name: string;
    email: string;
    phone: string | null;
    message: string | null;
    source: string | null;
    status: string;
    notes: string | null;
    created_at: string;
    updated_at: string;
}

export interface Page<T> {
    data: T[];
    total: number;
    per_page: number;
    current_page: number;
    last_page: number;
    from: number | null;
    to: number | null;
}

export interface Stats {
    total: number;
    by_status: Record<string, number>;
    last_7_days: { date: string; count: number }[];
}

export interface LeadFilters {
    q: string;
    status: string;
    page: number;
}
