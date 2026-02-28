// =============================================
// historico.js - Histórico de mensagens enviadas
// Utiliza jQuery para manipulação do DOM e
// localStorage para persistência dos dados
// =============================================

$(document).ready(function () {

  // Renderiza o histórico ao carregar a página
  renderizarHistorico();

  // Evento para limpar o histórico
  $('#btn-limpar-historico').on('click', function () {
    swal({
      title: 'Tem certeza?',
      text: 'O histórico de mensagens será apagado permanentemente.',
      icon: 'warning',
      buttons: {
        cancel: { text: 'Cancelar', value: null, visible: true },
        confirm: { text: 'Apagar', value: true, visible: true, className: 'btn-danger' }
      }
    }).then(function (confirmado) {
      if (confirmado) {
        localStorage.removeItem('historicoMensagens');
        renderizarHistorico();
        swal('Pronto!', 'Histórico apagado com sucesso.', 'success');
      }
    });
  });

});

/**
 * Salva uma nova mensagem no localStorage.
 * Chamada externamente pelo mail.js após envio bem-sucedido.
 */
function salvarNoHistorico(nome, email, mensagem) {
  var historico = obterHistorico();

  var novoRegistro = {
    id: Date.now(),
    nome: nome,
    email: email,
    trecho: mensagem.length > 60 ? mensagem.substring(0, 60) + '...' : mensagem,
    dataHora: new Date().toLocaleString('pt-BR')
  };

  historico.unshift(novoRegistro); // Insere no início (mais recente primeiro)
  localStorage.setItem('historicoMensagens', JSON.stringify(historico));
  renderizarHistorico();
}

/**
 * Recupera o histórico do localStorage.
 */
function obterHistorico() {
  var dados = localStorage.getItem('historicoMensagens');
  return dados ? JSON.parse(dados) : [];
}

/**
 * Renderiza a tabela de histórico no DOM usando jQuery.
 */
function renderizarHistorico() {
  var historico = obterHistorico();
  var $secao = $('#secao-historico');
  var $tbody = $('#historico-tbody');
  var $contador = $('#historico-contador');

  $tbody.empty(); // Limpa as linhas anteriores

  if (historico.length === 0) {
    $secao.hide();
    return;
  }

  $secao.show();
  $contador.text(historico.length + (historico.length === 1 ? ' mensagem enviada' : ' mensagens enviadas'));

  $.each(historico, function (index, item) {
    var $linha = $('<tr>').addClass(index % 2 === 0 ? 'linha-par' : 'linha-impar');

    $linha.append($('<td>').text(item.nome));
    $linha.append($('<td>').text(item.email));
    $linha.append($('<td>').text(item.trecho));
    $linha.append($('<td>').text(item.dataHora));

    $tbody.append($linha);
  });
}
