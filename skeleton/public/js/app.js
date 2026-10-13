// JS do site (vanilla, sem build). Envia o formulário de contacto por fetch; sem JavaScript, o envio normal funciona na mesma.
(() => {
    const form = document.querySelector('[data-contacto]');

    if (!form || !window.fetch) {
        return;
    }

    const estado = form.querySelector('[data-estado]');
    const botao = form.querySelector('button[type="submit"]');

    form.addEventListener('submit', async (evento) => {
        evento.preventDefault();
        form.querySelectorAll('.erro').forEach((el) => el.remove());
        estado.className = 'estado';
        estado.textContent = '';
        botao.disabled = true;

        try {
            const resposta = await fetch(form.action, {
                method: 'POST',
                body: new FormData(form),
                headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
            });

            if (resposta.status === 201) {
                window.location.href = form.dataset.obrigado || '/obrigado';
                return;
            }

            const dados = await resposta.json().catch(() => ({}));

            if (resposta.status === 422 && dados.errors) {
                for (const [campo, mensagens] of Object.entries(dados.errors)) {
                    const input = form.querySelector(`[name="${campo}"]`);
                    const erro = document.createElement('p');
                    erro.className = 'erro';
                    erro.textContent = mensagens[0];
                    (input?.closest('.campo') ?? form).append(erro);
                }
                estado.className = 'estado falhou';
                estado.textContent = 'Corrija os campos assinalados.';
            } else {
                throw new Error('falhou');
            }
        } catch {
            estado.className = 'estado falhou';
            estado.textContent = 'Não foi possível enviar. Tente novamente.';
        } finally {
            botao.disabled = false;
        }
    });
})();
