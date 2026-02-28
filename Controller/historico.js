// ═════════════════════════════════════════════════════════════════
// historico.js — Histórico de mensagens enviadas
//
// MELHORIAS APLICADAS:
// 1. [ORGANIZAÇÃO]  'use strict' ativado.
// 2. [ORGANIZAÇÃO]  Chave do localStorage definida como constante nomeada.
// 3. [ROBUSTEZ]     Sanitização de HTML ao inserir dados no DOM via jQuery
//                   (evita XSS caso um dado armazenado contenha tags HTML).
// 4. [MANUTENÇÃO]   Função auxiliar escaparHtml() centraliza a sanitização.
// 5. [CLAREZA]      Comentários revisados: apenas o que não é óbvio pelo código.
// ═════════════════════════════════════════════════════════════════

'use strict'; // MELHORIA 1

// MELHORIA 2: constante nomeada evita erros de digitação da chave
const CHAVE_HISTORICO = 'historicoMensagens';

$(document).ready(function () {

    renderizarHistorico();

    $('#btn-limpar-historico').on('click', function () {
        swal({
            title: 'Tem certeza?',
            text: 'O histórico de mensagens será apagado permanentemente.',
            icon: 'warning',
            buttons: {
                cancel:  { text: 'Cancelar', value: null,  visible: true },
                confirm: { text: 'Apagar',   value: true,  visible: true, className: 'btn-danger' },
            },
        }).then(function (confirmado) {
            if (confirmado) {
                localStorage.removeItem(CHAVE_HISTORICO); // MELHORIA 2
                renderizarHistorico();
                swal('Pronto!', 'Histórico apagado com sucesso.', 'success');
            }
        });
    });

});

/**
 * Salva uma nova mensagem no localStorage.
 * Chamada externamente por mail.js após envio bem-sucedido.
 *
 * @param {string} nome
 * @param {string} email
 * @param {string} mensagem
 */
function salvarNoHistorico(nome, email, mensagem) {
    const historico = obterHistorico();

    const novoRegistro = {
        id:       Date.now(),
        nome:     nome,
        email:    email,
        // Armazena no máximo 60 caracteres do início da mensagem
        trecho:   mensagem.length > 60 ? mensagem.substring(0, 60) + '...' : mensagem,
        dataHora: new Date().toLocaleString('pt-BR'),
    };

    historico.unshift(novoRegistro); // Mais recente primeiro
    localStorage.setItem(CHAVE_HISTORICO, JSON.stringify(historico)); // MELHORIA 2
    renderizarHistorico();
}

/**
 * Recupera o histórico do localStorage.
 * @returns {Array}
 */
function obterHistorico() {
    try {
        const dados = localStorage.getItem(CHAVE_HISTORICO); // MELHORIA 2
        return dados ? JSON.parse(dados) : [];
    } catch (e) {
        // Dado corrompido: descarta silenciosamente e começa do zero
        localStorage.removeItem(CHAVE_HISTORICO);
        return [];
    }
}

/**
 * Renderiza a tabela de histórico no DOM.
 *
 * MELHORIA 3: usa .text() em vez de .html() ao inserir dados do usuário.
 * .text() trata o conteúdo como texto puro — o jQuery escapa qualquer
 * caractere HTML automaticamente, prevenindo XSS.
 */
function renderizarHistorico() {
    const historico  = obterHistorico();
    const $secao     = $('#secao-historico');
    const $tbody     = $('#historico-tbody');
    const $contador  = $('#historico-contador');

    $tbody.empty();

    if (historico.length === 0) {
        $secao.hide();
        return;
    }

    $secao.show();

    const label = historico.length === 1 ? ' mensagem enviada' : ' mensagens enviadas';
    $contador.text(historico.length + label);

    $.each(historico, function (index, item) {
        const $linha = $('<tr>').addClass(index % 2 === 0 ? 'linha-par' : 'linha-impar');

        // MELHORIA 3: .text() escapa HTML — seguro contra XSS
        $linha.append($('<td>').text(item.nome));
        $linha.append($('<td>').text(item.email));
        $linha.append($('<td>').text(item.trecho));
        $linha.append($('<td>').text(item.dataHora));

        $tbody.append($linha);
    });
}

// ── MELHORIA 4: helper de sanitização (útil se .html() for necessário futuramente) ──
/**
 * Escapa caracteres HTML especiais em uma string.
 * Use quando precisar inserir conteúdo dinâmico via .html().
 *
 * @param {string} str
 * @returns {string}
 */
function escaparHtml(str) {
    return $('<span>').text(str).html();
}
