<?php
$ref = htmlspecialchars($_GET['ref'] ?? 'E-' . strtoupper(substr(md5((string)time()), 0, 8)));
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>500 - Falha Crítica Interceptada | ErrorTracker</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=JetBrains+Mono:wght@400;600&display=swap" rel="stylesheet">
  <style>
    :root {
      --bg: #0B0E14;
      --card-bg: rgba(22, 27, 34, 0.8);
      --border: rgba(248, 81, 73, 0.3);
      --accent-red: #F85149;
      --accent-cyan: #00F2FE;
      --text: #E6EDF3;
      --muted: #8B949E;
    }
    * { box-sizing: border-box; margin: 0; padding: 0; }
    body {
      background: var(--bg);
      /* background: radial-gradient(circle at 50% 20%, rgba(248, 81, 73, 0.12) 0%, var(--bg) 70%); */
      color: var(--text);
      font-family: 'Inter', sans-serif;
      min-height: 100vh;
      display: flex;
      align-items: center;
      justify-content: center;
      padding: 24px;
    }
    .card {
      background: var(--card-bg);
      border: 1px solid var(--border);
      border-radius: 15px;
      padding: 48px 40px;
      max-width: 580px;
      width: 100%;
      text-align: center;
      box-shadow: 0 10px 15px rgba(0, 0, 0, 0.6), 0 0 15px rgba(248, 81, 73, 0.15);
      backdrop-filter: blur(12px);
    }
    .badge {
      display: inline-flex;
      align-items: center;
      gap: 6px;
      padding: 6px 14px;
      background: rgba(248, 81, 73, 0.15);
      border: 1px solid rgba(248, 81, 73, 0.4);
      color: var(--accent-red);
      font-size: 13px;
      font-weight: 700;
      border-radius: 999px;
      margin-bottom: 24px;
      text-transform: uppercase;
      letter-spacing: 0.5px;
    }
    h1 {
      font-size: 32px;
      font-weight: 800;
      margin-bottom: 12px;
      color: #FFF;
      letter-spacing: -0.5px;
    }
    p {
      color: var(--muted);
      font-size: 15px;
      line-height: 1.6;
      margin-bottom: 28px;
    }
    .code-box {
      background: rgba(13, 17, 23, 0.9);
      border: 1px solid #30363D;
      border-radius: 10px;
      padding: 14px;
      font-family: 'JetBrains Mono', monospace;
      font-size: 14px;
      color: var(--accent-cyan);
      margin-bottom: 32px;
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 8px;
    }
    .btn {
      display: inline-flex;
      align-items: center;
      gap: 8px;
      background: linear-gradient(135deg, #00F2FE 0%, #4FACFE 100%);
      color: #0B0E14;
      font-weight: 700;
      padding: 14px 28px;
      border-radius: 10px;
      text-decoration: none;
      transition: all 0.2s ease;
      box-shadow: 0 4px 15px rgba(0, 242, 254, 0.3);
    }
    .btn:hover {
      transform: translateY(-2px);
      box-shadow: 0 6px 20px rgba(0, 242, 254, 0.5);
    }
  </style>
</head>
<body>
  <div class="card">
    <div class="badge">
      <span>🛡️</span> Rota de Fuga Ativada (Failsafe)
    </div>
    <h1>Ops! Algo inesperado aconteceu</h1>
    <p>
      Ocorreu uma falha crítica na aplicação. O <strong>ErrorTracker</strong> interceptou o erro com segurança, sanitizou as informações da requisição e notificou a equipe de engenharia.
    </p>
    <div class="code-box">
      <span style="color: #8B949E;">Código do Incidente:</span>
      <span><?= $ref ?></span>
    </div>
    <a href="index.php" class="btn">
      Voltar para o Painel Demo
    </a>
  </div>
</body>
</html>
