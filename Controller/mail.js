// ─────────────────────────────────────────────────────────────────
// mail.js  (versão com integração ao InterSystems Caché)
// 
// NOVO: ao sair do campo e-mail, o sistema consulta o banco Caché
// via buscar-paciente.php e pré-preenche o nome automaticamente
// caso o paciente já esteja cadastrado.
// ─────────────────────────────────────────────────────────────────

var emailUser = ''
let $salvarMail  = document.querySelector('#salvar-email')
let $enviarMail  = document.querySelector('#form-mail')
let $campoNome   = document.querySelector('#nome')
let $campoEmail  = document.querySelector('#correio')
let $badgeCache  = document.querySelector('#badge-cache')

// ── Validação de e-mail ───────────────────────────────────────────
function validarEmail(email) {
    let expressao = /^\w+([\.-]?\w+)*@\w+([\.-]?\w+)*(\.\w{2,4})+[a-zA-Z]{1,}$/;
    return expressao.exec(email) !== null;
}

// ── Modal para salvar o e-mail do destinatário ────────────────────
function salvarEmail() {
    swal({
        title: "Importante",
        text: "Digite o email do destinatário:",
        content: {
            element: "input",
            attributes: { type: "email", placeholder: "Endereço de email", style: "color: black;" }
        },
        buttons: {
            cancel: { text: "Cancelar", value: null, visible: true, closeModal: true },
            confirm: { text: "Salvar", value: true, visible: true, closeModal: true }
        }
    })
    .then((value) => {
        if (value === null) {
            swal('Error', 'Clique no botão Salvar Email e digite o seu email', 'error');
        } else if (value === '') {
            swal('Aviso', 'Não digitou o email. Não se preocupe, pode fazer isso mais tarde', 'warning');
        } else {
            if (!validarEmail(value)) {
                swal('Error', 'Digite um email válido', 'error');
            } else {
                swal('Perfeito', 'Email Salvo', 'success');
                emailUser = value;
            }
        }
    });
}

salvarEmail();

$salvarMail.addEventListener('click', () => salvarEmail());

// ── NOVO: busca o paciente no Caché ao sair do campo e-mail ───────
$campoEmail.addEventListener('blur', () => {
    const emailDigitado = $campoEmail.value.trim();

    if (!validarEmail(emailDigitado)) return;

    mostrarBadge('🔍 Buscando no Caché...', '#6c757d');

    const url = `/Model/buscar-paciente.php?email=${encodeURIComponent(emailDigitado)}`;

    fetch(url)
        .then(res => res.json())
        .then(data => {
            if (data.encontrado) {
                $campoNome.value = data.paciente.Nome;
                $campoNome.setAttribute('readonly', true);
                $campoNome.style.borderColor = '#28a745';
                mostrarBadge(
                    `✅ Paciente encontrado no ${data.fonte}: ${data.paciente.Nome}`,
                    '#28a745'
                );
            } else {
                $campoNome.removeAttribute('readonly');
                $campoNome.style.borderColor = '';
                mostrarBadge('🆕 Paciente não cadastrado. Será registrado após o envio.', '#ffc107');
            }
        })
        .catch(() => {
            mostrarBadge('⚠️ Não foi possível consultar o Caché.', '#dc3545');
        });
});

$campoEmail.addEventListener('input', () => {
    $campoNome.removeAttribute('readonly');
    $campoNome.style.borderColor = '';
    if ($badgeCache) $badgeCache.style.display = 'none';
});

// ── Helper: exibe o badge de status do Caché ─────────────────────
function mostrarBadge(texto, cor) {
    if (!$badgeCache) return;
    $badgeCache.textContent = texto;
    $badgeCache.style.background = cor;
    $badgeCache.style.display = 'inline-block';
}

// ── Envio do formulário ───────────────────────────────────────────
$enviarMail.addEventListener('submit', (e) => {
    e.preventDefault();

    if (!validarEmail(emailUser)) {
        swal('Error', 'Você colocou um email incorreto, digite um email correto', 'error');
        return;
    }

    let $nome     = document.querySelector('#nome').value;
    let $mensagem = document.querySelector('#mensagem').value;
    let $correio  = document.querySelector('#correio').value;

    if ($nome === '' || $mensagem === '' || $correio === '') {
        swal('Error', 'Preenche todos os campos, por favor!', 'error');
        return;
    }

    let formData = new FormData($enviarMail);
    formData.append('email', emailUser);

    const url = "/Model/mail.php";

    fetch(url, { method: 'POST', body: formData })
        .then(res => res.json())
        .then(data => {
            if (!data.error) {
                salvarNoHistorico($nome, $correio, $mensagem);

                const infoCache = data.cache_info ? `\n\n📋 Caché: ${data.cache_info}` : '';
                swal('Perfeito', data.mensagem + infoCache, 'success');

                document.querySelector('#nome').value     = '';
                document.querySelector('#mensagem').value = '';
                document.querySelector('#correio').value  = '';
                document.querySelector('#nome').removeAttribute('readonly');
                document.querySelector('#nome').style.borderColor = '';
                if ($badgeCache) $badgeCache.style.display = 'none';
            } else {
                swal('Sentimos muito', data.mensagem, 'warning');
            }
        });
});
