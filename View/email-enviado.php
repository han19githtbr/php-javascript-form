<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Mensagem Recebida</title>

  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=DM+Serif+Display:ital@0;1&family=DM+Sans:wght@300;400;500;600&display=swap" rel="stylesheet">

  <style>
    :root {
      --bg:        #0d0f12;
      --surface:   #141619;
      --surface-2: #1c1f24;
      --border:    rgba(255,255,255,0.07);
      --text:      #e8eaf0;
      --muted:     #6b7280;
      --accent:    #63b3ed;
      --accent-dim:rgba(99,179,237,0.12);
      --accent-glow:rgba(99,179,237,0.25);
      --green:     #68d391;
      --radius:    12px;
      --radius-lg: 20px;
    }

    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

    body {
      background: var(--bg);
      color: var(--text);
      font-family: 'DM Sans', sans-serif;
      min-height: 100vh;
      display: flex;
      align-items: center;
      justify-content: center;
      padding: 24px;
    }

    body::before {
      content: '';
      position: fixed;
      inset: 0;
      background: radial-gradient(ellipse 70% 50% at 50% 0%, rgba(99,179,237,0.07) 0%, transparent 60%);
      pointer-events: none;
    }

    .container {
      position: relative;
      width: 100%;
      max-width: 600px;
    }

    .card {
      background: var(--surface);
      border: 1px solid var(--border);
      border-radius: var(--radius-lg);
      overflow: hidden;
      box-shadow: 0 20px 60px rgba(0,0,0,0.5);
      animation: fadeUp 0.6s ease both;
    }

    /* ── Top bar ── */
    .card-top {
      background: var(--accent-dim);
      border-bottom: 1px solid rgba(99,179,237,0.15);
      padding: 14px 28px;
      display: flex;
      align-items: center;
      gap: 10px;
    }

    .card-top .badge {
      background: rgba(104,211,145,0.15);
      border: 1px solid rgba(104,211,145,0.3);
      color: var(--green);
      font-size: 0.72rem;
      font-weight: 700;
      letter-spacing: 0.1em;
      text-transform: uppercase;
      padding: 3px 10px;
      border-radius: 100px;
    }

    .card-top .from {
      font-size: 0.85rem;
      color: var(--muted);
    }

    .card-top .from strong {
      color: var(--accent);
    }

    /* ── Body ── */
    .card-body-inner {
      padding: 36px 32px;
    }

    .card-body-inner .subject {
      font-family: 'DM Serif Display', serif;
      font-size: 1.6rem;
      font-weight: 400;
      color: #fff;
      margin-bottom: 24px;
      line-height: 1.2;
    }

    .mensagem-texto {
      background: var(--surface-2);
      border: 1px solid var(--border);
      border-radius: var(--radius);
      padding: 22px 24px;
      font-size: 0.95rem;
      color: #c1c8d4;
      line-height: 1.75;
      margin-bottom: 24px;
    }

    /* ── Footer ── */
    .card-footer-inner {
      background: var(--surface-2);
      border-top: 1px solid var(--border);
      padding: 18px 32px;
      display: flex;
      align-items: center;
      gap: 10px;
      flex-wrap: wrap;
    }

    .card-footer-inner .label {
      font-size: 0.78rem;
      font-weight: 600;
      letter-spacing: 0.06em;
      text-transform: uppercase;
      color: var(--muted);
    }

    .card-footer-inner a {
      color: var(--accent);
      font-size: 0.9rem;
      text-decoration: none;
      border-bottom: 1px solid rgba(99,179,237,0.3);
      transition: border-color 0.2s;
    }

    .card-footer-inner a:hover {
      border-color: var(--accent);
    }

    /* ── Back link ── */
    .back-link {
      display: inline-flex;
      align-items: center;
      gap: 8px;
      margin-top: 20px;
      color: var(--muted);
      font-size: 0.85rem;
      text-decoration: none;
      transition: color 0.2s;
    }

    .back-link:hover { color: var(--accent); text-decoration: none; }

    @keyframes fadeUp {
      from { opacity: 0; transform: translateY(20px); }
      to   { opacity: 1; transform: translateY(0); }
    }
  </style>
</head>
<body>
  <div class="container">

    <div class="card">
      <div class="card-top">
        <span class="badge">✓ Recebido</span>
        <span class="from">De: <strong>Sistema de Mensagens</strong></span>
      </div>

      <div class="card-body-inner">
        <div class="subject">Sua mensagem chegou.</div>
        <div class="mensagem-texto">
          Esta é a mensagem que eu queria enviar para você.
        </div>
      </div>

      <div class="card-footer-inner">
        <span class="label">Responder para</span>
        <a href="mailto:exemplo@gmail.com">exemplo@gmail.com</a>
      </div>
    </div>

    <a href="/" class="back-link">
      ← Voltar ao formulário
    </a>

  </div>
</body>
</html>
