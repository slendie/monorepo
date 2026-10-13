import { useEffect, useState } from 'react';
import { t } from '../i18n';

interface Props {
    value: string;
    onChange: (value: string) => void;
    delay?: number;
}

/** Campo de pesquisa com debounce: só chama `onChange` depois de `delay` ms sem escrever. */
export function SearchInput({ value, onChange, delay = 300 }: Props) {
    const [text, setText] = useState(value);

    useEffect(() => setText(value), [value]);

    useEffect(() => {
        if (text === value) {
            return;
        }

        const timer = setTimeout(() => onChange(text), delay);

        return () => clearTimeout(timer);
    }, [text, value, delay, onChange]);

    return (
        <input
            type="search"
            value={text}
            onChange={(event) => setText(event.target.value)}
            placeholder={t().search}
            aria-label={t().search}
            className="w-full rounded border border-slate-300 px-3 py-2 sm:w-72"
        />
    );
}
