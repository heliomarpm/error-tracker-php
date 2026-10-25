<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>ErrorTracker — Monitoramento Resiliente, Anti-Flood & Sanitização de Dados</title>
  <meta name="description" content="Demonstração interativa da biblioteca heliomarpm/error-tracker: interceptação resiliente, anti-flood throttle, mascaramento LGPD e múltiplos handlers.">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&family=JetBrains+Mono:wght@400;500;600;700&family=Outfit:wght@500;600;700;800;900&display=swap" rel="stylesheet">
  <style>
    :root {
      --bg: #080C14;
      --bg-surface: #0E1524;
      --bg-card: rgba(18, 26, 43, 0.75);
      --bg-card-hover: rgba(26, 37, 60, 0.85);
      --border: rgba(255, 255, 255, 0.08);
      --border-accent: rgba(0, 242, 254, 0.3);
      --cyan: #00F2FE;
      --cyan-glow: rgba(0, 242, 254, 0.35);
      --violet: #8A2BE2;
      --violet-glow: rgba(138, 43, 226, 0.35);
      --green: #10B981;
      --amber: #F59E0B;
      --red: #F85149;
      --text: #F1F5F9;
      --text-muted: #94A3B8;
      --text-dim: #64748B;
      --radius: 14px;
      --radius-sm: 8px;
      --radius-lg: 20px;
      --font-heading: 'Outfit', sans-serif;
      --font-body: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
      --font-mono: 'JetBrains Mono', monospace;
    }

    * {
      box-sizing: border-box;
      margin: 0;
      padding: 0;
    }

    body {
      background-color: var(--bg);
      background-image: 
        radial-gradient(circle at 15% 10%, rgba(0, 242, 254, 0.08) 0%, transparent 40%),
        radial-gradient(circle at 85% 25%, rgba(138, 43, 226, 0.08) 0%, transparent 45%),
        radial-gradient(circle at 50% 80%, rgba(16, 185, 129, 0.05) 0%, transparent 50%);
      color: var(--text);
      font-family: var(--font-body);
      min-height: 100vh;
      line-height: 1.5;
      overflow-x: hidden;
      padding-bottom: 80px;
    }

    /* Container */
    .container {
      max-width: 1240px;
      margin: 0 auto;
      padding: 0 24px;
    }

    /* Header */
    header {
      padding: 32px 0 24px;
      border-bottom: 1px solid var(--border);
      position: sticky;
      top: 0;
      background: rgba(8, 12, 20, 0.85);
      backdrop-filter: blur(16px);
      z-index: 100;
    }

    .header-inner {
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 16px;
      flex-wrap: wrap;
    }

    .brand {
      display: flex;
      align-items: center;
      gap: 16px;
      text-decoration: none;
    }

    .brand-logo {
      width: 46px;
      height: 46px;
      border-radius: 12px;
      object-fit: cover;
      box-shadow: 0 0 20px var(--cyan-glow);
      border: 1px solid rgba(0, 242, 254, 0.4);
      background: #0E1524;
    }

    .brand-info h1 {
      font-family: var(--font-heading);
      font-size: 20px;
      font-weight: 800;
      color: #FFF;
      letter-spacing: -0.5px;
      display: flex;
      align-items: center;
      gap: 8px;
    }

    .brand-info h1 span.highlight {
      background: linear-gradient(135deg, var(--cyan) 0%, #4FACFE 50%, var(--violet) 100%);
      -webkit-background-clip: text;
      -webkit-text-fill-color: transparent;
    }

    .brand-info p {
      font-size: 13px;
      color: var(--text-muted);
    }

    .nav-actions {
      display: flex;
      align-items: center;
      gap: 12px;
      flex-wrap: wrap;
    }

    .pill-badge {
      display: inline-flex;
      align-items: center;
      gap: 6px;
      padding: 6px 12px;
      border-radius: 999px;
      font-size: 12px;
      font-weight: 600;
      background: rgba(16, 185, 129, 0.12);
      border: 1px solid rgba(16, 185, 129, 0.3);
      color: #34D399;
    }

    .pill-dot {
      width: 8px;
      height: 8px;
      border-radius: 50%;
      background: #10B981;
      box-shadow: 0 0 10px #10B981;
      animation: pulse 2s infinite;
    }

    @keyframes pulse {
      0%, 100% { opacity: 1; transform: scale(1); }
      50% { opacity: 0.4; transform: scale(0.9); }
    }

    .btn-link {
      display: inline-flex;
      align-items: center;
      gap: 6px;
      padding: 8px 14px;
      border-radius: var(--radius-sm);
      font-size: 13px;
      font-weight: 600;
      text-decoration: none;
      color: var(--text);
      background: var(--bg-surface);
      border: 1px solid var(--border);
      transition: all 0.2s ease;
    }

    .btn-link:hover {
      background: rgba(255, 255, 255, 0.08);
      border-color: rgba(255, 255, 255, 0.2);
      color: #FFF;
      transform: translateY(-1px);
    }

    /* Hero */
    .hero {
      padding: 48px 0 36px;
      text-align: center;
    }

    .hero-badge {
      display: inline-flex;
      align-items: center;
      gap: 8px;
      padding: 6px 16px;
      border-radius: 999px;
      background: rgba(0, 242, 254, 0.1);
      border: 1px solid var(--border-accent);
      color: var(--cyan);
      font-size: 13px;
      font-weight: 700;
      text-transform: uppercase;
      letter-spacing: 0.5px;
      margin-bottom: 20px;
    }

    .hero h2 {
      font-family: var(--font-heading);
      font-size: 42px;
      font-weight: 900;
      line-height: 1.15;
      letter-spacing: -1px;
      margin-bottom: 16px;
      max-width: 860px;
      margin-left: auto;
      margin-right: auto;
    }

    .hero h2 .grad-text {
      background: linear-gradient(135deg, #FFF 0%, var(--cyan) 50%, var(--violet) 100%);
      -webkit-background-clip: text;
      -webkit-text-fill-color: transparent;
    }

    .hero p {
      font-size: 17px;
      color: var(--text-muted);
      max-width: 680px;
      margin: 0 auto 28px;
      line-height: 1.6;
    }

    /* Stats Grid */
    .stats-grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
      gap: 16px;
      margin-bottom: 40px;
    }

    .stat-card {
      background: var(--bg-card);
      border: 1px solid var(--border);
      border-radius: var(--radius);
      padding: 20px;
      display: flex;
      align-items: center;
      gap: 16px;
      backdrop-filter: blur(12px);
      transition: all 0.2s ease;
    }

    .stat-card:hover {
      border-color: rgba(0, 242, 254, 0.3);
      transform: translateY(-2px);
      box-shadow: 0 10px 25px rgba(0, 0, 0, 0.3);
    }

    .stat-icon {
      width: 44px;
      height: 44px;
      border-radius: 12px;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 20px;
      background: var(--bg-surface);
      border: 1px solid var(--border);
    }

    .stat-val {
      font-family: var(--font-mono);
      font-size: 24px;
      font-weight: 700;
      color: #FFF;
      line-height: 1.2;
    }

    .stat-label {
      font-size: 13px;
      color: var(--text-muted);
    }

    /* Layout Grids */
    .grid-2col {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 24px;
      margin-bottom: 40px;
    }

    @media (max-width: 992px) {
      .grid-2col {
        grid-template-columns: 1fr;
      }
      .hero h2 {
        font-size: 32px;
      }
    }

    /* Section Card */
    .card {
      background: var(--bg-card);
      border: 1px solid var(--border);
      border-radius: var(--radius-lg);
      padding: 28px;
      backdrop-filter: blur(16px);
      position: relative;
      overflow: hidden;
      box-shadow: 0 16px 40px rgba(0, 0, 0, 0.4);
    }

    .card::before {
      content: '';
      position: absolute;
      top: 0;
      left: 0;
      right: 0;
      height: 2px;
      background: linear-gradient(90deg, transparent, rgba(0, 242, 254, 0.3), transparent);
    }

    .card-header {
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 12px;
      margin-bottom: 20px;
      padding-bottom: 16px;
      border-bottom: 1px solid var(--border);
    }

    .card-title {
      display: flex;
      align-items: center;
      gap: 10px;
      font-family: var(--font-heading);
      font-size: 18px;
      font-weight: 700;
      color: #FFF;
    }

    .card-title .icon {
      font-size: 20px;
    }

    .card-badge {
      font-size: 11px;
      font-weight: 700;
      text-transform: uppercase;
      padding: 4px 10px;
      border-radius: 999px;
      background: rgba(255, 255, 255, 0.06);
      border: 1px solid var(--border);
      color: var(--text-muted);
    }

    /* Triggers Grid */
    .triggers-grid {
      display: grid;
      grid-template-columns: repeat(2, 1fr);
      gap: 12px;
      margin-bottom: 20px;
    }

    @media (max-width: 600px) {
      .triggers-grid {
        grid-template-columns: 1fr;
      }
    }

    .btn-trigger {
      background: var(--bg-surface);
      border: 1px solid var(--border);
      border-radius: var(--radius-sm);
      padding: 16px;
      text-align: left;
      color: var(--text);
      cursor: pointer;
      transition: all 0.2s ease;
      display: flex;
      flex-direction: column;
      gap: 4px;
      position: relative;
      overflow: hidden;
    }

    .btn-trigger:hover {
      background: rgba(255, 255, 255, 0.05);
      transform: translateY(-2px);
      box-shadow: 0 6px 18px rgba(0, 0, 0, 0.3);
    }

    .btn-trigger.notice:hover {
      border-color: rgba(0, 242, 254, 0.5);
      box-shadow: 0 6px 18px var(--cyan-glow);
    }

    .btn-trigger.warning:hover {
      border-color: rgba(245, 158, 11, 0.5);
      box-shadow: 0 6px 18px rgba(245, 158, 11, 0.2);
    }

    .btn-trigger.error:hover {
      border-color: rgba(248, 81, 73, 0.5);
      box-shadow: 0 6px 18px rgba(248, 81, 73, 0.2);
    }

    .btn-trigger.failsafe:hover {
      border-color: rgba(138, 43, 226, 0.5);
      box-shadow: 0 6px 18px var(--violet-glow);
    }

    .btn-trigger .top {
      display: flex;
      align-items: center;
      justify-content: space-between;
      font-weight: 700;
      font-size: 14px;
      color: #FFF;
    }

    .btn-trigger .desc {
      font-size: 12px;
      color: var(--text-dim);
    }

    /* Forms */
    .form-group {
      margin-bottom: 14px;
    }

    .form-group label {
      display: block;
      font-size: 12px;
      font-weight: 600;
      color: var(--text-muted);
      margin-bottom: 6px;
      text-transform: uppercase;
      letter-spacing: 0.5px;
    }

    .form-control {
      width: 100%;
      background: rgba(13, 19, 33, 0.9);
      border: 1px solid var(--border);
      border-radius: var(--radius-sm);
      padding: 10px 14px;
      color: #FFF;
      font-family: var(--font-mono);
      font-size: 13px;
      transition: all 0.2s ease;
    }

    .form-control:focus {
      outline: none;
      border-color: var(--cyan);
      box-shadow: 0 0 10px var(--cyan-glow);
    }

    .btn-primary {
      width: 100%;
      background: linear-gradient(135deg, var(--cyan) 0%, #3B82F6 100%);
      color: #080C14;
      font-weight: 700;
      font-size: 14px;
      padding: 12px 20px;
      border: none;
      border-radius: var(--radius-sm);
      cursor: pointer;
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 8px;
      transition: all 0.2s ease;
      box-shadow: 0 4px 15px rgba(0, 242, 254, 0.25);
    }

    .btn-primary:hover {
      transform: translateY(-2px);
      box-shadow: 0 6px 20px rgba(0, 242, 254, 0.45);
    }

    .btn-primary:disabled {
      opacity: 0.5;
      cursor: not-allowed;
      transform: none;
      box-shadow: none;
    }

    /* Code & Comparison Viewer */
    .comparison-grid {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 16px;
      margin-top: 16px;
    }

    @media (max-width: 768px) {
      .comparison-grid {
        grid-template-columns: 1fr;
      }
    }

    .code-panel {
      background: #090E17;
      border: 1px solid var(--border);
      border-radius: var(--radius-sm);
      overflow: hidden;
      display: flex;
      flex-direction: column;
    }

    .code-panel-header {
      background: rgba(255, 255, 255, 0.03);
      padding: 8px 14px;
      font-size: 12px;
      font-weight: 700;
      display: flex;
      align-items: center;
      justify-content: space-between;
      border-bottom: 1px solid var(--border);
    }

    .code-panel-header.danger {
      color: #F87171;
    }

    .code-panel-header.success {
      color: #34D399;
    }

    .code-content {
      padding: 14px;
      font-family: var(--font-mono);
      font-size: 12px;
      color: #E2E8F0;
      white-space: pre-wrap;
      word-break: break-all;
      line-height: 1.6;
      max-height: 240px;
      overflow-y: auto;
    }

    .code-content .masked {
      color: #34D399;
      font-weight: 700;
      background: rgba(16, 185, 129, 0.15);
      padding: 2px 6px;
      border-radius: 4px;
    }

    .code-content .exposed {
      color: #F87171;
      font-weight: 700;
      background: rgba(248, 81, 73, 0.15);
      padding: 2px 6px;
      border-radius: 4px;
    }

    /* Anti-Flood Box */
    .flood-box {
      text-align: center;
      padding: 24px;
      background: rgba(14, 21, 36, 0.5);
      border: 1px solid var(--border);
      border-radius: var(--radius);
      margin-bottom: 20px;
    }

    .flood-metrics {
      display: flex;
      justify-content: center;
      align-items: center;
      gap: 32px;
      margin: 24px 0;
      flex-wrap: wrap;
    }

    .flood-val {
      font-family: var(--font-mono);
      font-size: 36px;
      font-weight: 800;
      line-height: 1;
      margin-bottom: 6px;
    }

    .flood-val.total { color: #FFF; }
    .flood-val.sent { color: #F87171; }
    .flood-val.suppressed { color: #34D399; }

    .flood-title {
      font-size: 13px;
      color: var(--text-muted);
      text-transform: uppercase;
      letter-spacing: 0.5px;
    }

    .progress-bar-wrap {
      width: 100%;
      height: 10px;
      background: #090E17;
      border-radius: 999px;
      overflow: hidden;
      margin-bottom: 16px;
      border: 1px solid var(--border);
    }

    .progress-bar-fill {
      height: 100%;
      width: 0%;
      background: linear-gradient(90deg, #10B981, #00F2FE);
      border-radius: 999px;
      transition: width 0.6s cubic-bezier(0.16, 1, 0.3, 1);
    }

    /* Tabs & Previews */
    .tabs-nav {
      display: flex;
      gap: 8px;
      border-bottom: 1px solid var(--border);
      padding-bottom: 12px;
      margin-bottom: 16px;
      overflow-x: auto;
    }

    .tab-btn {
      background: transparent;
      border: 1px solid transparent;
      padding: 8px 16px;
      border-radius: var(--radius-sm);
      color: var(--text-muted);
      font-size: 13px;
      font-weight: 600;
      cursor: pointer;
      display: flex;
      align-items: center;
      gap: 8px;
      transition: all 0.2s ease;
      white-space: nowrap;
    }

    .tab-btn:hover {
      color: #FFF;
      background: rgba(255, 255, 255, 0.04);
    }

    .tab-btn.active {
      color: #FFF;
      background: var(--bg-surface);
      border-color: var(--border);
      box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
    }

    .tab-btn.active .tab-indicator {
      background: var(--cyan);
      box-shadow: 0 0 8px var(--cyan);
    }

    .tab-indicator {
      width: 6px;
      height: 6px;
      border-radius: 50%;
      background: var(--text-dim);
    }

    .tab-pane {
      display: none;
    }

    .tab-pane.active {
      display: block;
      animation: fadeIn 0.2s ease-in-out;
    }

    @keyframes fadeIn {
      from { opacity: 0; transform: translateY(4px); }
      to { opacity: 1; transform: translateY(0); }
    }

    .preview-card {
      background: #090E17;
      border: 1px solid var(--border);
      border-radius: var(--radius-sm);
      padding: 20px;
      font-size: 13px;
      min-height: 280px;
      max-height: 480px;
      overflow-y: auto;
    }

    /* Email Mock Render */
    .email-preview-box {
      background: #111827;
      border-radius: 10px;
      padding: 24px;
      border: 1px solid #1F2937;
      color: #E5E7EB;
      font-family: var(--font-body);
    }

    /* GitHub Markdown Mock */
    .github-preview-box {
      background: #0D1117;
      border: 1px solid #30363D;
      border-radius: 10px;
      padding: 20px;
      color: #C9D1D9;
    }

    /* Webhook Cards */
    .slack-preview-box {
      background: #1A1D21;
      border-left: 4px solid #F0AD4E;
      border-radius: 4px;
      padding: 16px;
      color: #D1D2D3;
      font-family: 'Lato', sans-serif, system-ui;
    }

    .discord-preview-box {
      background: #2B2D31;
      border-left: 4px solid #5865F2;
      border-radius: 4px;
      padding: 16px;
      color: #DBDEE1;
      font-family: 'gg sans', sans-serif, system-ui;
    }

    /* Incident History Table */
    .history-card {
      margin-top: 40px;
    }

    .table-wrap {
      overflow-x: auto;
      border-radius: var(--radius-sm);
      border: 1px solid var(--border);
    }

    table {
      width: 100%;
      border-collapse: collapse;
      text-align: left;
      font-size: 13px;
    }

    th {
      background: var(--bg-surface);
      padding: 12px 16px;
      color: var(--text-muted);
      font-weight: 700;
      text-transform: uppercase;
      font-size: 11px;
      letter-spacing: 0.5px;
      border-bottom: 1px solid var(--border);
    }

    td {
      padding: 14px 16px;
      border-bottom: 1px solid var(--border);
      color: var(--text);
    }

    tr:hover td {
      background: rgba(255, 255, 255, 0.02);
    }

    .badge-severity {
      display: inline-flex;
      align-items: center;
      padding: 3px 8px;
      border-radius: 999px;
      font-size: 11px;
      font-weight: 700;
      text-transform: uppercase;
      letter-spacing: 0.5px;
    }

    .badge-severity.INFO { background: rgba(0, 242, 254, 0.15); color: #38BDF8; }
    .badge-severity.NOTICE { background: rgba(52, 211, 153, 0.15); color: #34D399; }
    .badge-severity.WARNING { background: rgba(245, 158, 11, 0.15); color: #FBBF24; }
    .badge-severity.ERROR { background: rgba(248, 81, 73, 0.15); color: #F87171; }
    .badge-severity.CRITICAL { background: rgba(239, 68, 68, 0.25); color: #EF4444; }

    .btn-action-sm {
      background: var(--bg-surface);
      border: 1px solid var(--border);
      color: var(--cyan);
      font-size: 12px;
      font-weight: 600;
      padding: 4px 10px;
      border-radius: 4px;
      cursor: pointer;
      transition: all 0.2s ease;
    }

    .btn-action-sm:hover {
      background: rgba(0, 242, 254, 0.1);
      border-color: var(--cyan);
    }

    /* Toast */
    .toast {
      position: fixed;
      bottom: 24px;
      right: 24px;
      background: #0E1524;
      border: 1px solid var(--border-accent);
      color: #FFF;
      padding: 14px 20px;
      border-radius: var(--radius-sm);
      font-size: 13px;
      font-weight: 600;
      display: flex;
      align-items: center;
      gap: 10px;
      box-shadow: 0 10px 30px rgba(0, 0, 0, 0.6);
      transform: translateY(100px);
      opacity: 0;
      transition: all 0.3s cubic-bezier(0.16, 1, 0.3, 1);
      z-index: 1000;
    }

    .toast.show {
      transform: translateY(0);
      opacity: 1;
    }

    /* Footer */
    footer {
      margin-top: 60px;
      padding-top: 32px;
      border-top: 1px solid var(--border);
      text-align: center;
      color: var(--text-dim);
      font-size: 13px;
    }

    footer a {
      color: var(--text-muted);
      text-decoration: none;
      transition: color 0.2s;
    }

    footer a:hover {
      color: var(--cyan);
    }
    /* Modal */
    .modal-backdrop {
      position: fixed;
      top: 0;
      left: 0;
      right: 0;
      bottom: 0;
      background: rgba(8, 12, 20, 0.85);
      backdrop-filter: blur(8px);
      z-index: 1000;
      display: none;
      align-items: center;
      justify-content: center;
      padding: 20px;
    }

    .modal-backdrop.show {
      display: flex;
      animation: modalFadeIn 0.2s ease-out;
    }

    @keyframes modalFadeIn {
      from { opacity: 0; transform: scale(0.97); }
      to { opacity: 1; transform: scale(1); }
    }

    .modal-card {
      background: #0E1524;
      border: 1px solid var(--border-accent);
      border-radius: var(--radius-lg);
      max-width: 800px;
      width: 100%;
      max-height: 85vh;
      display: flex;
      flex-direction: column;
      box-shadow: 0 24px 60px rgba(0, 0, 0, 0.8), 0 0 30px rgba(0, 242, 254, 0.15);
      overflow: hidden;
    }

    .modal-header {
      padding: 18px 24px;
      border-bottom: 1px solid var(--border);
      display: flex;
      align-items: center;
      justify-content: space-between;
      background: rgba(255, 255, 255, 0.02);
    }

    .modal-close-btn {
      background: transparent;
      border: none;
      color: var(--text-dim);
      font-size: 20px;
      cursor: pointer;
      line-height: 1;
      padding: 4px 8px;
      border-radius: 6px;
      transition: color 0.2s;
    }

    .modal-close-btn:hover {
      color: #FFF;
      background: rgba(255, 255, 255, 0.08);
    }

    .modal-body {
      padding: 24px;
      overflow-y: auto;
      flex: 1;
    }

    .modal-section {
      margin-bottom: 18px;
    }

    .modal-label {
      font-size: 11px;
      font-weight: 700;
      color: var(--text-muted);
      text-transform: uppercase;
      letter-spacing: 0.5px;
      margin-bottom: 6px;
    }

    .modal-msg-box {
      background: #090E17;
      border: 1px solid var(--border);
      border-left: 4px solid var(--cyan);
      padding: 12px 16px;
      border-radius: var(--radius-sm);
      color: #FFF;
      font-weight: 600;
      font-size: 14px;
    }

    .modal-meta-item {
      background: rgba(13, 19, 33, 0.7);
      border: 1px solid var(--border);
      padding: 10px 14px;
      border-radius: var(--radius-sm);
    }

    .modal-code-block {
      background: #090E17;
      border: 1px solid var(--border);
      border-radius: var(--radius-sm);
      padding: 12px 14px;
      font-family: var(--font-mono);
      font-size: 11px;
      color: #CBD5E1;
      max-height: 180px;
      overflow-y: auto;
      white-space: pre-wrap;
      word-break: break-all;
      line-height: 1.5;
    }

    .modal-footer {
      padding: 16px 24px;
      border-top: 1px solid var(--border);
      display: flex;
      justify-content: flex-end;
      gap: 12px;
      background: rgba(255, 255, 255, 0.02);
    }
  </style>
</head>
<body>

  <!-- Toast Notification -->
  <div id="toast" class="toast">
    <span id="toastIcon">🛡️</span>
    <span id="toastMsg">Mensagem</span>
  </div>

  <!-- Modal de Detalhes do Incidente -->
  <div id="incidentModal" class="modal-backdrop" onclick="handleModalBackdropClick(event)">
    <div class="modal-card">
      <div class="modal-header">
        <div style="display: flex; align-items: center; gap: 10px;">
          <span id="modalSeverityBadge" class="badge-severity INFO">INFO</span>
          <span id="modalIncidentId" style="font-family: var(--font-mono); font-size: 13px; color: var(--text-muted);">INC-000000</span>
          <span id="modalTime" style="font-size: 12px; color: var(--text-dim);">12:00:00</span>
        </div>
        <button class="modal-close-btn" onclick="closeIncidentModal()">✕</button>
      </div>

      <div class="modal-body">
        <div class="modal-section">
          <div class="modal-label">Mensagem do Incidente</div>
          <div id="modalMessage" class="modal-msg-box">Mensagem do incidente</div>
        </div>

        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 12px; margin-bottom: 18px;">
          <div class="modal-meta-item">
            <div class="modal-label">Arquivo & Linha</div>
            <div id="modalFile" style="font-family: var(--font-mono); font-size: 12px; color: #FFF; word-break: break-all;">---</div>
          </div>
          <div class="modal-meta-item">
            <div class="modal-label">Hash Único (Deduplicação MD5)</div>
            <div id="modalHash" style="font-family: var(--font-mono); font-size: 12px; color: var(--cyan); word-break: break-all;">---</div>
          </div>
        </div>

        <div class="modal-section">
          <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 6px;">
            <div class="modal-label" style="margin: 0;">Stack Trace</div>
            <button class="btn-action-sm" onclick="copyModalTrace()">📋 Copiar Trace</button>
          </div>
          <pre id="modalTrace" class="modal-code-block"></pre>
        </div>

        <div class="modal-section">
          <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 6px;">
            <div class="modal-label" style="margin: 0;">Payload / Ambiente Sanitizado</div>
            <button class="btn-action-sm" onclick="copyModalPayload()">📋 Copiar JSON</button>
          </div>
          <pre id="modalPayload" class="modal-code-block"></pre>
        </div>
      </div>

      <div class="modal-footer">
        <button class="btn-action-sm" style="padding: 8px 16px; font-size: 13px;" onclick="closeIncidentModal()">Fechar</button>
        <button class="btn-primary" style="width: auto; padding: 8px 18px; font-size: 13px;" onclick="loadIncidentIntoPreviews()">
          <span>🔄 Carregar nos Previews de Notificação</span>
        </button>
      </div>
    </div>
  </div>

  <!-- Header -->
  <header>
    <div class="container header-inner">
      <a href="/" class="brand">
        <img src="logo.png" alt="ErrorTracker Shield Logo" class="brand-logo" onerror="this.src='data:image/svg+xml;utf8,<svg xmlns=\'http://www.w3.org/2000/svg\' width=\'46\' height=\'46\' viewBox=\'0 0 24 24\' fill=\'none\' stroke=\'%2300F2FE\' stroke-width=\'2\'><path d=\'M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z\'/></svg>'">
        <div class="brand-info">
          <h1>HeliomarPM / <span class="highlight">ErrorTracker</span></h1>
          <p>Resilient Error Tracker, Anti-Flood & LGPD Sanitizer</p>
        </div>
      </a>
      <div class="nav-actions">
        <div class="pill-badge">
          <span class="pill-dot"></span>
          <span>Vercel Serverless Ready (Zero-Disk)</span>
        </div>
        <a href="https://github.com/heliomarpm/error-tracker" target="_blank" rel="noopener" class="btn-link">
          <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor"><path d="M12 0C5.37 0 0 5.37 0 12c0 5.31 3.435 9.795 8.205 11.385.6.105.825-.255.825-.57 0-.285-.015-1.23-.015-2.235-3.015.555-3.795-.735-4.035-1.41-.135-.345-.72-1.41-1.23-1.695-.42-.225-1.02-.78-.015-.795.945-.015 1.62.87 1.845 1.23 1.08 1.815 2.805 1.305 3.495.99.105-.78.42-1.305.765-1.605-2.67-.3-5.46-1.335-5.46-5.925 0-1.305.465-2.385 1.23-3.225-.12-.3-.54-1.53.12-3.18 0 0 1.005-.315 3.3 1.23.96-.27 1.98-.405 3-.405s2.04.135 3 .405c2.295-1.56 3.3-1.23 3.3-1.23.66 1.65.24 2.88.12 3.18.765.84 1.23 1.905 1.23 3.225 0 4.605-2.805 5.625-5.475 5.925.435.375.81 1.095.81 2.22 0 1.605-.015 2.895-.015 3.3 0 .315.225.69.825.57A12.02 12.02 0 0024 12c0-6.63-5.37-12-12-12z"/></svg>
          GitHub
        </a>
        <a href="https://packagist.org/packages/heliomarpm/error-tracker" target="_blank" rel="noopener" class="btn-link">
          📦 Packagist
        </a>
      </div>
    </div>
  </header>

  <!-- Main Content -->
  <main class="container">
    <!-- Hero Section -->
    <section class="hero">
      <div class="hero-badge">
        <span>⚡</span> Arquitetura Moderna PHP 8.2+ & PSR-3 / PSR-16
      </div>
      <h2>
        Proteja sua aplicação contra falhas críticas com <span class="grad-text">tolerância a desastres e zero ruído</span>.
      </h2>
      <p>
        Interceptação inteligente de exceções com deduplicação anti-flood, sanitização automática de dados sensíveis (LGPD/PCI-DSS) e despacho desacoplado para múltiplos canais.
      </p>

      <!-- Stats Grid -->
      <div class="stats-grid">
        <div class="stat-card">
          <div class="stat-icon">🛡️</div>
          <div>
            <div class="stat-val" id="statSimulations">0</div>
            <div class="stat-label">Simulações Disparadas</div>
          </div>
        </div>
        <div class="stat-card">
          <div class="stat-icon">⚡</div>
          <div style="text-align: left;">
            <div class="stat-val" id="statFloodsSuppressed">0</div>
            <div class="stat-label">Duplicatas Silenciadas</div>
          </div>
        </div>
        <div class="stat-card">
          <div class="stat-icon">🔒</div>
          <div style="text-align: left;">
            <div class="stat-val" id="statSanitizedFields">100%</div>
            <div class="stat-label">Campos LGPD Mascarados</div>
          </div>
        </div>
        <div class="stat-card">
          <div class="stat-icon">📡</div>
          <div style="text-align: left;">
            <div class="stat-val">6 Handlers</div>
            <div class="stat-label">Email, GitHub, Slack & +</div>
          </div>
        </div>
      </div>
    </section>

    <!-- 2 Column Section: Triggers & Anti-Flood -->
    <div class="grid-2col">
      <!-- Card: Triggers -->
      <div class="card">
        <div class="card-header">
          <div class="card-title">
            <span class="icon">🚨</span>
            <span>Simulador de Incidentes</span>
          </div>
          <span class="card-badge">Tempo Real</span>
        </div>
        <p style="font-size: 13px; color: var(--text-muted); margin-bottom: 18px;">
          Dispare diferentes níveis de gravidade capturados pelo <code>ErrorTracker</code>. Os incidentes são processados em memória sem persistência em disco.
        </p>

        <div class="triggers-grid">
          <button class="btn-trigger notice" onclick="triggerIncident('notice')">
            <div class="top">
              <span>⚡ Notice</span>
              <span style="color: var(--cyan); font-size: 11px;">E_USER_NOTICE</span>
            </div>
            <div class="desc">Acesso com cupom de desconto expirado.</div>
          </button>

          <button class="btn-trigger warning" onclick="triggerIncident('warning')">
            <div class="top">
              <span>⚠️ Warning</span>
              <span style="color: var(--amber); font-size: 11px;">E_USER_WARNING</span>
            </div>
            <div class="desc">Endpoint depreciado v1/pedidos detectado.</div>
          </button>

          <button class="btn-trigger error" onclick="triggerIncident('exception')">
            <div class="top">
              <span>💥 Exception</span>
              <span style="color: var(--red); font-size: 11px;">RuntimeException</span>
            </div>
            <div class="desc">Timeout de comunicação com gateway de cartão.</div>
          </button>

          <a href="500.php?ref=DEMO-CYBER-99" target="_blank" rel="noopener" class="btn-trigger failsafe" style="text-decoration: none;">
            <div class="top">
              <span>🛡️ Rota de Fuga 500</span>
              <span style="color: var(--violet); font-size: 11px;">FAILSAFE UI ↗</span>
            </div>
            <div class="desc">Abre tela amigável isolando detalhes técnicos do usuário.</div>
          </a>
        </div>

        <div id="triggerResultBox" style="display: none; background: #090E17; border: 1px solid var(--border); border-radius: var(--radius-sm); padding: 14px; margin-top: 14px; font-family: var(--font-mono); font-size: 12px;">
          <div style="display: flex; justify-content: space-between; margin-bottom: 6px;">
            <span id="triggerSeverity" class="badge-severity INFO">INFO</span>
            <span id="triggerTime" style="color: var(--text-dim);">12:00:00</span>
          </div>
          <div id="triggerMsg" style="color: #FFF; font-weight: 600; margin-bottom: 6px;">Mensagem de erro</div>
          <div style="color: var(--text-dim); font-size: 11px;">Hash: <span id="triggerHash" style="color: var(--cyan);">---</span></div>
        </div>
      </div>

      <!-- Card: Anti-Flood Rate Limiting -->
      <div class="card">
        <div class="card-header">
          <div class="card-title">
            <span class="icon">🛑</span>
            <span>Anti-Flood Rate Limiting</span>
          </div>
          <span class="card-badge">PSR-16 Cache</span>
        </div>
        <p style="font-size: 13px; color: var(--text-muted); margin-bottom: 18px;">
          Em uma tempestade de acessos ou ataque DDoS, 50 erros idênticos por segundo inundariam seu Slack, GitHub e e-mail. Veja o ErrorTracker em ação:
        </p>

        <div class="flood-box">
          <div class="progress-bar-wrap">
            <div id="floodProgress" class="progress-bar-fill"></div>
          </div>
          <div class="flood-metrics">
            <div>
              <div class="flood-val total" id="floodTotal">0</div>
              <div class="flood-title">Erros Disparados</div>
            </div>
            <div>
              <div class="flood-val sent" id="floodSent">0</div>
              <div class="flood-title">Notificações Despachadas</div>
            </div>
            <div>
              <div class="flood-val suppressed" id="floodSuppressed">0</div>
              <div class="flood-title">Duplicatas Silenciadas</div>
            </div>
          </div>
          <button id="btnFlood" class="btn-primary" onclick="runAntiFloodStressTest()">
            <span>🚀 Disparar Rajada de 50 Erros em Loop</span>
          </button>
        </div>

        <p style="font-size: 12px; color: var(--text-dim); line-height: 1.5;">
          💡 <strong>Como funciona:</strong> O <code>ErrorTracker</code> gera um hash criptográfico contextual (Classe + Mensagem + Linha) e verifica o cache. Na janela de TTL (ex: 60s), apenas o primeiro erro aciona os handlers!
        </p>
      </div>
    </div>

    <!-- Section: Sanitization Playground -->
    <div class="card" style="margin-bottom: 40px;">
      <div class="card-header">
        <div class="card-title">
          <span class="icon">🔒</span>
          <span>Playground de Sanitização LGPD & PCI-DSS</span>
        </div>
        <span class="card-badge">DataSanitizer</span>
      </div>
      <p style="font-size: 13px; color: var(--text-muted); margin-bottom: 20px;">
        O <code>DataSanitizer</code> remove senhas, tokens de autenticação, chaves de API e mascara dados sensíveis (CPF, cartões de crédito, e-mails) de qualquer superglobal antes do envio para logs externos.
      </p>

      <form id="sanitizerForm" onsubmit="handleSanitizeSubmit(event)">
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 14px; margin-bottom: 16px;">
          <div class="form-group">
            <label>Usuário / Nome</label>
            <input type="text" id="inputUsername" class="form-control" value="heliomar_dev">
          </div>
          <div class="form-group">
            <label>Senha Secreta</label>
            <input type="text" id="inputPassword" class="form-control" value="minhaSenhaSuperSecreta123">
          </div>
          <div class="form-group">
            <label>Token JWT (Auth)</label>
            <input type="text" id="inputToken" class="form-control" value="eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9...">
          </div>
          <div class="form-group">
            <label>CPF do Cliente</label>
            <input type="text" id="inputCpf" class="form-control" value="123.456.789-10">
          </div>
          <div class="form-group">
            <label>Cartão de Crédito</label>
            <input type="text" id="inputCard" class="form-control" value="4532 1122 3344 9876">
          </div>
          <div class="form-group">
            <label>E-mail Pessoal</label>
            <input type="text" id="inputEmail" class="form-control" value="suporte.tech@empresa.com.br">
          </div>
        </div>

        <button type="submit" id="btnSanitize" class="btn-primary" style="max-width: 320px;">
          <span>🛡️ Sanitizar Requisição & Interceptar Erro</span>
        </button>
      </form>

      <!-- Side-by-Side Comparison -->
      <div class="comparison-grid">
        <div class="code-panel">
          <div class="code-panel-header danger">
            <span>⚠️ Payload Bruto Enviado (Exposto / Risco LGPD)</span>
            <span>$_POST</span>
          </div>
          <div class="code-content" id="rawPayloadView">
{
  "username": "heliomar_dev",
  "password": "<span class='exposed'>minhaSenhaSuperSecreta123</span>",
  "token": "<span class='exposed'>eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9...</span>",
  "cpf": "<span class='exposed'>123.456.789-10</span>",
  "credit_card": "<span class='exposed'>4532 1122 3344 9876</span>",
  "email": "<span class='exposed'>suporte.tech@empresa.com.br</span>"
}
          </div>
        </div>

        <div class="code-panel">
          <div class="code-panel-header success">
            <span>✅ Payload Sanitizado pelo ErrorTracker (Protegido)</span>
            <span>$context->requestData</span>
          </div>
          <div class="code-content" id="sanitizedPayloadView">
{
  "username": "heliomar_dev",
  "password": "<span class='masked'>********</span>",
  "token": "<span class='masked'>********</span>",
  "cpf": "<span class='masked'>***.***.789-10</span>",
  "credit_card": "<span class='masked'>**** **** **** 9876</span>",
  "email": "<span class='masked'>sup*********@empresa.com.br</span>"
}
          </div>
        </div>
      </div>
    </div>

    <!-- Section: Mock Notification Previewer -->
    <div class="card" style="margin-bottom: 40px;">
      <div class="card-header">
        <div class="card-title">
          <span class="icon">📬</span>
          <span>Visualizador de Notificações Mockadas (Handlers)</span>
        </div>
        <button class="btn-action-sm" onclick="copyActivePreview()">📋 Copiar Conteúdo</button>
      </div>
      <p style="font-size: 13px; color: var(--text-muted); margin-bottom: 16px;">
        Os despachos são 100% simulados em tela. Veja como cada handler da biblioteca renderiza o incidente antes da transmissão física:
      </p>

      <!-- Tabs Navigation -->
      <div class="tabs-nav">
        <button class="tab-btn active" onclick="switchTab('emailHtml', this)">
          <span class="tab-indicator"></span>
          <span>✉️ Email HTML</span>
        </button>
        <button class="tab-btn" onclick="switchTab('emailText', this)">
          <span class="tab-indicator"></span>
          <span>📄 Email Texto Puro</span>
        </button>
        <button class="tab-btn" onclick="switchTab('github', this)">
          <span class="tab-indicator"></span>
          <span>🐙 GitHub Issue</span>
        </button>
        <button class="tab-btn" onclick="switchTab('slack', this)">
          <span class="tab-indicator"></span>
          <span>💬 Slack Webhook</span>
        </button>
        <button class="tab-btn" onclick="switchTab('discord', this)">
          <span class="tab-indicator"></span>
          <span>🤖 Discord Webhook</span>
        </button>
        <button class="tab-btn" onclick="switchTab('filelog', this)">
          <span class="tab-indicator"></span>
          <span>📁 Log em Arquivo</span>
        </button>
        <button class="tab-btn" onclick="switchTab('database', this)">
          <span class="tab-indicator"></span>
          <span>🗄️ Banco de Dados (SQL)</span>
        </button>
      </div>

      <!-- Tab Panes -->
      <div id="pane-emailHtml" class="tab-pane active">
        <div class="preview-card" style="padding: 0; overflow: hidden;">
          <iframe id="emailHtmlFrame" style="width: 100%; height: 420px; border: none; background: #0B0E14;" title="Email HTML Preview"></iframe>
        </div>
      </div>

      <div id="pane-emailText" class="tab-pane">
        <div class="preview-card">
          <pre id="emailTextPre" style="font-family: var(--font-mono); font-size: 12px; color: #94A3B8; white-space: pre-wrap; line-height: 1.6;"></pre>
        </div>
      </div>

      <div id="pane-github" class="tab-pane">
        <div class="preview-card github-preview-box">
          <div style="font-size: 16px; font-weight: 700; color: #FFF; margin-bottom: 12px; padding-bottom: 8px; border-bottom: 1px solid #30363D;" id="githubTitle">
            [ERROR] Incidente Detectado
          </div>
          <pre id="githubMarkdownPre" style="font-family: var(--font-mono); font-size: 12px; color: #C9D1D9; white-space: pre-wrap; line-height: 1.6;"></pre>
        </div>
      </div>

      <div id="pane-slack" class="tab-pane">
        <div class="preview-card" style="background: #1A1D21;">
          <div class="slack-preview-box">
            <div id="slackText" style="font-weight: 700; color: #FFF; margin-bottom: 8px;">🚨 [ERROR] Incidente Detectado</div>
            <div id="slackBody" style="font-size: 13px; color: #D1D2D3; margin-bottom: 12px;">Falha de comunicação no gateway</div>
            <div id="slackFields" style="display: grid; grid-template-columns: 1fr 1fr; gap: 8px; font-size: 12px; background: rgba(0,0,0,0.2); padding: 10px; border-radius: 4px;"></div>
          </div>
          <div style="margin-top: 14px; font-size: 11px; color: #6E7681; font-family: var(--font-mono);">
            Payload JSON:
            <pre id="slackJsonPre" style="margin-top: 6px; color: #8B949E; white-space: pre-wrap;"></pre>
          </div>
        </div>
      </div>

      <div id="pane-discord" class="tab-pane">
        <div class="preview-card" style="background: #1E1F22;">
          <div class="discord-preview-box">
            <div id="discordHeader" style="font-weight: 700; color: #FFF; margin-bottom: 8px;">🚨 **[ERROR] Incidente Detectado**</div>
            <div id="discordTitle" style="font-size: 14px; font-weight: 600; color: #00A8FC; margin-bottom: 8px;">Título do Embed</div>
            <div id="discordDesc" style="font-family: var(--font-mono); font-size: 11px; background: #2B2D31; padding: 8px; border-radius: 4px; margin-bottom: 10px; color: #DBDEE1;"></div>
            <div id="discordFields" style="display: grid; grid-template-columns: 1fr 1fr; gap: 8px; font-size: 12px;"></div>
          </div>
          <div style="margin-top: 14px; font-size: 11px; color: #6E7681; font-family: var(--font-mono);">
            Payload JSON:
            <pre id="discordJsonPre" style="margin-top: 6px; color: #8B949E; white-space: pre-wrap;"></pre>
          </div>
        </div>
      </div>

      <div id="pane-filelog" class="tab-pane">
        <div class="preview-card" style="background: #090E17;">
          <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 14px; padding-bottom: 10px; border-bottom: 1px solid var(--border); flex-wrap: wrap; gap: 8px;">
            <div style="display: flex; align-items: center; gap: 8px;">
              <span style="font-size: 16px;">📁</span>
              <span id="filelogPath" style="font-family: var(--font-mono); font-size: 12px; color: var(--cyan); word-break: break-all;">storage/logs/CRITICAL_20261025.log</span>
            </div>
            <span style="font-size: 11px; color: #34D399; background: rgba(16, 185, 129, 0.15); border: 1px solid rgba(16, 185, 129, 0.3); padding: 3px 8px; border-radius: 4px; font-weight: 700;">LOCK_EX Ativo</span>
          </div>
          <pre id="filelogPre" style="font-family: var(--font-mono); font-size: 12px; color: #CBD5E1; white-space: pre-wrap; line-height: 1.6; max-height: 420px; overflow-y: auto;"></pre>
        </div>
      </div>

      <div id="pane-database" class="tab-pane">
        <div class="preview-card" style="background: #0D1321;">
          <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 14px; padding-bottom: 10px; border-bottom: 1px solid var(--border); flex-wrap: wrap; gap: 8px;">
            <div style="display: flex; align-items: center; gap: 8px;">
              <span style="font-size: 16px;">🗄️</span>
              <span style="font-family: var(--font-heading); font-size: 14px; color: #FFF; font-weight: 700;">Tabela: <span style="color: var(--cyan); font-family: var(--font-mono);">error_tracker</span></span>
            </div>
            <span style="font-size: 11px; color: var(--cyan); background: rgba(0, 242, 254, 0.15); border: 1px solid rgba(0, 242, 254, 0.3); padding: 3px 8px; border-radius: 4px; font-weight: 700;">Upsert com Deduplicação Automática</span>
          </div>

          <div style="margin-bottom: 16px;">
            <div style="font-size: 11px; font-weight: 700; color: var(--text-muted); text-transform: uppercase; margin-bottom: 6px; letter-spacing: 0.5px;">
              Instrução SQL Executada (MySQL / MariaDB / SQLite):
            </div>
            <pre id="dbQueryPre" style="font-family: var(--font-mono); font-size: 11px; background: #080C14; border: 1px solid var(--border); border-radius: var(--radius-sm); padding: 12px; color: #38BDF8; white-space: pre-wrap; line-height: 1.5;"></pre>
          </div>

          <div style="margin-bottom: 16px;">
            <div style="font-size: 11px; font-weight: 700; color: var(--text-muted); text-transform: uppercase; margin-bottom: 6px; letter-spacing: 0.5px;">
              Registro Persistido (Colunas Estruturadas & Valores):
            </div>
            <div id="dbFieldsGrid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 10px;"></div>
          </div>

          <div>
            <div style="font-size: 11px; font-weight: 700; color: var(--text-muted); text-transform: uppercase; margin-bottom: 6px; letter-spacing: 0.5px;">
              Payload Sanitizado em JSON (Coluna payload):
            </div>
            <pre id="dbPayloadPre" style="font-family: var(--font-mono); font-size: 11px; background: #080C14; border: 1px solid var(--border); border-radius: var(--radius-sm); padding: 10px 12px; color: #94A3B8; max-height: 140px; overflow-y: auto; white-space: pre-wrap;"></pre>
          </div>
        </div>
      </div>
    </div>

    <!-- Section: LocalStorage Audit Trail -->
    <div class="card history-card">
      <div class="card-header">
        <div class="card-title">
          <span class="icon">📜</span>
          <span>Histórico Local de Incidentes (Zero-Disk)</span>
        </div>
        <button class="btn-action-sm" onclick="clearLocalHistory()">🗑️ Limpar Histórico</button>
      </div>
      <p style="font-size: 13px; color: var(--text-muted); margin-bottom: 16px;">
        Os eventos gerados são salvos exclusivamente no <strong>localStorage</strong> deste navegador. Nenhum log persiste no disco do servidor.
      </p>

      <div class="table-wrap">
        <table>
          <thead>
            <tr>
              <th>Timestamp</th>
              <th>Severidade</th>
              <th>Mensagem</th>
              <th>Arquivo : Linha</th>
              <th>Hash Único</th>
              <th>Ações</th>
            </tr>
          </thead>
          <tbody id="historyTableBody">
            <tr>
              <td colspan="6" style="text-align: center; color: var(--text-dim); padding: 24px;">
                Nenhum incidente registrado ainda nesta sessão. Dispare um erro acima!
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </main>

  <!-- Footer -->
  <footer>
    <div class="container">
      <p>
        <strong>HeliomarPM ErrorTracker</strong> — Desenvolvido por <a href="https://github.com/heliomarpm" target="_blank" rel="noopener">Heliomar P. Marques</a> sob a licença MIT.
      </p>
      <p style="margin-top: 6px; font-size: 12px;">
        Demo otimizado para Vercel Serverless com PHP 8.2+. Zero arquivos de log gerados no disco do servidor.
      </p>
    </div>
  </footer>

  <!-- Client-side Logic -->
  <script>
    const STORAGE_KEY = 'errortracker_demo_incidents';
    let currentIncidentData = null;
    let activeTabId = 'emailHtml';

    // Inicialização ao carregar página
    document.addEventListener('DOMContentLoaded', () => {
      loadHistoryTable();
      // Dispara um notice inicial leve para popular os previews
      triggerIncident('info', true);
    });

    // Exibe toast notification
    function showToast(msg, icon = '🛡️') {
      const toast = document.getElementById('toast');
      document.getElementById('toastIcon').textContent = icon;
      document.getElementById('toastMsg').textContent = msg;
      toast.classList.add('show');
      setTimeout(() => {
        toast.classList.remove('show');
      }, 3500);
    }

    // Alterna abas de visualização
    function switchTab(tabId, btn) {
      activeTabId = tabId;
      document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
      document.querySelectorAll('.tab-pane').forEach(p => p.classList.remove('active'));

      btn.classList.add('active');
      const pane = document.getElementById(`pane-${tabId}`);
      if (pane) pane.classList.add('active');
    }

    // Copia conteúdo da aba ativa
    function copyActivePreview() {
      if (!currentIncidentData) return;
      let content = '';

      if (activeTabId === 'emailHtml') {
        content = currentIncidentData.previews?.email_html || '';
      } else if (activeTabId === 'emailText') {
        content = currentIncidentData.previews?.email_text || '';
      } else if (activeTabId === 'github') {
        content = (currentIncidentData.previews?.github_title || '') + "\n\n" + (currentIncidentData.previews?.github_markdown || '');
      } else if (activeTabId === 'slack') {
        content = JSON.stringify(currentIncidentData.previews?.slack_json || {}, null, 2);
      } else if (activeTabId === 'discord') {
        content = JSON.stringify(currentIncidentData.previews?.discord_json || {}, null, 2);
      } else if (activeTabId === 'filelog') {
        content = currentIncidentData.previews?.filelog?.content || '';
      } else if (activeTabId === 'database') {
        const db = currentIncidentData.previews?.database || {};
        content = "-- INSTRUÇÃO SQL:\n" + (db.query_mysql || db.query_sqlite || '') + "\n\n-- REGISTRO:\n" + JSON.stringify(db.record || {}, null, 2);
      }

      navigator.clipboard.writeText(content).then(() => {
        showToast('Conteúdo copiado para a área de transferência!', '📋');
      }).catch(() => {
        showToast('Falha ao copiar.', '⚠️');
      });
    }

    // Helper resiliente para chamadas à API da Demo (compatível com local php -S e Vercel)
    async function callApi(payload) {
      const endpoints = ['api.php', '/api', '/demo/api.php'];
      let lastError = null;

      for (const endpoint of endpoints) {
        try {
          const res = await fetch(endpoint, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
            body: JSON.stringify(payload)
          });

          const contentType = res.headers.get('content-type') || '';
          if (res.ok && contentType.includes('application/json')) {
            const data = await res.json();
            if (data.status === 'success') {
              return data;
            }
            throw new Error(data.message || 'Erro reportado pela API.');
          }
        } catch (e) {
          lastError = e;
        }
      }

      throw lastError || new Error('Não foi possível obter resposta JSON válida da API.');
    }

    // Dispara Incidente via API
    async function triggerIncident(action, isInitial = false) {
      try {
        const data = await callApi({ action: action });
        currentIncidentData = data;
        updatePreviews(data);

        if (!isInitial) {
          saveIncidentToLocalStorage(data.context);
          incrementStat('statSimulations');
          showTriggerResult(data.context);
          showToast(`Incidente [${data.context.severity}] interceptado com sucesso!`, '⚡');
        }
      } catch (err) {
        console.error('Erro ao acionar API:', err);
        showToast('Falha na comunicação com a API da demo.', '⚠️');
      }
    }

    // Exibe caixa de resultado rápido do trigger
    function showTriggerResult(ctx) {
      const box = document.getElementById('triggerResultBox');
      const sev = document.getElementById('triggerSeverity');
      box.style.display = 'block';
      sev.textContent = ctx.severity;
      sev.className = `badge-severity ${ctx.severity}`;
      document.getElementById('triggerTime').textContent = new Date().toLocaleTimeString();
      document.getElementById('triggerMsg').textContent = ctx.message;
      document.getElementById('triggerHash').textContent = ctx.hash;
    }

    // Simulação Anti-Flood (50 disparos simultâneos)
    async function runAntiFloodStressTest() {
      const btn = document.getElementById('btnFlood');
      const progressBar = document.getElementById('floodProgress');
      btn.disabled = true;
      btn.innerHTML = '<span>⚡ Executando rajada de 50 erros...</span>';
      progressBar.style.width = '30%';

      try {
        const data = await callApi({ action: 'test_flood' });
        progressBar.style.width = '80%';

        setTimeout(() => {
          progressBar.style.width = '100%';
          document.getElementById('floodTotal').textContent = data.total_triggered;
          document.getElementById('floodSent').textContent = data.dispatched_count;
          document.getElementById('floodSuppressed').textContent = data.suppressed_count;

          incrementStat('statSimulations', data.total_triggered);
          incrementStat('statFloodsSuppressed', data.suppressed_count);

          if (data.context) {
            saveIncidentToLocalStorage({
              ...data.context,
              message: `[Anti-Flood Test] ${data.context.message}`
            });
          }

          if (data.previews) {
            currentIncidentData = data;
            updatePreviews(data);
          }

          btn.disabled = false;
          btn.innerHTML = '<span>🚀 Disparar Rajada de 50 Erros em Loop</span>';
          showToast(`Anti-Flood: 50 disparos! Apenas 1 enviado, 49 silenciados (${data.efficiency_percent} economia)`, '🛡️');
        }, 400);

      } catch (err) {
        btn.disabled = false;
        btn.innerHTML = '<span>🚀 Disparar Rajada de 50 Erros em Loop</span>';
        console.error(err);
        showToast('Falha no teste Anti-Flood.', '⚠️');
      }
    }

    // Formulário de Sanitização
    async function handleSanitizeSubmit(e) {
      e.preventDefault();
      const btn = document.getElementById('btnSanitize');
      btn.disabled = true;

      const payload = {
        action: 'sanitization',
        username: document.getElementById('inputUsername').value,
        password: document.getElementById('inputPassword').value,
        token: document.getElementById('inputToken').value,
        cpf: document.getElementById('inputCpf').value,
        credit_card: document.getElementById('inputCard').value,
        email: document.getElementById('inputEmail').value
      };

      // Atualiza visualmente o payload exposto sem regex quebradiço
      const rawHtml = '{\n' +
        `  "username": "${escapeHtml(payload.username)}",\n` +
        `  "password": "<span class='exposed'>${escapeHtml(payload.password)}</span>",\n` +
        `  "token": "<span class='exposed'>${escapeHtml(payload.token)}</span>",\n` +
        `  "cpf": "<span class='exposed'>${escapeHtml(payload.cpf)}</span>",\n` +
        `  "credit_card": "<span class='exposed'>${escapeHtml(payload.credit_card)}</span>",\n` +
        `  "email": "<span class='exposed'>${escapeHtml(payload.email)}</span>"\n` +
        '}';
      document.getElementById('rawPayloadView').innerHTML = rawHtml;

      try {
        const data = await callApi(payload);
        currentIncidentData = data;
        updatePreviews(data);
        saveIncidentToLocalStorage(data.context);
        incrementStat('statSimulations');

        // Atualiza visualmente o payload protegido usando os dados reais sanitizados
        const san = data.context?.sanitized_post || data.context?.sanitized_payload?.post || {};
        const sanHtml = '{\n' +
          `  "username": "${escapeHtml(san.username || payload.username)}",\n` +
          `  "password": "<span class='masked'>${escapeHtml(san.password || '********')}</span>",\n` +
          `  "token": "<span class='masked'>${escapeHtml(san.token || '********')}</span>",\n` +
          `  "cpf": "<span class='masked'>${escapeHtml(san.cpf || '***.***.***-**')}</span>",\n` +
          `  "credit_card": "<span class='masked'>${escapeHtml(san.credit_card || '**** **** **** ****')}</span>",\n` +
          `  "email": "<span class='masked'>${escapeHtml(san.email || '')}</span>"\n` +
          '}';
        document.getElementById('sanitizedPayloadView').innerHTML = sanHtml;

        showToast('Dados sensíveis mascarados com sucesso!', '🔒');
      } catch (err) {
        console.error('Erro na sanitização:', err);
        showToast('Falha ao sanitizar payload: ' + (err.message || ''), '⚠️');
      } finally {
        btn.disabled = false;
      }
    }

    // Atualiza os painéis de Preview dos Handlers
    function updatePreviews(data) {
      if (!data.previews) return;

      // 1. Email HTML (Iframe)
      const frame = document.getElementById('emailHtmlFrame');
      if (frame && data.previews.email_html) {
        frame.srcdoc = data.previews.email_html;
      }

      // 2. Email Texto
      const textPre = document.getElementById('emailTextPre');
      if (textPre) {
        textPre.textContent = data.previews.email_text || 'Sem prévia de texto puro.';
      }

      // 3. GitHub Issue
      const ghTitle = document.getElementById('githubTitle');
      const ghPre = document.getElementById('githubMarkdownPre');
      if (ghTitle) ghTitle.textContent = data.previews.github_title || '[ERROR] Incidente';
      if (ghPre) ghPre.textContent = data.previews.github_markdown || '';

      // 4. Slack
      const slack = data.previews.slack_json || {};
      const slackText = document.getElementById('slackText');
      const slackBody = document.getElementById('slackBody');
      const slackFields = document.getElementById('slackFields');
      const slackJsonPre = document.getElementById('slackJsonPre');

      if (slackText) slackText.textContent = slack.text || '';
      if (slack.attachments && slack.attachments[0]) {
        const att = slack.attachments[0];
        if (slackBody) slackBody.textContent = att.title || '';
        if (slackFields) {
          slackFields.innerHTML = (att.fields || []).map(f => `
            <div>
              <div style="color: #8B949E; font-size: 11px;">${f.title}</div>
              <div style="color: #FFF; font-weight: 600;">${f.value}</div>
            </div>
          `).join('');
        }
      }
      if (slackJsonPre) slackJsonPre.textContent = JSON.stringify(slack, null, 2);

      // 5. Discord
      const discord = data.previews.discord_json || {};
      const discordHeader = document.getElementById('discordHeader');
      const discordTitle = document.getElementById('discordTitle');
      const discordDesc = document.getElementById('discordDesc');
      const discordFields = document.getElementById('discordFields');
      const discordJsonPre = document.getElementById('discordJsonPre');

      if (discordHeader) discordHeader.textContent = discord.content || '';
      if (discord.embeds && discord.embeds[0]) {
        const emb = discord.embeds[0];
        if (discordTitle) discordTitle.textContent = emb.title || '';
        if (discordDesc) discordDesc.textContent = emb.description || '';
        if (discordFields) {
          discordFields.innerHTML = (emb.fields || []).map(f => `
            <div>
              <div style="color: #949BA4; font-size: 11px;">${f.name}</div>
              <div style="color: #FFF; font-weight: 600;">${f.value}</div>
            </div>
          `).join('');
        }
      }
      if (discordJsonPre) discordJsonPre.textContent = JSON.stringify(discord, null, 2);

      // 6. Filelog
      const filelog = data.previews.filelog || {};
      const filelogPath = document.getElementById('filelogPath');
      const filelogPre = document.getElementById('filelogPre');
      if (filelogPath) filelogPath.textContent = filelog.filename || 'storage/logs/incident.log';
      if (filelogPre) filelogPre.textContent = filelog.content || 'Sem entrada de log gerada.';

      // 7. Database
      const db = data.previews.database || {};
      const dbQueryPre = document.getElementById('dbQueryPre');
      const dbFieldsGrid = document.getElementById('dbFieldsGrid');
      const dbPayloadPre = document.getElementById('dbPayloadPre');

      if (dbQueryPre) dbQueryPre.textContent = (db.query_mysql || db.query_sqlite || '').trim();
      if (dbPayloadPre) {
        const rawPayload = db.record?.payload || '{}';
        try {
          dbPayloadPre.textContent = JSON.stringify(JSON.parse(rawPayload), null, 2);
        } catch {
          dbPayloadPre.textContent = rawPayload;
        }
      }
      if (dbFieldsGrid && db.record) {
        const rec = db.record;
        const keysToDisplay = [
          { key: 'hash', label: 'Hash (Deduplicação)' },
          { key: 'severity', label: 'Severidade' },
          { key: 'error_class', label: 'Classe da Exceção' },
          { key: 'previous_class', label: 'Causa Raiz' },
          { key: 'message', label: 'Mensagem' },
          { key: 'file', label: 'Arquivo Relativo' },
          { key: 'line', label: 'Linha' },
          { key: 'method', label: 'Método HTTP' },
          { key: 'uri', label: 'Rota / URI' },
          { key: 'user_name', label: 'Usuário' },
          { key: 'incidents', label: 'Reincidências' }
        ];

        dbFieldsGrid.innerHTML = keysToDisplay.map(item => {
          const val = rec[item.key];
          const displayVal = val === null || val === undefined ? '<span style="color: var(--text-dim); font-style: italic;">null</span>' : escapeHtml(String(val));
          return `
            <div style="background: rgba(14, 21, 36, 0.7); border: 1px solid var(--border); border-radius: 6px; padding: 8px 12px;">
              <div style="color: var(--text-muted); font-size: 11px; text-transform: uppercase;">${item.label}</div>
              <div style="color: #FFF; font-family: var(--font-mono); font-size: 12px; word-break: break-all; margin-top: 2px;">${displayVal}</div>
            </div>
          `;
        }).join('');
      }
    }

    let selectedIncidentForModal = null;

    // Armazenamento em localStorage (Zero-disk no servidor)
    function saveIncidentToLocalStorage(ctx) {
      if (!ctx) return;
      try {
        const incidents = JSON.parse(localStorage.getItem(STORAGE_KEY) || '[]');
        incidents.unshift({
          id: 'INC-' + Math.random().toString(36).substring(2, 8).toUpperCase(),
          timestamp: new Date().toLocaleTimeString(),
          fullDate: new Date().toLocaleString(),
          severity: ctx.severity || 'INFO',
          message: ctx.message || 'Sem mensagem',
          file: ctx.file || '---',
          shortFile: ctx.file ? `${ctx.file.split(/[\\/]/).pop()} : ${ctx.line || 0}` : '---',
          line: ctx.line || 0,
          code: ctx.code || 0,
          hash: ctx.hash || '---',
          trace: ctx.trace || 'Sem stack trace registrado para este tipo de evento.',
          sanitized_payload: ctx.sanitized_post || ctx.sanitized_payload || {},
          previews: currentIncidentData?.previews || null
        });
        // Mantém os últimos 20 incidentes
        if (incidents.length > 20) incidents.pop();
        localStorage.setItem(STORAGE_KEY, JSON.stringify(incidents));
        loadHistoryTable();
      } catch (e) {
        console.warn('Erro ao salvar no localStorage:', e);
      }
    }

    // Carrega tabela de histórico local
    function loadHistoryTable() {
      const tbody = document.getElementById('historyTableBody');
      try {
        const incidents = JSON.parse(localStorage.getItem(STORAGE_KEY) || '[]');
        if (incidents.length === 0) {
          tbody.innerHTML = `
            <tr>
              <td colspan="6" style="text-align: center; color: var(--text-dim); padding: 24px;">
                Nenhum incidente registrado ainda nesta sessão. Dispare um erro acima!
              </td>
            </tr>
          `;
          return;
        }

        tbody.innerHTML = incidents.map((item, index) => `
          <tr>
            <td style="font-family: var(--font-mono); font-size: 12px; color: var(--text-muted);">${item.timestamp}</td>
            <td><span class="badge-severity ${item.severity}">${item.severity}</span></td>
            <td style="font-weight: 600; color: #FFF;">${escapeHtml(item.message)}</td>
            <td style="font-family: var(--font-mono); font-size: 12px; color: var(--text-dim);">${escapeHtml(item.shortFile || item.file)}</td>
            <td style="font-family: var(--font-mono); font-size: 11px; color: var(--cyan);">${(item.hash || '---').substring(0, 10)}...</td>
            <td>
              <button class="btn-action-sm" onclick="openIncidentDetails(${index})">🔍 Detalhes</button>
            </td>
          </tr>
        `).join('');
      } catch (e) {
        console.warn(e);
      }
    }

    // Abre o Modal com Detalhes Completos do Incidente
    function openIncidentDetails(index) {
      try {
        const incidents = JSON.parse(localStorage.getItem(STORAGE_KEY) || '[]');
        const item = incidents[index];
        if (!item) return;

        selectedIncidentForModal = item;

        const sevBadge = document.getElementById('modalSeverityBadge');
        sevBadge.textContent = item.severity;
        sevBadge.className = `badge-severity ${item.severity}`;

        document.getElementById('modalIncidentId').textContent = item.id || `INC-${(item.hash || '').substring(0, 6).toUpperCase()}`;
        document.getElementById('modalTime').textContent = item.fullDate || item.timestamp;
        document.getElementById('modalMessage').textContent = item.message;
        document.getElementById('modalFile').textContent = `${item.file} (linha ${item.line})`;
        document.getElementById('modalHash').textContent = item.hash;
        document.getElementById('modalTrace').textContent = item.trace || 'Sem trace.';
        document.getElementById('modalPayload').textContent = JSON.stringify(item.sanitized_payload || {}, null, 2);

        document.getElementById('incidentModal').classList.add('show');
      } catch (err) {
        console.error('Erro ao abrir detalhes:', err);
      }
    }

    // Fecha o modal de detalhes
    function closeIncidentModal() {
      document.getElementById('incidentModal').classList.remove('show');
    }

    function handleModalBackdropClick(e) {
      if (e.target.id === 'incidentModal') {
        closeIncidentModal();
      }
    }

    // Carrega o incidente do modal nas abas de preview
    function loadIncidentIntoPreviews() {
      if (!selectedIncidentForModal) return;

      if (selectedIncidentForModal.previews) {
        currentIncidentData = {
          context: selectedIncidentForModal,
          previews: selectedIncidentForModal.previews
        };
        updatePreviews(currentIncidentData);
      } else {
        showTriggerResult(selectedIncidentForModal);
      }

      closeIncidentModal();
      const previewCard = document.querySelector('.tabs-nav');
      if (previewCard) {
        previewCard.scrollIntoView({ behavior: 'smooth' });
      }
      showToast('Incidente carregado nos previews de notificação!', '🔄');
    }

    function copyModalTrace() {
      if (!selectedIncidentForModal) return;
      navigator.clipboard.writeText(selectedIncidentForModal.trace || '').then(() => {
        showToast('Stack trace copiado!', '📋');
      });
    }

    function copyModalPayload() {
      if (!selectedIncidentForModal) return;
      const json = JSON.stringify(selectedIncidentForModal.sanitized_payload || {}, null, 2);
      navigator.clipboard.writeText(json).then(() => {
        showToast('Payload JSON copiado!', '📋');
      });
    }

    // Fecha modal no ESC
    document.addEventListener('keydown', (e) => {
      if (e.key === 'Escape') {
        closeIncidentModal();
      }
    });

    // Limpa histórico local
    function clearLocalHistory() {
      localStorage.removeItem(STORAGE_KEY);
      loadHistoryTable();
      showToast('Histórico local limpo com sucesso!', '🗑️');
    }

    // Incrementa contadores
    function incrementStat(id, qty = 1) {
      const el = document.getElementById(id);
      if (!el) return;
      const current = parseInt(el.textContent.replace(/[^0-9]/g, '')) || 0;
      el.textContent = current + qty;
    }

    function escapeHtml(text) {
      return String(text ?? '')
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
    }
  </script>
</body>
</html>
