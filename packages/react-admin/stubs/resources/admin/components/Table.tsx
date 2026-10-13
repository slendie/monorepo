import type { ReactNode } from 'react';

export interface Column<T> {
    key: string;
    header: string;
    render: (row: T) => ReactNode;
    className?: string;
}

interface Props<T> {
    columns: Column<T>[];
    rows: T[];
    rowKey: (row: T) => string | number;
}

export function Table<T>({ columns, rows, rowKey }: Props<T>) {
    return (
        <div className="overflow-x-auto">
            <table className="w-full text-left text-sm">
                <thead className="border-b bg-slate-50 text-slate-600">
                    <tr>
                        {columns.map((column) => (
                            <th key={column.key} scope="col" className={`px-4 py-2 font-medium ${column.className ?? ''}`}>
                                {column.header}
                            </th>
                        ))}
                    </tr>
                </thead>
                <tbody className="divide-y">
                    {rows.map((row) => (
                        <tr key={rowKey(row)} className="hover:bg-slate-50">
                            {columns.map((column) => (
                                <td key={column.key} className={`px-4 py-2 ${column.className ?? ''}`}>
                                    {column.render(row)}
                                </td>
                            ))}
                        </tr>
                    ))}
                </tbody>
            </table>
        </div>
    );
}
