<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Praticando JS e PHP</title>

  <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.0/css/bootstrap.min.css">
  <link rel="stylesheet" href="assets/css/main.css">

  <style>
    /* ── Badge de status do Caché ── */
    #badge-cache {
      display: none;
      margin-top: 8px;
      padding: 6px 12px;
      border-radius: 4px;
      color: #fff;
      font-size: 0.85rem;
      font-weight: 500;
      transition: background 0.3s;
    }

    /* ── Seção de Histórico ── */
    #secao-historico {
      display: none;
      margin: 40px auto;
      max-width: 860px;
      padding: 0 16px;
    }
    #secao-historico h2 { color: #eee; font-size: 1.4rem; margin-bottom: 4px; }
    #historico-contador { color: #aaa; font-size: 0.9rem; margin-bottom: 12px; display: block; }
    #tabela-historico { width: 100%; border-collapse: collapse; background: #2c2f33; border-radius: 8px; overflow: hidden; box-shadow: 0 2px 12px rgba(0,0,0,0.4); }
    #tabela-historico thead { background: #1a1d21; }
    #tabela-historico thead th { color: #80bdff; padding: 12px 16px; text-align: left; font-size: 0.85rem; text-transform: uppercase; letter-spacing: 0.05em; }
    #tabela-historico tbody td { color: #ddd; padding: 10px 16px; font-size: 0.9rem; border-top: 1px solid #3a3d42; }
    .linha-par { background: #2c2f33; }
    .linha-impar { background: #26292d; }
    #tabela-historico tbody tr:hover { background: #363a40; transition: background 0.2s; }
    #btn-limpar-historico { margin-top: 14px; background: transparent; border: 1px solid #dc3545; color: #dc3545; padding: 6px 18px; border-radius: 4px; font-size: 0.85rem; cursor: pointer; transition: all 0.2s; }
    #btn-limpar-historico:hover { background: #dc3545; color: #fff; }
  </style>
</head>
<body>
  <button class="btn btn-success salvar-email" id="salvar-email">Salvar Email</button>

  <div class="card-body">
    <h1 class="card-title">Formulário de Contato com JS e PHP</h1>
    <p id="card-text"></p>
  </div>

  <div class="contato">
    <h2>Contato</h2>
    <form id="form-mail">
      <label for="nome">Nome</label>
      <input type="text" class="form-control" id="nome" placeholder="Digite o teu nome..." name="nome" required />

      <label for="correio">E-mail</label>
      <input type="email" class="form-control" id="correio" placeholder="Digite o teu email..." name="correio" required />
      <!--
        O badge abaixo aparece automaticamente ao sair do campo e-mail.
        Ele consulta o InterSystems Caché (ou SQLite em dev) e exibe:
          ✅ verde  → paciente já cadastrado (nome pré-preenchido)
          🆕 amarelo → paciente novo (será cadastrado ao enviar)
          ⚠️ vermelho → falha na consulta ao Caché
      -->
      <span id="badge-cache"></span>

      <label for="mensagem">Mensagem</label>
      <textarea name="mensagem" id="mensagem" class="form-control" placeholder="Digite a tua mensagem..." required></textarea>
      <button type="submit" data-button>Enviar Mensagem</button>
    </form>
  </div>

  <!-- Histórico de envios (jQuery) -->
  <section id="secao-historico">
    <h2>📋 Histórico de Envios</h2>
    <span id="historico-contador"></span>
    <table id="tabela-historico">
      <thead>
        <tr>
          <th>Nome</th>
          <th>E-mail</th>
          <th>Mensagem</th>
          <th>Data / Hora</th>
        </tr>
      </thead>
      <tbody id="historico-tbody"></tbody>
    </table>
    <button id="btn-limpar-historico">🗑 Limpar Histórico</button>
  </section>

  <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/sweetalert/2.1.2/sweetalert.min.js"></script>
  <script src="Controller/mail.js"></script>
  <script src="Controller/animacao-texto.js"></script>
  <script src="Controller/historico.js"></script>
</body>
</html>
