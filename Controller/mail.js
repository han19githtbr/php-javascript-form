// ─────────────────────────────────────────────────────────────────
// mail.js — Controller de envio de formulário e busca no Caché
//
// MELHORIAS APLICADAS:
// 1. [ORGANIZAÇÃO]  'use strict' ativado: erros silenciosos viram exceções.
// 2. [ORGANIZAÇÃO]  Constantes nomeadas substituem strings e números repetidos.
// 3. [ORGANIZAÇÃO]  Cada responsabilidade em função própria com nome descritivo.
// 4. [ROBUSTEZ]     Feedback visual no botão de submit durante o envio (loading).
// 5. [PADRÃO]       Atualizado para trabalhar com a resposta { sucesso } de mail.php.
// 6. [MANUTENÇÃO]   URL dos endpoints definida em um único lugar (ENDPOINTS).
// ─────────────────────────────────────────────────────────────────

'use strict'; // MELHORIA 1

// ── Constantes ────────────────────────────────────────────────────
// MELHORIA 2: centraliza strings e valores "mágicos"
const ENDPOINTS = {
    enviarEmail:    '/Model/mail.php',
    buscarPaciente: '/Model/buscar-paciente.php',
};

const EMAIL_REGEX = /^\w+([\.-]?\w+)*@\w+([\.-]?\w+)*(\.\w{2,4})+[a-zA-Z]{1,}$/;

// ── Seleção de elementos do DOM ───────────────────────────────────
const $salvarMail = document.querySelector('#salvar-email');
const $enviarMail = document.querySelector('#form-mail');
const $campoNome  = document.querySelector('#nome');
const $campoEmail = document.querySelector('#correio');
const $badgeCache = document.querySelector('#badge-cache');
const $btnSubmit  = document.querySelector('[data-button]');

let emailDestino = '';

// ── Inicialização ─────────────────────────────────────────────────
solicitarEmailDestino();
$salvarMail.addEventListener('click', solicitarEmailDestino);

// ── MELHORIA 3: Lógica de busca no Caché extraída para função ─────
$campoEmail.addEventListener('blur', () => {
    const email = $campoEmail.value.trim();
    if (!validarEmail(email)) return;

    atualizarBadge('🔍 Buscando no Caché...', '#6c757d');

    const url = `${ENDPOINTS.buscarPaciente}?email=${encodeURIComponent(email)}`;

    fetch(url)
        .then(res => res.json())
        .then(data => {
            if (data.encontrado) {
                preencherNomeComPaciente(data.paciente.Nome, data.fonte);
            } else {
                limparPreenchimentoNome();
                atualizarBadge('🆕 Paciente não cadastrado. Será registrado após o envio.', '#ffc107');
            }
        })
        .catch(() => {
            atualizarBadge('⚠️ Não foi possível consultar o Caché.', '#dc3545');
        });
});

// Ao editar o campo de e-mail, desfaz o preenchimento automático do nome
$campoEmail.addEventListener('input', limparPreenchimentoNome);

// ── Envio do formulário ───────────────────────────────────────────
$enviarMail.addEventListener('submit', (e) => {
    e.preventDefault();

    if (!validarEmail(emailDestino)) {
        swal('Erro', 'Salve um e-mail de destinatário válido antes de enviar.', 'error');
        return;
    }

    const nome     = $campoNome.value.trim();
    const mensagem = document.querySelector('#mensagem').value.trim();
    const correio  = $campoEmail.value.trim();

    if (!nome || !mensagem || !correio) {
        swal('Erro', 'Preencha todos os campos antes de enviar.', 'error');
        return;
    }

    const formData = new FormData($enviarMail);
    formData.append('email', emailDestino);

    // MELHORIA 4: feedback visual durante o envio
    definirEstadoEnviando(true);

    fetch(ENDPOINTS.enviarEmail, { method: 'POST', body: formData })
        .then(res => res.json())
        // MELHORIA 5: usa "sucesso" conforme o padrão do back-end melhorado
        .then(data => {
            if (data.sucesso) {
                salvarNoHistorico(nome, correio, mensagem);

                const infoCache = data.cache_info ? `\n\n📋 Caché: ${data.cache_info}` : '';
                swal('Perfeito', data.mensagem + infoCache, 'success');
                limparFormulario();
            } else {
                swal('Erro ao enviar', data.mensagem, 'warning');
            }
        })
        .catch(() => {
            swal('Erro', 'Não foi possível conectar ao servidor. Tente novamente.', 'error');
        })
        .finally(() => {
            // MELHORIA 4: restaura o botão independente de sucesso ou erro
            definirEstadoEnviando(false);
        });
});


// ══════════════════════════════════════════════════════════════════
// FUNÇÕES AUXILIARES
// ══════════════════════════════════════════════════════════════════

/**
 * Valida o formato do e-mail.
 * @param {string} email
 * @returns {boolean}
 */
function validarEmail(email) {
    return EMAIL_REGEX.test(email);
}

/**
 * Abre o modal do SweetAlert para que o usuário informe o e-mail destinatário.
 * MELHORIA 3: extraído do código inline para função reutilizável.
 */
function solicitarEmailDestino() {
    swal({
        title: 'Importante',
        text: 'Digite o e-mail do destinatário:',
        content: {
            element: 'input',
            attributes: {
                type: 'email',
                placeholder: 'Endereço de e-mail',
                style: 'color: black;',
            },
        },
        buttons: {
            cancel:  { text: 'Cancelar', value: null,  visible: true, closeModal: true },
            confirm: { text: 'Salvar',   value: true,  visible: true, closeModal: true },
        },
    }).then((valor) => {
        if (valor === null) {
            swal('Atenção', 'Clique em "Salvar Destinatário" e informe um e-mail.', 'error');
        } else if (valor === '') {
            swal('Aviso', 'Nenhum e-mail digitado. Você pode fazer isso mais tarde.', 'warning');
        } else if (!validarEmail(valor)) {
            swal('Erro', 'Digite um e-mail válido.', 'error');
        } else {
            swal('Pronto', 'E-mail do destinatário salvo!', 'success');
            emailDestino = valor;
        }
    });
}

/**
 * Preenche o campo de nome com os dados vindos do Caché e o bloqueia para edição.
 * @param {string} nome
 * @param {string} fonte  — ex.: "SQLite (desenvolvimento)"
 */
function preencherNomeComPaciente(nome, fonte) {
    $campoNome.value = nome;
    $campoNome.setAttribute('readonly', true);
    $campoNome.style.borderColor = '#28a745';
    atualizarBadge(`✅ Paciente encontrado no ${fonte}: ${nome}`, '#28a745');
}

/**
 * Remove o preenchimento automático do nome e restaura o campo para edição manual.
 */
function limparPreenchimentoNome() {
    $campoNome.removeAttribute('readonly');
    $campoNome.style.borderColor = '';
    if ($badgeCache) $badgeCache.style.display = 'none';
}

/**
 * Exibe e estiliza o badge de status da consulta ao Caché.
 * @param {string} texto
 * @param {string} cor   — valor CSS de cor de fundo
 */
function atualizarBadge(texto, cor) {
    if (!$badgeCache) return;
    $badgeCache.textContent     = texto;
    $badgeCache.style.background  = cor;
    $badgeCache.style.display     = 'inline-block';
}

/**
 * Habilita/desabilita o botão de envio com texto de feedback.
 * MELHORIA 4: evita duplo clique e comunica o estado ao usuário.
 * @param {boolean} enviando
 */
function definirEstadoEnviando(enviando) {
    if (!$btnSubmit) return;
    $btnSubmit.disabled    = enviando;
    $btnSubmit.textContent = enviando ? 'Enviando...' : 'Enviar Mensagem →';
}

/**
 * Limpa todos os campos do formulário após envio bem-sucedido.
 */
function limparFormulario() {
    $campoNome.value = '';
    $campoNome.removeAttribute('readonly');
    $campoNome.style.borderColor = '';
    document.querySelector('#mensagem').value = '';
    $campoEmail.value = '';
    if ($badgeCache) $badgeCache.style.display = 'none';
}
