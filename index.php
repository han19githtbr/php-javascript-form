<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Contato — Sistema de Mensagens</title>

  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=DM+Serif+Display:ital@0;1&family=DM+Sans:wght@300;400;500;600&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.0/css/bootstrap.min.css">
  <link rel="stylesheet" href="assets/css/main.css">

  <style>
    /* ═══════════════════════════════════════════════
       TOKENS
    ═══════════════════════════════════════════════ */
    :root {
      --bg:          #0d0f12;
      --surface:     #141619;
      --surface-2:   #1c1f24;
      --border:      rgba(255,255,255,0.07);
      --border-focus:rgba(99,179,237,0.5);
      --text:        #e8eaf0;
      --text-muted:  #6b7280;
      --accent:      #63b3ed;
      --accent-dim:  rgba(99,179,237,0.12);
      --accent-glow: rgba(99,179,237,0.25);
      --green:       #68d391;
      --yellow:      #f6e05e;
      --red:         #fc8181;
      --radius:      12px;
      --radius-lg:   20px;
      --shadow:      0 8px 40px rgba(0,0,0,0.5);
      --transition:  0.22s cubic-bezier(0.4,0,0.2,1);
    }

    /* ═══════════════════════════════════════════════
       RESET & BASE
    ═══════════════════════════════════════════════ */
    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

    html { scroll-behavior: smooth; }

    body {
      background: var(--bg);
      color: var(--text);
      font-family: 'DM Sans', sans-serif;
      font-weight: 400;
      min-height: 100vh;
      overflow-x: hidden;
    }

    /* ── Background mesh ── */
    body::before {
      content: '';
      position: fixed;
      inset: 0;
      background:
        radial-gradient(ellipse 80% 50% at 20% -10%, rgba(99,179,237,0.08) 0%, transparent 60%),
        radial-gradient(ellipse 60% 40% at 80% 110%, rgba(104,211,145,0.05) 0%, transparent 55%);
      pointer-events: none;
      z-index: 0;
    }

    /* ═══════════════════════════════════════════════
       HEADER
    ═══════════════════════════════════════════════ */
    .page-header {
      position: relative;
      z-index: 1;
      padding: 56px 24px 0;
      text-align: center;
      animation: fadeDown 0.7s ease both;
    }

    .page-header .eyebrow {
      display: inline-flex;
      align-items: center;
      gap: 8px;
      font-size: 0.72rem;
      font-weight: 600;
      letter-spacing: 0.18em;
      text-transform: uppercase;
      color: var(--accent);
      background: var(--accent-dim);
      border: 1px solid rgba(99,179,237,0.2);
      padding: 5px 14px;
      border-radius: 100px;
      margin-bottom: 20px;
    }

    .page-header .eyebrow::before {
      content: '';
      width: 6px; height: 6px;
      background: var(--accent);
      border-radius: 50%;
      animation: pulse 2s ease infinite;
    }

    .page-header h1 {
      font-family: 'DM Serif Display', serif;
      font-size: clamp(2rem, 5vw, 3.2rem);
      font-weight: 400;
      line-height: 1.15;
      color: #fff;
      margin-bottom: 12px;
    }

    .page-header h1 em {
      font-style: italic;
      color: var(--accent);
    }

    .page-header p {
      color: var(--text-muted);
      font-size: 1rem;
      max-width: 420px;
      margin: 0 auto 36px;
      line-height: 1.65;
    }

    /* ── Salvar email btn ── */
    #salvar-email {
      position: fixed !important;
      top: 20px !important;
      right: 20px !important;
      left: auto !important;
      width: auto !important;
      max-width: 220px !important;
      display: inline-flex !important;
      align-items: center;
      white-space: nowrap;
      z-index: 1000;
      background: var(--accent-dim) !important;
      border: 1px solid rgba(99,179,237,0.3) !important;
      color: var(--accent) !important;
      font-family: 'DM Sans', sans-serif !important;
      font-size: 0.82rem !important;
      font-weight: 600 !important;
      letter-spacing: 0.04em;
      padding: 9px 18px !important;
      border-radius: 100px !important;
      cursor: pointer;
      transition: var(--transition);
      backdrop-filter: blur(12px);
      box-shadow: none !important;
    }

    #salvar-email:hover {
      background: var(--accent) !important;
      color: #0d0f12 !important;
      box-shadow: 0 0 20px var(--accent-glow) !important;
      transform: translateY(-1px);
    }

    /* ═══════════════════════════════════════════════
       MAIN LAYOUT
    ═══════════════════════════════════════════════ */
    .page-body {
      position: relative;
      z-index: 1;
      display: grid;
      grid-template-columns: 1fr;
      gap: 24px;
      max-width: 560px;
      margin: 0 auto;
      padding: 0 20px 60px;
    }

    @media (min-width: 900px) {
      .page-body {
        grid-template-columns: 1fr 1fr;
        max-width: 1020px;
        gap: 32px;
        align-items: start;
      }
    }

    /* ═══════════════════════════════════════════════
       CARD
    ═══════════════════════════════════════════════ */
    .card-contato {
      background: var(--surface);
      border: 1px solid var(--border);
      border-radius: var(--radius-lg);
      padding: 36px 32px;
      box-shadow: var(--shadow);
      animation: fadeUp 0.7s 0.1s ease both;
    }

    .card-contato h2 {
      font-family: 'DM Serif Display', serif;
      font-size: 1.5rem;
      font-weight: 400;
      color: #fff;
      margin-bottom: 24px;
      display: flex;
      align-items: center;
      gap: 10px;
    }

    .card-contato h2 .icon {
      width: 34px; height: 34px;
      background: var(--accent-dim);
      border: 1px solid rgba(99,179,237,0.2);
      border-radius: 8px;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 1rem;
    }

    /* ═══════════════════════════════════════════════
       FORM
    ═══════════════════════════════════════════════ */
    .form-group-custom {
      margin-bottom: 18px;
    }

    .form-group-custom label {
      display: block;
      font-size: 0.78rem;
      font-weight: 600;
      letter-spacing: 0.06em;
      text-transform: uppercase;
      color: var(--text-muted);
      margin-bottom: 8px;
    }

    .form-group-custom input,
    .form-group-custom textarea {
      width: 100%;
      background: var(--surface-2);
      border: 1px solid var(--border);
      border-radius: var(--radius);
      color: var(--text);
      font-family: 'DM Sans', sans-serif;
      font-size: 0.95rem;
      padding: 12px 16px;
      transition: var(--transition);
      outline: none;
      -webkit-appearance: none;
    }

    .form-group-custom input::placeholder,
    .form-group-custom textarea::placeholder {
      color: #3a3f4a;
    }

    .form-group-custom input:focus,
    .form-group-custom textarea:focus {
      border-color: var(--accent);
      box-shadow: 0 0 0 3px var(--accent-glow);
      background: #181b20;
    }

    .form-group-custom textarea {
      resize: vertical;
      min-height: 120px;
      line-height: 1.6;
    }

    /* ── Input validado pelo Cache ── */
    .form-group-custom input.cache-ok {
      border-color: var(--green);
      box-shadow: 0 0 0 3px rgba(104,211,145,0.15);
    }

    /* ── Badge ── */
    #badge-cache {
      display: none;
      margin-top: 8px;
      padding: 7px 13px;
      border-radius: 8px;
      color: #fff;
      font-size: 0.8rem;
      font-weight: 500;
      line-height: 1.4;
      transition: var(--transition);
    }

    /* ── Submit ── */
    .btn-submit {
      width: 100%;
      background: var(--accent);
      color: #0d0f12;
      font-family: 'DM Sans', sans-serif;
      font-size: 0.9rem;
      font-weight: 700;
      letter-spacing: 0.04em;
      border: none;
      border-radius: var(--radius);
      padding: 14px;
      cursor: pointer;
      transition: var(--transition);
      position: relative;
      overflow: hidden;
      margin-top: 6px;
    }

    .btn-submit::after {
      content: '';
      position: absolute;
      inset: 0;
      background: linear-gradient(135deg, rgba(255,255,255,0.15), transparent);
      opacity: 0;
      transition: var(--transition);
    }

    .btn-submit:hover {
      box-shadow: 0 4px 24px var(--accent-glow);
      transform: translateY(-2px);
    }

    .btn-submit:hover::after { opacity: 1; }
    .btn-submit:active { transform: translateY(0); }

    /* ═══════════════════════════════════════════════
       INFO PANEL (lado direito)
    ═══════════════════════════════════════════════ */
    .info-panel {
      display: flex;
      flex-direction: column;
      gap: 16px;
      animation: fadeUp 0.7s 0.2s ease both;
    }

    .info-card {
      background: var(--surface);
      border: 1px solid var(--border);
      border-radius: var(--radius-lg);
      padding: 24px;
    }

    .info-card .label {
      font-size: 0.72rem;
      font-weight: 600;
      letter-spacing: 0.12em;
      text-transform: uppercase;
      color: var(--text-muted);
      margin-bottom: 10px;
    }

    .info-card p {
      font-size: 0.9rem;
      color: #9ca3af;
      line-height: 1.65;
    }

    .info-card .feature-list {
      list-style: none;
      display: flex;
      flex-direction: column;
      gap: 10px;
      margin-top: 4px;
    }

    .info-card .feature-list li {
      display: flex;
      align-items: flex-start;
      gap: 10px;
      font-size: 0.88rem;
      color: #9ca3af;
      line-height: 1.5;
    }

    .info-card .feature-list li .dot {
      width: 6px; height: 6px;
      min-width: 6px;
      background: var(--accent);
      border-radius: 50%;
      margin-top: 6px;
    }

    /* ── Stat badges ── */
    .stat-row {
      display: flex;
      gap: 12px;
      flex-wrap: wrap;
    }

    .stat {
      flex: 1;
      min-width: 80px;
      background: var(--surface-2);
      border: 1px solid var(--border);
      border-radius: var(--radius);
      padding: 14px 16px;
      text-align: center;
    }

    .stat .val {
      font-family: 'DM Serif Display', serif;
      font-size: 1.6rem;
      color: var(--accent);
      line-height: 1;
    }

    .stat .key {
      font-size: 0.7rem;
      font-weight: 600;
      letter-spacing: 0.08em;
      text-transform: uppercase;
      color: var(--text-muted);
      margin-top: 4px;
    }

    /* ═══════════════════════════════════════════════
       HISTÓRICO
    ═══════════════════════════════════════════════ */
    #secao-historico {
      display: none;
      position: relative;
      z-index: 1;
      max-width: 1020px;
      margin: 0 auto 60px;
      padding: 0 20px;
      animation: fadeUp 0.5s ease both;
    }

    .historico-header {
      display: flex;
      align-items: center;
      justify-content: space-between;
      flex-wrap: wrap;
      gap: 12px;
      margin-bottom: 16px;
    }

    .historico-header h2 {
      font-family: 'DM Serif Display', serif;
      font-size: 1.4rem;
      font-weight: 400;
      color: #fff;
    }

    #historico-contador {
      font-size: 0.8rem;
      color: var(--text-muted);
      background: var(--surface-2);
      border: 1px solid var(--border);
      padding: 4px 12px;
      border-radius: 100px;
    }

    #btn-limpar-historico {
      background: transparent;
      border: 1px solid rgba(252,129,129,0.3);
      color: var(--red);
      font-family: 'DM Sans', sans-serif;
      font-size: 0.8rem;
      font-weight: 600;
      padding: 7px 16px;
      border-radius: 8px;
      cursor: pointer;
      transition: var(--transition);
    }

    #btn-limpar-historico:hover {
      background: rgba(252,129,129,0.1);
      border-color: var(--red);
    }

    .table-wrap {
      background: var(--surface);
      border: 1px solid var(--border);
      border-radius: var(--radius-lg);
      overflow: hidden;
      box-shadow: var(--shadow);
    }

    #tabela-historico {
      width: 100%;
      border-collapse: collapse;
    }

    #tabela-historico thead {
      background: var(--surface-2);
    }

    #tabela-historico thead th {
      color: var(--text-muted);
      padding: 14px 20px;
      text-align: left;
      font-size: 0.72rem;
      font-weight: 600;
      letter-spacing: 0.1em;
      text-transform: uppercase;
      border-bottom: 1px solid var(--border);
    }

    #tabela-historico tbody td {
      color: #c1c8d4;
      padding: 13px 20px;
      font-size: 0.88rem;
      border-top: 1px solid var(--border);
      vertical-align: middle;
    }

    #tabela-historico tbody tr {
      transition: background var(--transition);
    }

    #tabela-historico tbody tr:hover {
      background: rgba(99,179,237,0.04);
    }

    /* email pill */
    #tabela-historico tbody td:nth-child(2) {
      font-size: 0.82rem;
      color: var(--accent);
    }

    /* data pill */
    #tabela-historico tbody td:last-child {
      color: var(--text-muted);
      font-size: 0.8rem;
      white-space: nowrap;
    }

    /* ═══════════════════════════════════════════════
       ANIMATIONS
    ═══════════════════════════════════════════════ */
    @keyframes fadeDown {
      from { opacity: 0; transform: translateY(-20px); }
      to   { opacity: 1; transform: translateY(0); }
    }

    @keyframes fadeUp {
      from { opacity: 0; transform: translateY(24px); }
      to   { opacity: 1; transform: translateY(0); }
    }

    @keyframes pulse {
      0%, 100% { opacity: 1; }
      50% { opacity: 0.4; }
    }

    /* ═══════════════════════════════════════════════
       RESPONSIVE ADJUSTMENTS
    ═══════════════════════════════════════════════ */
    @media (max-width: 480px) {
      .card-contato { padding: 24px 20px; }
      .page-header { padding-top: 60px; }
      #salvar-email {
        top: 12px !important;
        right: 12px !important;
        font-size: 0.75rem !important;
        padding: 8px 14px !important;
      }
      .stat .val { font-size: 1.3rem; }
    }

    /* ── Scrollbar ── */
    ::-webkit-scrollbar { width: 6px; }
    ::-webkit-scrollbar-track { background: var(--bg); }
    ::-webkit-scrollbar-thumb { background: #2a2d35; border-radius: 3px; }
    ::-webkit-scrollbar-thumb:hover { background: #3a3f4a; }
  </style>
</head>
<body>

  <!-- Botão fixo: salvar email -->
  <button class="btn-success" id="salvar-email">✉ Salvar Destinatário</button>

  <!-- ── Header ── -->
  <header class="page-header">
    <div class="eyebrow">Sistema de Mensagens</div>
    <h1>Fale <em>conosco</em></h1>
    <p id="card-text">Envie sua mensagem de forma segura. Respondemos em até 24 horas.</p>
  </header>

  <!-- ── Body grid ── -->
  <main class="page-body">

    <!-- Formulário -->
    <div class="card-contato">
      <h2>
        <span class="icon">✉</span>
        Contato
      </h2>

      <form id="form-mail" novalidate>

        <div class="form-group-custom">
          <label for="nome">Nome</label>
          <input type="text" id="nome" name="nome" placeholder="Digite o seu nome..." required />
        </div>

        <div class="form-group-custom">
          <label for="correio">E-mail</label>
          <input type="email" id="correio" name="correio" placeholder="Digite o seu email..." required />
          <span id="badge-cache"></span>
        </div>

        <div class="form-group-custom">
          <label for="mensagem">Mensagem</label>
          <textarea name="mensagem" id="mensagem" placeholder="Escreva aqui..." required></textarea>
        </div>

        <button type="submit" class="btn-submit" data-button>
          Enviar Mensagem →
        </button>

      </form>
    </div>

    <!-- Painel informativo -->
    <div class="info-panel">

      <div class="info-card">
        <div class="label">Como funciona</div>
        <ul class="feature-list">
          <li><span class="dot"></span> Clique em "Salvar Destinatário" para definir o email que receberá a mensagem</li>
          <li><span class="dot"></span> Preencha o formulário com seus dados e mensagem</li>
          <li><span class="dot"></span> O sistema verifica automaticamente se você já é cadastrado</li>
          <li><span class="dot"></span> Ao enviar, o email é entregue e o registro é salvo no banco</li>
        </ul>
      </div>

      <div class="info-card">
        <div class="label">Integração</div>
        <ul class="feature-list">
          <li><span class="dot"></span> <strong style="color:#e8eaf0">Resend API</strong> — entrega de email confiável</li>
          <li><span class="dot"></span> <strong style="color:#e8eaf0">InterSystems Caché</strong> — registro de pacientes</li>
          <li><span class="dot"></span> <strong style="color:#e8eaf0">SQLite</strong> — fallback para desenvolvimento local</li>
        </ul>
      </div>

      <div class="info-card">
        <div class="label">Status do sistema</div>
        <div class="stat-row">
          <div class="stat">
            <div class="val" id="stat-enviados">0</div>
            <div class="key">Enviados</div>
          </div>
          <div class="stat">
            <div class="val" style="color: var(--green)">✓</div>
            <div class="key">Online</div>
          </div>
        </div>
      </div>

    </div>
  </main>

  <!-- ── Histórico ── -->
  <section id="secao-historico">
    <div class="historico-header">
      <h2>📋 Histórico de Envios</h2>
      <div style="display:flex; align-items:center; gap:10px; flex-wrap:wrap;">
        <span id="historico-contador"></span>
        <button id="btn-limpar-historico">🗑 Limpar</button>
      </div>
    </div>
    <div class="table-wrap">
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
    </div>
  </section>

  <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/sweetalert/2.1.2/sweetalert.min.js"></script>
  <script src="Controller/mail.js"></script>
  <script src="Controller/animacao-texto.js"></script>
  <script src="Controller/historico.js"></script>

  <script>
    // Atualiza o contador de "enviados" com base no histórico local
    function atualizarStatEnviados() {
      try {
        const h = JSON.parse(localStorage.getItem('historico-emails') || '[]');
        document.getElementById('stat-enviados').textContent = h.length;
      } catch(e) {}
    }
    atualizarStatEnviados();
    // Re-atualiza quando o histórico mudar
    window.addEventListener('storage', atualizarStatEnviados);
    document.addEventListener('historico-atualizado', atualizarStatEnviados);
  </script>
</body>
</html>
