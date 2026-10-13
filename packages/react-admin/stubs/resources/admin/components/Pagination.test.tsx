import { renderToStaticMarkup } from 'react-dom/server';
import { describe, expect, it } from 'vitest';
import { Pagination } from './Pagination';

const render = (page: number, lastPage: number) =>
    renderToStaticMarkup(<Pagination page={page} lastPage={lastPage} total={42} onChange={() => undefined} />);

describe('Pagination', () => {
    it('com uma só página mostra apenas o total', () => {
        const html = render(1, 1);
        expect(html).toContain('42');
        expect(html).not.toContain('<button');
    });

    it('na primeira página desactiva "Anterior"', () => {
        const [previous, next] = render(1, 3).split('</button>');
        expect(previous).toContain('disabled=""');
        expect(next).not.toContain('disabled=""');
    });

    it('na última página desactiva "Seguinte"', () => {
        const [previous, next] = render(3, 3).split('</button>');
        expect(previous).not.toContain('disabled=""');
        expect(next).toContain('disabled=""');
    });
});
