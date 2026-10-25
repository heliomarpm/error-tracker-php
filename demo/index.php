<!DOCTYPE html>
<html lang="pt-BR">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>ErrorTracker — Sandbox Interativo & Console de Engenharia</title>
  <meta name="description"
    content="Sandbox interativo para desenvolvedores: teste interceptação de exceções, anti-flood throttle via cache PSR-16, sanitização LGPD e despacho para múltiplos handlers.">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link
    href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=JetBrains+Mono:wght@400;500;600;700&family=Outfit:wght@600;700;800&display=swap"
    rel="stylesheet">
  <style>
    :root {
      --bg: #070A10;
      --bg-surface: #0D1322;
      --bg-card: rgba(15, 23, 40, 0.85);
      --bg-card-hover: rgba(22, 33, 56, 0.9);
      --border: rgba(255, 255, 255, 0.09);
      --border-accent: rgba(0, 242, 254, 0.35);
      --cyan: #00F2FE;
      --cyan-glow: rgba(0, 242, 254, 0.3);
      --violet: #8A2BE2;
      --green: #10B981;
      --amber: #F59E0B;
      --red: #F85149;
      --text: #F1F5F9;
      --text-muted: #94A3B8;
      --text-dim: #64748B;
      --radius: 12px;
      --radius-sm: 6px;
      --radius-lg: 16px;
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
        radial-gradient(circle at 10% 10%, rgba(0, 242, 254, 0.06) 0%, transparent 40%),
        radial-gradient(circle at 90% 20%, rgba(138, 43, 226, 0.06) 0%, transparent 45%);
      color: var(--text);
      font-family: var(--font-body);
      min-height: 100vh;
      line-height: 1.5;
      overflow-x: hidden;
    }

    /* Container */
    .container {
      max-width: 1600px;
      margin: 0 auto;
      padding: 0 20px;
    }

    /* Header */
    header {
      padding: 16px 0;
      border-bottom: 1px solid var(--border);
      position: sticky;
      top: 0;
      background: rgba(7, 10, 16, 0.9);
      backdrop-filter: blur(14px);
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
      gap: 12px;
      text-decoration: none;
    }

    .brand-logo {
      width: 38px;
      height: 38px;
      border-radius: 10px;
      object-fit: cover;
      box-shadow: 0 0 16px var(--cyan-glow);
      border: 1px solid rgba(0, 242, 254, 0.4);
      background: #0E1524;
    }

    .brand-title {
      font-family: var(--font-heading);
      font-size: 19px;
      font-weight: 800;
      color: #FFF;
      letter-spacing: -0.5px;
      display: flex;
      align-items: center;
      gap: 8px;
    }

    .brand-title span.highlight {
      background: linear-gradient(135deg, var(--cyan) 0%, #4FACFE 100%);
      -webkit-background-clip: text;
      -webkit-text-fill-color: transparent;
    }

    .tag-sandbox {
      font-size: 12px;
      font-weight: 700;
      text-transform: uppercase;
      letter-spacing: 0.5px;
      padding: 2px 7px;
      border-radius: 4px;
      background: rgba(0, 242, 254, 0.12);
      color: var(--cyan);
      border: 1px solid rgba(0, 242, 254, 0.3);
    }

    /* Hero */
    .hero {
      padding: 8px 0 8px;
      /* text-align: center; */
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
      font-size: 14px;
      font-weight: 700;
      text-transform: uppercase;
      letter-spacing: 0.5px;
      margin-bottom: 20px;
    }

    .hero h2 {
      font-family: var(--font-heading);
      font-size: 30px;
      font-weight: 900;
      line-height: 1.15;
      letter-spacing: -1px;
      margin-bottom: 16px;
      /* max-width: 860px; */
      margin-left: auto;
      margin-right: auto;
    }

    .hero h2 .grad-text {
      background: linear-gradient(135deg, #FFF 0%, var(--cyan) 50%, var(--violet) 100%);
      -webkit-background-clip: text;
      -webkit-text-fill-color: transparent;
    }

    .hero p {
      font-size: 14px;
      color: var(--text-muted);
      /* max-width: 680px; */
      margin: 0 auto 18px;
      line-height: 1.6;
    }


    /* Terminal Pill in Header */
    .terminal-pill {
      display: flex;
      align-items: center;
      gap: 10px;
      background: rgba(13, 19, 34, 0.85);
      border: 1px solid var(--border);
      padding: 6px 14px;
      border-radius: 8px;
      font-family: var(--font-mono);
      font-size: 12px;
      color: #CBD5E1;
      transition: border-color 0.2s;
    }

    .terminal-pill:hover {
      border-color: var(--cyan);
    }

    .terminal-pill span.prompt {
      color: var(--cyan);
      font-weight: 700;
    }

    .terminal-pill button.btn-copy {
      background: rgba(255, 255, 255, 0.06);
      border: 1px solid var(--border);
      color: #94A3B8;
      border-radius: 4px;
      padding: 2px 8px;
      font-size: 11px;
      font-weight: 600;
      cursor: pointer;
      transition: all 0.2s;
    }

    .terminal-pill button.btn-copy:hover {
      color: #FFF;
      background: rgba(0, 242, 254, 0.2);
      border-color: var(--cyan);
    }

    .nav-links {
      display: flex;
      align-items: center;
      gap: 12px;
    }

    .nav-btn {
      display: inline-flex;
      align-items: center;
      gap: 6px;
      padding: 6px 12px;
      border-radius: 6px;
      font-size: 12px;
      font-weight: 600;
      text-decoration: none;
      color: var(--text-muted);
      background: rgba(255, 255, 255, 0.04);
      border: 1px solid var(--border);
      transition: all 0.2s;
    }

    .nav-btn:hover {
      color: #FFF;
      border-color: rgba(255, 255, 255, 0.2);
    }

    /* Subheader & Stats Bar */
    .subbar {
      padding: 16px 0;
      border-bottom: 1px solid var(--border);
      background: rgba(13, 19, 34, 0.3);
    }

    .subbar-inner {
      display: grid;
      grid-template-columns: repeat(4, 1fr);
      gap: 14px;
    }

    .stat-box {
      background: var(--bg-card);
      border: 1px solid var(--border);
      border-radius: var(--radius-sm);
      padding: 12px 16px;
      display: flex;
      align-items: center;
      gap: 12px;
      transition: transform 0.2s, border-color 0.2s;
    }

    .stat-box:hover {
      border-color: rgba(0, 242, 254, 0.3);
      transform: translateY(-1px);
    }

    .stat-icon {
      font-size: 20px;
      flex-shrink: 0;
    }

    .stat-val {
      font-family: var(--font-mono);
      font-size: 22px;
      font-weight: 800;
      color: #FFF;
      line-height: 1.1;
    }

    .stat-label {
      font-size: 12px;
      color: var(--text-muted);
      text-transform: uppercase;
      letter-spacing: 0.5px;
      font-weight: 600;
      margin-top: 2px;
    }

    /* Main Workbench Layout (2 Columns) */
    .workbench-grid {
      display: grid;
      grid-template-columns: 420px 1fr;
      gap: 20px;
      margin-top: 20px;
      margin-bottom: 40px;
      align-items: start;
    }

    /* Sidebar / Left Column (Triggers & Controls) */
    .workbench-sidebar {
      display: flex;
      flex-direction: column;
      gap: 18px;
    }

    .panel {
      background: var(--bg-card);
      border: 1px solid var(--border);
      border-radius: var(--radius);
      padding: 20px;
      position: relative;
    }

    .panel-header {
      display: flex;
      align-items: center;
      justify-content: space-between;
      margin-bottom: 12px;
    }

    .panel-title {
      font-family: var(--font-heading);
      font-size: 15px;
      font-weight: 700;
      color: #FFF;
      display: flex;
      align-items: center;
      gap: 8px;
    }

    .panel-badge {
      font-size: 10px;
      font-family: var(--font-mono);
      font-weight: 700;
      padding: 2px 6px;
      border-radius: 4px;
      background: rgba(255, 255, 255, 0.05);
      color: var(--text-muted);
      border: 1px solid var(--border);
    }

    .panel-desc {
      font-size: 14px;
      color: var(--text-muted);
      margin-bottom: 16px;
      line-height: 1.5;
    }

    /* Triggers Grid (2x2) */
    .triggers-grid {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 10px;
      margin-bottom: 14px;
    }

    .btn-trigger {
      display: flex;
      flex-direction: column;
      align-items: flex-start;
      gap: 4px;
      padding: 12px;
      border-radius: var(--radius-sm);
      border: 1px solid var(--border);
      background: rgba(255, 255, 255, 0.03);
      cursor: pointer;
      text-align: left;
      transition: all 0.2s ease;
      font-family: var(--font-body);
    }

    .btn-trigger:hover {
      transform: translateY(-2px);
      box-shadow: 0 4px 14px rgba(0, 0, 0, 0.3);
    }

    .btn-trigger .top {
      font-size: 12px;
      font-weight: 700;
      display: flex;
      align-items: center;
      justify-content: space-between;
      width: 100%;
    }

    .btn-trigger .code-tag {
      font-family: var(--font-mono);
      font-size: 9px;
      font-weight: 600;
      opacity: 0.8;
    }

    .btn-trigger .desc {
      font-size: 12px;
      color: var(--text-muted);
      line-height: 1.3;
    }

    .btn-trigger.notice {
      border-left: 3px solid var(--cyan);
    }

    .btn-trigger.notice:hover {
      background: rgba(0, 242, 254, 0.08);
      border-color: var(--cyan);
    }

    .btn-trigger.notice .top {
      color: var(--cyan);
    }

    .btn-trigger.warning {
      border-left: 3px solid var(--amber);
    }

    .btn-trigger.warning:hover {
      background: rgba(245, 158, 11, 0.08);
      border-color: var(--amber);
    }

    .btn-trigger.warning .top {
      color: var(--amber);
    }

    .btn-trigger.error {
      border-left: 3px solid var(--red);
    }

    .btn-trigger.error:hover {
      background: rgba(248, 81, 73, 0.08);
      border-color: var(--red);
    }

    .btn-trigger.error .top {
      color: var(--red);
    }

    .btn-trigger.failsafe {
      border-left: 3px solid var(--violet);
      text-decoration: none;
    }

    .btn-trigger.failsafe:hover {
      background: rgba(138, 43, 226, 0.08);
      border-color: var(--violet);
    }

    .btn-trigger.failsafe .top {
      color: #A78BFA;
    }

    /* Trigger result toast/box */
    .trigger-result-box {
      background: #090E17;
      border: 1px solid var(--border);
      border-radius: var(--radius-sm);
      padding: 12px;
      font-family: var(--font-mono);
      font-size: 11px;
      display: none;
    }

    .trigger-result-box .header-line {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 6px;
    }

    /* Flood rate limiter box */
    .flood-meter {
      background: #090E17;
      border: 1px solid var(--border);
      border-radius: var(--radius-sm);
      padding: 16px;
      margin-bottom: 12px;
      text-align: center;
    }

    .progress-bar-wrap {
      width: 100%;
      height: 8px;
      background: #060910;
      border-radius: 999px;
      overflow: hidden;
      margin-bottom: 14px;
      border: 1px solid var(--border);
    }

    .progress-bar-fill {
      height: 100%;
      width: 0%;
      background: linear-gradient(90deg, var(--cyan) 0%, var(--green) 100%);
      transition: width 0.3s ease;
    }

    .flood-stats-row {
      display: flex;
      justify-content: space-around;
      gap: 12px;
      margin-bottom: 16px;
    }

    .flood-num {
      font-family: var(--font-mono);
      font-size: 24px;
      font-weight: 800;
      line-height: 1;
    }

    .flood-num.total {
      color: #FFF;
    }

    .flood-num.sent {
      color: var(--red);
    }

    .flood-num.suppressed {
      color: var(--green);
    }

    .flood-sublabel {
      font-size: 10px;
      color: var(--text-muted);
      text-transform: uppercase;
      margin-top: 4px;
      letter-spacing: 0.5px;
    }

    .btn-flood {
      width: 100%;
      padding: 11px 16px;
      background: linear-gradient(135deg, rgba(0, 242, 254, 0.15) 0%, rgba(138, 43, 226, 0.15) 100%);
      border: 1px solid var(--border-accent);
      color: #FFF;
      border-radius: var(--radius-sm);
      font-size: 13px;
      font-weight: 700;
      cursor: pointer;
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 8px;
      transition: all 0.2s ease;
    }

    .btn-flood:hover:not(:disabled) {
      background: linear-gradient(135deg, rgba(0, 242, 254, 0.25) 0%, rgba(138, 43, 226, 0.25) 100%);
      box-shadow: 0 0 16px var(--cyan-glow);
    }

    .btn-flood:disabled {
      opacity: 0.6;
      cursor: not-allowed;
    }

    /* Right Column / Main Workspaces */
    .workbench-main {
      background: var(--bg-card);
      border: 1px solid var(--border);
      border-radius: var(--radius);
      overflow: hidden;
      display: flex;
      flex-direction: column;
      min-height: 640px;
    }

    /* Main Tabs Header */
    .main-tabs-header {
      background: rgba(11, 16, 28, 0.95);
      border-bottom: 1px solid var(--border);
      display: flex;
      align-items: center;
      justify-content: space-between;
      padding: 0 16px;
      overflow-x: auto;
    }

    .main-tabs-list {
      display: flex;
      align-items: center;
      gap: 6px;
    }

    .main-tab-btn {
      padding: 14px 16px;
      background: transparent;
      border: none;
      border-bottom: 2px solid transparent;
      color: var(--text-muted);
      font-family: var(--font-body);
      font-size: 13px;
      font-weight: 600;
      cursor: pointer;
      display: flex;
      align-items: center;
      gap: 8px;
      transition: all 0.2s;
      white-space: nowrap;
    }

    .main-tab-btn:hover {
      color: #FFF;
    }

    .main-tab-btn.active {
      color: var(--cyan);
      border-bottom-color: var(--cyan);
      background: rgba(0, 242, 254, 0.04);
    }

    .tab-badge {
      font-family: var(--font-mono);
      font-size: 10px;
      padding: 1px 6px;
      border-radius: 999px;
      background: rgba(255, 255, 255, 0.08);
      color: var(--text-muted);
    }

    .main-tab-btn.active .tab-badge {
      background: rgba(0, 242, 254, 0.2);
      color: var(--cyan);
    }

    /* Workspaces Panes */
    .workspace-pane {
      display: none;
      padding: 22px;
      flex: 1;
    }

    .workspace-pane.active {
      display: flex;
      flex-direction: column;
      animation: paneFadeIn 0.2s ease-in-out;
    }

    @keyframes paneFadeIn {
      from {
        opacity: 0;
        transform: translateY(4px);
      }

      to {
        opacity: 1;
        transform: translateY(0);
      }
    }

    /* Sub-tabs inside Handlers Notification Viewer */
    .subtabs-bar {
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 12px;
      margin-bottom: 16px;
      flex-wrap: wrap;
    }

    .handlers-pills {
      display: flex;
      align-items: center;
      gap: 6px;
      flex-wrap: wrap;
    }

    .handler-pill {
      background: rgba(255, 255, 255, 0.04);
      border: 1px solid var(--border);
      color: var(--text-muted);
      padding: 6px 12px;
      border-radius: 6px;
      font-size: 12px;
      font-weight: 600;
      cursor: pointer;
      display: flex;
      align-items: center;
      gap: 6px;
      transition: all 0.15s;
    }

    .handler-pill:hover {
      color: #FFF;
      background: rgba(255, 255, 255, 0.08);
    }

    .handler-pill.active {
      background: rgba(0, 242, 254, 0.12);
      border-color: var(--cyan);
      color: #FFF;
      box-shadow: 0 0 10px rgba(0, 242, 254, 0.15);
    }

    .actions-group {
      display: flex;
      align-items: center;
      gap: 8px;
    }

    .btn-action-sm {
      background: rgba(255, 255, 255, 0.05);
      border: 1px solid var(--border);
      color: #CBD5E1;
      font-size: 12px;
      font-weight: 600;
      padding: 6px 12px;
      border-radius: 6px;
      cursor: pointer;
      display: flex;
      align-items: center;
      gap: 6px;
      transition: all 0.2s;
    }

    .btn-action-sm:hover {
      background: rgba(0, 242, 254, 0.1);
      border-color: var(--cyan);
      color: #FFF;
    }

    .btn-action-code {
      background: rgba(138, 43, 226, 0.15);
      border: 1px solid rgba(138, 43, 226, 0.4);
      color: #C4B5FD;
      font-size: 12px;
      font-weight: 600;
      padding: 6px 12px;
      border-radius: 6px;
      cursor: pointer;
      display: flex;
      align-items: center;
      gap: 6px;
      transition: all 0.2s;
    }

    .btn-action-code:hover {
      background: rgba(138, 43, 226, 0.25);
      border-color: #A78BFA;
      color: #FFF;
    }

    /* Handler Preview Content Boxes */
    .preview-box {
      display: none;
      flex: 1;
    }

    .preview-box.active {
      display: block;
    }

    .preview-card-wrap {
      background: #090E17;
      border: 1px solid var(--border);
      border-radius: var(--radius-sm);
      min-height: 440px;
      overflow: hidden;
      display: flex;
      flex-direction: column;
    }

    /* Forms */
    .form-group {
      display: flex;
      flex-direction: column;
      gap: 6px;
    }

    .form-group label {
      font-size: 11px;
      font-weight: 700;
      color: var(--text-muted);
      text-transform: uppercase;
      letter-spacing: 0.5px;
    }

    .form-control {
      background: #090E17;
      border: 1px solid var(--border);
      border-radius: var(--radius-sm);
      padding: 9px 12px;
      color: #FFF;
      font-family: var(--font-mono);
      font-size: 12px;
      outline: none;
      transition: border-color 0.2s;
    }

    .form-control:focus {
      border-color: var(--cyan);
      box-shadow: 0 0 8px var(--cyan-glow);
    }

    .comparison-grid {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 16px;
      margin-top: 16px;
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
      padding: 8px 12px;
      font-size: 11px;
      font-weight: 700;
      display: flex;
      align-items: center;
      justify-content: space-between;
      border-bottom: 1px solid var(--border);
      text-transform: uppercase;
      letter-spacing: 0.5px;
    }

    .code-panel-header.danger {
      color: #F87171;
      background: rgba(248, 81, 73, 0.08);
    }

    .code-panel-header.success {
      color: #34D399;
      background: rgba(16, 185, 129, 0.08);
    }

    .code-content {
      padding: 14px;
      font-family: var(--font-mono);
      font-size: 12px;
      color: #E2E8F0;
      white-space: pre-wrap;
      word-break: break-all;
      line-height: 1.6;
      max-height: 280px;
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

    /* History Table */
    .table-wrap {
      overflow-x: auto;
      border-radius: var(--radius-sm);
      border: 1px solid var(--border);
      background: #090E17;
      max-height: 520px;
    }

    table {
      width: 100%;
      border-collapse: collapse;
      text-align: left;
      font-size: 13px;
    }

    th {
      background: var(--bg-surface);
      padding: 12px 14px;
      color: var(--text-muted);
      font-weight: 700;
      text-transform: uppercase;
      font-size: 11px;
      letter-spacing: 0.5px;
      border-bottom: 1px solid var(--border);
      position: sticky;
      top: 0;
      z-index: 10;
    }

    td {
      padding: 12px 14px;
      border-bottom: 1px solid var(--border);
      color: var(--text);
    }

    tr:hover td {
      background: rgba(255, 255, 255, 0.02);
    }

    .badge-severity {
      display: inline-flex;
      align-items: center;
      padding: 2px 8px;
      border-radius: 4px;
      font-size: 10px;
      font-weight: 700;
      text-transform: uppercase;
      letter-spacing: 0.5px;
      font-family: var(--font-mono);
    }

    .badge-severity.INFO {
      background: rgba(0, 242, 254, 0.15);
      color: #38BDF8;
    }

    .badge-severity.NOTICE {
      background: rgba(52, 211, 153, 0.15);
      color: #34D399;
    }

    .badge-severity.WARNING {
      background: rgba(245, 158, 11, 0.15);
      color: #FBBF24;
    }

    .badge-severity.ERROR {
      background: rgba(248, 81, 73, 0.15);
      color: #F87171;
    }

    .badge-severity.CRITICAL {
      background: rgba(239, 68, 68, 0.25);
      color: #EF4444;
    }

    /* Docs / Implementation Code Tabs */
    .docs-nav {
      display: flex;
      align-items: center;
      gap: 6px;
      margin-bottom: 16px;
      flex-wrap: wrap;
    }

    .docs-tab-btn {
      padding: 7px 14px;
      background: rgba(255, 255, 255, 0.04);
      border: 1px solid var(--border);
      border-radius: 6px;
      color: var(--text-muted);
      font-size: 12px;
      font-weight: 600;
      cursor: pointer;
      display: flex;
      align-items: center;
      gap: 6px;
      transition: all 0.2s;
    }

    .docs-tab-btn:hover {
      color: #FFF;
      background: rgba(255, 255, 255, 0.08);
    }

    .docs-tab-btn.active {
      background: rgba(138, 43, 226, 0.15);
      border-color: #8A2BE2;
      color: #E2E8F0;
    }

    .docs-pane {
      display: none;
    }

    .docs-pane.active {
      display: block;
    }

    .snippet-box {
      background: #090E17;
      border: 1px solid var(--border);
      border-radius: var(--radius-sm);
      overflow: hidden;
      position: relative;
    }

    .snippet-header {
      background: rgba(255, 255, 255, 0.03);
      padding: 10px 16px;
      border-bottom: 1px solid var(--border);
      display: flex;
      align-items: center;
      justify-content: space-between;
      font-size: 12px;
      font-weight: 600;
      color: #CBD5E1;
    }

    .snippet-code {
      padding: 16px;
      font-family: var(--font-mono);
      font-size: 12px;
      color: #E2E8F0;
      line-height: 1.6;
      white-space: pre-wrap;
      max-height: 460px;
      overflow-y: auto;
    }

    /* Buttons */
    .btn-primary {
      padding: 10px 18px;
      background: linear-gradient(135deg, var(--cyan) 0%, #0099FF 100%);
      border: none;
      border-radius: var(--radius-sm);
      color: #070A10;
      font-size: 13px;
      font-weight: 700;
      cursor: pointer;
      display: inline-flex;
      align-items: center;
      gap: 8px;
      transition: all 0.2s;
    }

    .btn-primary:hover:not(:disabled) {
      box-shadow: 0 0 16px var(--cyan-glow);
      transform: translateY(-1px);
    }

    .btn-primary:disabled {
      opacity: 0.6;
      cursor: not-allowed;
    }

    /* Toast */
    .toast {
      position: fixed;
      bottom: 24px;
      right: 24px;
      background: #0D1322;
      border: 1px solid var(--border-accent);
      color: #FFF;
      padding: 12px 18px;
      border-radius: var(--radius-sm);
      font-size: 13px;
      font-weight: 600;
      display: flex;
      align-items: center;
      gap: 10px;
      box-shadow: 0 10px 30px rgba(0, 0, 0, 0.7);
      transform: translateY(100px);
      opacity: 0;
      transition: all 0.25s cubic-bezier(0.16, 1, 0.3, 1);
      z-index: 1000;
    }

    .toast.show {
      transform: translateY(0);
      opacity: 1;
    }

    /* Modal */
    .modal-backdrop {
      position: fixed;
      top: 0;
      left: 0;
      right: 0;
      bottom: 0;
      background: rgba(7, 10, 16, 0.85);
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
      from {
        opacity: 0;
        transform: scale(0.97);
      }

      to {
        opacity: 1;
        transform: scale(1);
      }
    }

    .modal-card {
      background: #0D1322;
      border: 1px solid var(--border-accent);
      border-radius: var(--radius);
      max-width: 800px;
      width: 100%;
      max-height: 85vh;
      display: flex;
      flex-direction: column;
      box-shadow: 0 24px 60px rgba(0, 0, 0, 0.8);
      overflow: hidden;
    }

    .modal-header {
      padding: 16px 20px;
      border-bottom: 1px solid var(--border);
      display: flex;
      align-items: center;
      justify-content: space-between;
    }

    .modal-close-btn {
      background: transparent;
      border: none;
      color: var(--text-muted);
      font-size: 18px;
      cursor: pointer;
      width: 30px;
      height: 30px;
      display: flex;
      align-items: center;
      justify-content: center;
      border-radius: 4px;
      transition: all 0.2s;
    }

    .modal-close-btn:hover {
      color: #FFF;
      background: rgba(255, 255, 255, 0.08);
    }

    .modal-body {
      padding: 20px;
      overflow-y: auto;
      flex: 1;
    }

    .modal-footer {
      padding: 14px 20px;
      border-top: 1px solid var(--border);
      display: flex;
      justify-content: flex-end;
      gap: 10px;
      background: rgba(255, 255, 255, 0.02);
    }

    /* Footer */
    footer {
      border-top: 1px solid var(--border);
      padding: 24px 0;
      text-align: center;
      color: var(--text-dim);
      font-size: 12px;
      background: rgba(7, 10, 16, 0.8);
    }

    footer a {
      color: var(--text-muted);
      text-decoration: none;
      transition: color 0.2s;
    }

    footer a:hover {
      color: var(--cyan);
    }

    /* Responsive */
    @media (max-width: 1080px) {
      .subbar-inner {
        grid-template-columns: repeat(2, 1fr);
      }

      .workbench-grid {
        grid-template-columns: 1fr;
      }
    }

    @media (max-width: 640px) {
      .subbar-inner {
        grid-template-columns: 1fr;
      }

      .triggers-grid {
        grid-template-columns: 1fr;
      }

      .comparison-grid {
        grid-template-columns: 1fr;
      }
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
          <span id="modalIncidentId"
            style="font-family: var(--font-mono); font-size: 13px; color: var(--text-muted);">INC-000000</span>
          <span id="modalTime" style="font-size: 12px; color: var(--text-dim);">12:00:00</span>
        </div>
        <button class="modal-close-btn" onclick="closeIncidentModal()">✕</button>
      </div>

      <div class="modal-body">
        <div style="margin-bottom: 16px;">
          <div
            style="font-size: 11px; font-weight: 700; color: var(--text-muted); text-transform: uppercase; margin-bottom: 6px;">
            Mensagem do Incidente</div>
          <div id="modalMessage"
            style="background: #090E17; border: 1px solid var(--border); border-left: 3px solid var(--cyan); padding: 10px 14px; border-radius: var(--radius-sm); color: #FFF; font-weight: 600; font-size: 13px;">
            Mensagem</div>
        </div>

        <div
          style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 10px; margin-bottom: 16px;">
          <div
            style="background: rgba(13, 19, 33, 0.7); border: 1px solid var(--border); padding: 10px 12px; border-radius: var(--radius-sm);">
            <div style="font-size: 10px; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">Arquivo
              & Linha</div>
            <div id="modalFile"
              style="font-family: var(--font-mono); font-size: 11px; color: #FFF; word-break: break-all; margin-top: 2px;">
              ---</div>
          </div>
          <div
            style="background: rgba(13, 19, 33, 0.7); border: 1px solid var(--border); padding: 10px 12px; border-radius: var(--radius-sm);">
            <div style="font-size: 10px; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">Hash
              Único (Deduplicação)</div>
            <div id="modalHash"
              style="font-family: var(--font-mono); font-size: 11px; color: var(--cyan); word-break: break-all; margin-top: 2px;">
              ---</div>
          </div>
        </div>

        <div style="margin-bottom: 16px;">
          <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 6px;">
            <span style="font-size: 11px; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">Stack
              Trace (Legível)</span>
            <button class="btn-action-sm" style="padding: 2px 8px; font-size: 10px;" onclick="copyModalTrace()">📋
              Copiar</button>
          </div>
          <pre id="modalTrace"
            style="background: #090E17; border: 1px solid var(--border); border-radius: var(--radius-sm); padding: 12px; font-family: var(--font-mono); font-size: 11px; color: #CBD5E1; max-height: 160px; overflow-y: auto; white-space: pre-wrap; line-height: 1.5;"></pre>
        </div>

        <div>
          <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 6px;">
            <span
              style="font-size: 11px; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">Payload
              Sanitizado ($context->requestData)</span>
            <button class="btn-action-sm" style="padding: 2px 8px; font-size: 10px;" onclick="copyModalPayload()">📋
              Copiar JSON</button>
          </div>
          <pre id="modalPayload"
            style="background: #090E17; border: 1px solid var(--border); border-radius: var(--radius-sm); padding: 12px; font-family: var(--font-mono); font-size: 11px; color: #34D399; max-height: 140px; overflow-y: auto; white-space: pre-wrap; line-height: 1.5;"></pre>
        </div>
      </div>

      <div class="modal-footer">
        <button class="btn-action-sm" onclick="closeIncidentModal()">Fechar</button>
        <button class="btn-primary" style="padding: 6px 14px; font-size: 12px;" onclick="loadIncidentIntoPreviews()">
          <span>🔄 Carregar no Visualizador</span>
        </button>
      </div>
    </div>
  </div>

  <!-- Header -->
  <header>
    <div class="container">
      <div class="header-inner">
        <div class="brand">
          <img src="./logo.png" alt="ErrorTracker" class="brand-logo" onerror="this.style.display='none'">
          <div>
            <div class="brand-title">
              Error<span class="highlight">Tracker</span>
              <!-- <span class="tag-sandbox">Dev Sandbox</span> -->
            </div>
          </div>
        </div>

        <div class="terminal-pill">
          <span class="prompt">$</span>
          <code>composer require heliomarpm/error-tracker</code>
          <button class="btn-copy" onclick="copyComposerCommand()">📋 Copiar</button>
        </div>

        <div class="nav-links">
          <a href="https://github.com/heliomarpm/error-tracker-php" target="_blank" rel="noopener" class="nav-btn">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="currentColor">
              <path
                d="M12 0C5.37 0 0 5.37 0 12c0 5.31 3.435 9.795 8.205 11.385.6.105.825-.255.825-.57 0-.285-.015-1.23-.015-2.235-3.015.555-3.795-.735-4.035-1.41-.135-.345-.72-1.41-1.23-1.695-.42-.225-1.02-.78-.015-.795.945-.015 1.62.87 1.845 1.23 1.08 1.815 2.805 1.305 3.495.99.105-.78.42-1.305.765-1.605-2.67-.3-5.46-1.335-5.46-5.925 0-1.305.465-2.385 1.23-3.225-.12-.3-.54-1.53.12-3.18 0 0 1.005-.315 3.3 1.23.96-.27 1.98-.405 3-.405s2.04.135 3 .405c2.295-1.56 3.3-1.23 3.3-1.23.66 1.65.24 2.88.12 3.18.765.84 1.23 1.905 1.23 3.225 0 4.605-2.805 5.625-5.475 5.925.435.375.81 1.095.81 2.22 0 1.605-.015 2.895-.015 3.3 0 .315.225.69.825.57A12.02 12.02 0 0024 12c0-6.63-5.37-12-12-12z" />
            </svg>
            GitHub
          </a>
          <a href="https://packagist.org/packages/heliomarpm/error-tracker" target="_blank" rel="noopener"
            class="nav-btn">
            📦 Packagist
          </a>
        </div>
      </div>
    </div>
  </header>

  <!-- Metrics Subbar -->
  <section class="subbar">
    <div class="container">
      <!-- Hero Section -->

      <section class="hero">
        <div class="hero-badge">
          <span>⚡</span> Arquitetura Moderna PHP 8.2+ & PSR-3 / PSR-16
        </div>
        <h2>
          Biblioteca robusta para monitoramento de falhas críticas com <span class="grad-text">tolerância a desastres e
            zero ruído</span>.
        </h2>
        <p>
          Interceptação inteligente de exceções com deduplicação anti-flood, sanitização automática de dados sensíveis
          (LGPD/PCI-DSS) e despacho desacoplado para múltiplos canais.
        </p>
      </section>

      <div class="subbar-inner">
        <div class="stat-box">
          <div class="stat-icon">🛡️</div>
          <div>
            <div class="stat-val" id="statSimulations">0</div>
            <div class="stat-label">Simulações Disparadas</div>
          </div>
        </div>
        <div class="stat-box">
          <div class="stat-icon">🛑</div>
          <div>
            <div class="stat-val" id="statFloodsSuppressed">0</div>
            <div class="stat-label">Duplicatas Silenciadas</div>
          </div>
        </div>
        <div class="stat-box">
          <div class="stat-icon">🔒</div>
          <div>
            <div class="stat-val" id="statSanitizedFields">100%</div>
            <div class="stat-label">Campos LGPD Mascarados</div>
          </div>
        </div>
        <div class="stat-box">
          <div class="stat-icon">📡</div>
          <div>
            <div class="stat-val">5 Handlers</div>
            <div class="stat-label">GitHub, Webhook, DB, Mail & Log</div>
          </div>
        </div>
      </div>
    </div>
  </section>

  <!-- Main 2-Column Workbench -->
  <main class="container">
    <div class="workbench-grid">

      <!-- Left Column: Controls (Simulador + Anti-Flood) -->
      <aside class="workbench-sidebar">

        <!-- Panel 1: Incident Simulator -->
        <div class="panel">
          <div class="panel-header">
            <div class="panel-title">
              <span>🚨</span>
              <span>Simulador de Incidentes</span>
            </div>
            <span class="panel-badge">Runtime</span>
          </div>
          <p class="panel-desc">
            Dispare falhas capturadas pelo ErrorTracker. Veja como o contexto, o rastreamento e os handlers reagem:
          </p>

          <div class="triggers-grid">
            <button class="btn-trigger notice" onclick="triggerIncident('notice')">
              <div class="top">
                <span>⚡ Notice</span>
                <span class="code-tag">E_USER_NOTICE</span>
              </div>
              <div class="desc">Acesso com cupom de desconto expirado.</div>
            </button>

            <button class="btn-trigger warning" onclick="triggerIncident('warning')">
              <div class="top">
                <span>⚠️ Warning</span>
                <span class="code-tag">E_USER_WARNING</span>
              </div>
              <div class="desc">Endpoint depreciado v1/pedidos detectado.</div>
            </button>

            <button class="btn-trigger error" onclick="triggerIncident('exception')">
              <div class="top">
                <span>💥 Exception</span>
                <span class="code-tag">RuntimeException</span>
              </div>
              <div class="desc">Timeout de comunicação com gateway de cartão.</div>
            </button>

            <a href="500.php?ref=DEMO-CYBER-99" target="_blank" rel="noopener" class="btn-trigger failsafe">
              <div class="top">
                <span>🛡️ Rota 500</span>
                <span class="code-tag">FAILSAFE UI ↗</span>
              </div>
              <div class="desc">Tela amigável isolando detalhes técnicos do usuário.</div>
            </a>
          </div>

          <div id="triggerResultBox" class="trigger-result-box">
            <div class="header-line">
              <span id="triggerSeverity" class="badge-severity INFO">INFO</span>
              <span id="triggerTime" style="color: var(--text-dim);">12:00:00</span>
            </div>
            <div id="triggerMsg" style="color: #FFF; font-weight: 600; margin-bottom: 4px;">Mensagem de erro</div>
            <div style="color: var(--text-dim); font-size: 10px;">Hash: <span id="triggerHash"
                style="color: var(--cyan);">---</span></div>
          </div>
        </div>

        <!-- Panel 2: Anti-Flood Rate Limiting -->
        <div class="panel">
          <div class="panel-header">
            <div class="panel-title">
              <span>🛑</span>
              <span>Anti-Flood Rate Limiting</span>
            </div>
            <span class="panel-badge">PSR-16 Cache</span>
          </div>
          <p class="panel-desc">
            Em rajadas ou ataques, dezenas de erros repetidos inundariam canais externos. O ErrorTracker silencia
            duplicatas na janela de TTL:
          </p>

          <div class="flood-meter">
            <div class="progress-bar-wrap">
              <div id="floodProgress" class="progress-bar-fill"></div>
            </div>
            <div class="flood-stats-row">
              <div>
                <div class="flood-num total" id="floodTotal">0</div>
                <div class="flood-sublabel">Disparados</div>
              </div>
              <div>
                <div class="flood-num sent" id="floodSent">0</div>
                <div class="flood-sublabel">Despachados</div>
              </div>
              <div>
                <div class="flood-num suppressed" id="floodSuppressed">0</div>
                <div class="flood-sublabel">Silenciados</div>
              </div>
            </div>
            <button id="btnFlood" class="btn-flood" onclick="runAntiFloodStressTest()">
              <span>🚀 Disparar Rajada de 50 Erros</span>
            </button>
          </div>

          <p style="font-size: 11px; color: var(--text-dim); line-height: 1.4;">
            💡 <strong>Como funciona:</strong> O hash contextual <code>md5(file + line + message)</code> identifica a
            reincidência no cache PSR-16.
          </p>
        </div>

      </aside>

      <!-- Right Column: Workspace Tabs -->
      <section class="workbench-main">

        <!-- Main Navigation Tabs -->
        <div class="main-tabs-header">
          <div class="main-tabs-list">
            <button class="main-tab-btn active" onclick="switchMainTab('visualizer', this)">
              <span>📬</span>
              <span>Visualizador de Handlers</span>
            </button>
            <button class="main-tab-btn" onclick="switchMainTab('playground', this)">
              <span>🔒</span>
              <span>Playground LGPD</span>
            </button>
            <button class="main-tab-btn" onclick="switchMainTab('history', this)">
              <span>📜</span>
              <span>Histórico de Incidentes</span>
              <span id="historyCountBadge" class="tab-badge">0</span>
            </button>
            <button class="main-tab-btn" onclick="switchMainTab('docs', this)">
              <span>💻</span>
              <span>Como Implementar</span>
            </button>
          </div>
        </div>

        <!-- Tab 1: Visualizador de Notificações (Handlers) -->
        <div id="pane-visualizer" class="workspace-pane active">
          <div class="subtabs-bar">
            <div class="handlers-pills">
              <button class="handler-pill active" onclick="switchHandlerTab('emailHtml', this)">
                <span>✉️ HTML</span>
              </button>
              <button class="handler-pill" onclick="switchHandlerTab('emailText', this)">
                <span>📄 Texto</span>
              </button>
              <button class="handler-pill" onclick="switchHandlerTab('github', this)">
                <span>🐙 GitHub</span>
              </button>
              <button class="handler-pill" onclick="switchHandlerTab('slack', this)">
                <span>💬 Slack</span>
              </button>
              <button class="handler-pill" onclick="switchHandlerTab('discord', this)">
                <span>🤖 Discord</span>
              </button>
              <button class="handler-pill" onclick="switchHandlerTab('filelog', this)">
                <span>📁 Log</span>
              </button>
              <button class="handler-pill" onclick="switchHandlerTab('database', this)">
                <span>🗄️ SQL</span>
              </button>
            </div>

            <div class="actions-group">
              <button class="btn-action-code" onclick="jumpToImplementationFromHandler()">
                <span>&lt;/&gt; Ver Código deste Handler</span>
              </button>
              <button class="btn-action-sm" onclick="copyActivePreview()">
                <span>📋 Copiar</span>
              </button>
            </div>
          </div>

          <!-- Sub-Panes of Handlers -->
          <div id="handler-emailHtml" class="preview-box active">
            <div class="preview-card-wrap">
              <iframe id="emailHtmlFrame" style="width: 100%; height: 480px; border: none; background: #0B0E14;"
                title="Email HTML Preview"></iframe>
            </div>
          </div>

          <div id="handler-emailText" class="preview-box">
            <div class="preview-card-wrap" style="padding: 16px;">
              <pre id="emailTextPre"
                style="font-family: var(--font-mono); font-size: 12px; color: #94A3B8; white-space: pre-wrap; line-height: 1.6;"></pre>
            </div>
          </div>

          <div id="handler-github" class="preview-box">
            <div class="preview-card-wrap" style="padding: 20px; background: #0D1117;">
              <div
                style="font-size: 15px; font-weight: 700; color: #FFF; margin-bottom: 12px; padding-bottom: 8px; border-bottom: 1px solid #30363D;"
                id="githubTitle">
                [ERROR] Incidente Detectado
              </div>
              <pre id="githubMarkdownPre"
                style="font-family: var(--font-mono); font-size: 12px; color: #C9D1D9; white-space: pre-wrap; line-height: 1.6;"></pre>
            </div>
          </div>

          <div id="handler-slack" class="preview-box">
            <div class="preview-card-wrap" style="padding: 18px; background: #1A1D21;">
              <div
                style="background: #222529; border-left: 4px solid #F0AD4E; border-radius: 4px; padding: 14px; margin-bottom: 14px;">
                <div id="slackText" style="font-weight: 700; color: #FFF; margin-bottom: 6px; font-size: 13px;">🚨
                  [ERROR] Incidente Detectado</div>
                <div id="slackBody" style="font-size: 12px; color: #D1D2D3; margin-bottom: 10px;">Falha de comunicação
                </div>
                <div id="slackFields"
                  style="display: grid; grid-template-columns: 1fr 1fr; gap: 8px; font-size: 11px; background: rgba(0,0,0,0.25); padding: 8px; border-radius: 4px;">
                </div>
              </div>
              <div style="font-size: 11px; color: #6E7681; font-family: var(--font-mono);">
                Payload JSON despachado para a API:
                <pre id="slackJsonPre"
                  style="margin-top: 6px; color: #8B949E; white-space: pre-wrap; font-size: 11px;"></pre>
              </div>
            </div>
          </div>

          <div id="handler-discord" class="preview-box">
            <div class="preview-card-wrap" style="padding: 18px; background: #1E1F22;">
              <div
                style="background: #2B2D31; border-left: 4px solid #5865F2; border-radius: 4px; padding: 14px; margin-bottom: 14px;">
                <div id="discordHeader" style="font-weight: 700; color: #FFF; margin-bottom: 6px; font-size: 13px;">🚨
                  **[ERROR] Incidente Detectado**</div>
                <div id="discordTitle" style="font-size: 13px; font-weight: 600; color: #00A8FC; margin-bottom: 6px;">
                  Título do Embed</div>
                <div id="discordDesc"
                  style="font-family: var(--font-mono); font-size: 11px; background: #1E1F22; padding: 8px; border-radius: 4px; margin-bottom: 8px; color: #DBDEE1;">
                </div>
                <div id="discordFields"
                  style="display: grid; grid-template-columns: 1fr 1fr; gap: 8px; font-size: 11px;"></div>
              </div>
              <div style="font-size: 11px; color: #6E7681; font-family: var(--font-mono);">
                Payload JSON:
                <pre id="discordJsonPre"
                  style="margin-top: 6px; color: #8B949E; white-space: pre-wrap; font-size: 11px;"></pre>
              </div>
            </div>
          </div>

          <div id="handler-filelog" class="preview-box">
            <div class="preview-card-wrap" style="padding: 16px;">
              <div
                style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 12px; padding-bottom: 8px; border-bottom: 1px solid var(--border); flex-wrap: wrap; gap: 8px;">
                <div style="display: flex; align-items: center; gap: 8px;">
                  <span>📁</span>
                  <span id="filelogPath"
                    style="font-family: var(--font-mono); font-size: 12px; color: var(--cyan); word-break: break-all;">storage/logs/incident.log</span>
                </div>
                <span
                  style="font-size: 10px; color: #34D399; background: rgba(16, 185, 129, 0.15); border: 1px solid rgba(16, 185, 129, 0.3); padding: 2px 6px; border-radius: 4px; font-weight: 700;">LOCK_EX
                  Ativo</span>
              </div>
              <pre id="filelogPre"
                style="font-family: var(--font-mono); font-size: 12px; color: #CBD5E1; white-space: pre-wrap; line-height: 1.6; max-height: 420px; overflow-y: auto;"></pre>
            </div>
          </div>

          <div id="handler-database" class="preview-box">
            <div class="preview-card-wrap" style="padding: 16px; background: #0D1321;">
              <div
                style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 12px; padding-bottom: 8px; border-bottom: 1px solid var(--border); flex-wrap: wrap; gap: 8px;">
                <div style="display: flex; align-items: center; gap: 8px;">
                  <span>🗄️</span>
                  <span
                    style="font-family: var(--font-heading); font-size: 13px; color: #FFF; font-weight: 700;">Tabela:
                    <code style="color: var(--cyan); font-family: var(--font-mono);">error_tracker</code></span>
                </div>
                <span
                  style="font-size: 10px; color: var(--cyan); background: rgba(0, 242, 254, 0.15); border: 1px solid rgba(0, 242, 254, 0.3); padding: 2px 6px; border-radius: 4px; font-weight: 700;">Upsert
                  com Reincidência</span>
              </div>

              <div style="margin-bottom: 14px;">
                <div
                  style="font-size: 10px; font-weight: 700; color: var(--text-muted); text-transform: uppercase; margin-bottom: 4px;">
                  Instrução SQL Executada:</div>
                <pre id="dbQueryPre"
                  style="font-family: var(--font-mono); font-size: 11px; background: #080C14; border: 1px solid var(--border); border-radius: var(--radius-sm); padding: 10px; color: #38BDF8; white-space: pre-wrap; line-height: 1.4;"></pre>
              </div>

              <div style="margin-bottom: 14px;">
                <div
                  style="font-size: 10px; font-weight: 700; color: var(--text-muted); text-transform: uppercase; margin-bottom: 4px;">
                  Colunas & Valores Persistidos:</div>
                <div id="dbFieldsGrid"
                  style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 8px;"></div>
              </div>

              <div>
                <div
                  style="font-size: 10px; font-weight: 700; color: var(--text-muted); text-transform: uppercase; margin-bottom: 4px;">
                  Coluna payload (JSON Sanitizado):</div>
                <pre id="dbPayloadPre"
                  style="font-family: var(--font-mono); font-size: 11px; background: #080C14; border: 1px solid var(--border); border-radius: var(--radius-sm); padding: 8px 10px; color: #94A3B8; max-height: 120px; overflow-y: auto; white-space: pre-wrap;"></pre>
              </div>
            </div>
          </div>
        </div>

        <!-- Tab 2: Playground de Sanitização LGPD -->
        <div id="pane-playground" class="workspace-pane">
          <p style="font-size: 12px; color: var(--text-muted); margin-bottom: 16px;">
            O <code>DataSanitizer</code> ofusca e-mails, CPFs e cartões, além de eliminar senhas e tokens de
            autenticação em <code>$_POST</code> e <code>$_SERVER</code> antes do despacho.
          </p>

          <form id="sanitizerForm" onsubmit="handleSanitizeSubmit(event)">
            <div
              style="display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 12px; margin-bottom: 14px;">
              <div class="form-group">
                <label>Usuário / Nome</label>
                <input type="text" id="inputUsername" class="form-control" value="carlos_dev">
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

            <button type="submit" id="btnSanitize" class="btn-primary" style="margin-bottom: 16px;">
              <span>🛡️ Sanitizar Requisição & Interceptar Erro</span>
            </button>
          </form>

          <div class="comparison-grid">
            <div class="code-panel">
              <div class="code-panel-header danger">
                <span>⚠️ Payload Bruto Enviado ($_POST)</span>
                <span>Exposto</span>
              </div>
              <div class="code-content" id="rawPayloadView">
                {
                "username": "carlos_dev",
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
                <span>✅ Payload Sanitizado ($context->requestData)</span>
                <span>Protegido</span>
              </div>
              <div class="code-content" id="sanitizedPayloadView">
                {
                "username": "carlos_dev",
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

        <!-- Tab 3: Histórico de Incidentes -->
        <div id="pane-history" class="workspace-pane">
          <div
            style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 14px; flex-wrap: wrap; gap: 10px;">
            <p style="font-size: 12px; color: var(--text-muted);">
              Eventos capturados nesta sessão (armazenamento estritamente local em <code>localStorage</code>, zero-disk
              no servidor).
            </p>
            <button class="btn-action-sm" onclick="clearLocalHistory()">
              <span>🗑️ Limpar Histórico</span>
            </button>
          </div>

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
                    Nenhum incidente registrado ainda nesta sessão. Dispare um erro na barra lateral!
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>

        <!-- Tab 4: Como Implementar (Docs & Code Recipes) -->
        <div id="pane-docs" class="workspace-pane">
          <p style="font-size: 12px; color: var(--text-muted); margin-bottom: 16px;">
            Receitas de código PHP 8.2+ prontas para copiar e colar na sua aplicação:
          </p>

          <div class="docs-nav">
            <button class="docs-tab-btn active" onclick="switchDocTab('bootstrap', this)">
              <span>⚡ Bootstrap Global</span>
            </button>
            <button class="docs-tab-btn" onclick="switchDocTab('github', this)">
              <span>🐙 GitHub Issues</span>
            </button>
            <button class="docs-tab-btn" onclick="switchDocTab('webhook', this)">
              <span>💬 Slack & Discord</span>
            </button>
            <button class="docs-tab-btn" onclick="switchDocTab('database', this)">
              <span>🗄️ MySQL / SQLite</span>
            </button>
            <button class="docs-tab-btn" onclick="switchDocTab('filelog', this)">
              <span>📁 Arquivo & E-mail</span>
            </button>
            <button class="docs-tab-btn" onclick="switchDocTab('psr3', this)">
              <span>🛠️ Logger PSR-3</span>
            </button>
          </div>

          <!-- Snippet 1: Bootstrap -->
          <div id="doc-bootstrap" class="docs-pane active">
            <div class="snippet-box">
              <div class="snippet-header">
                <span>bootstrap.php</span>
                <button class="btn-action-sm" onclick="copySnippet('snippetBootstrap')">📋 Copiar</button>
              </div>
              <pre id="snippetBootstrap" class="snippet-code">&lt;?php

declare(strict_types=1);

use HeliomarPM\ErrorTracker\ErrorTracker;
use HeliomarPM\ErrorTracker\Cache\FileCache;
use HeliomarPM\ErrorTracker\Handlers\FilelogHandler;
use HeliomarPM\ErrorTracker\Handlers\EmailHandler;
use HeliomarPM\ErrorTracker\Severity;

require_once __DIR__ . '/vendor/autoload.php';

// 1. Configura cache PSR-16 com TTL de 3600s para deduplicação anti-flood
$cache = new FileCache(__DIR__ . '/storage/cache/errors');
$tracker = new ErrorTracker($cache, 3600);

// 2. Registra os canais de alerta desejados
$tracker->addHandler(new FilelogHandler(__DIR__ . '/storage/logs'))
        ->addHandler(new EmailHandler('devops@empresa.com', notifySeverities: [Severity::CRITICAL]));

// 3. Rota de Fuga amigável em incidentes críticos (Failsafe)
$tracker->onCritical(function ($context) {
    header("Location: /500.php?ref={$context->hash}");
    exit;
});

// 4. Ativa interceptadores globais de Exceptions, Errors e Fatals
$tracker->register();</pre>
            </div>
          </div>

          <!-- Snippet 2: GitHub -->
          <div id="doc-github" class="docs-pane">
            <div class="snippet-box">
              <div class="snippet-header">
                <span>GitHubIssueHandler.php</span>
                <button class="btn-action-sm" onclick="copySnippet('snippetGithub')">📋 Copiar</button>
              </div>
              <pre id="snippetGithub" class="snippet-code">use HeliomarPM\ErrorTracker\Handlers\GitHubIssueHandler;
use HeliomarPM\ErrorTracker\Severity;

// Abre issues no GitHub automaticamente com deduplicação inteligente de reincidência
$githubHandler = new GitHubIssueHandler(
    token: $_ENV['GITHUB_TOKEN'], // Fine-grained PAT com permissão Issues: Read and write
    repository: 'minha-empresa/meu-sistema',
    application: 'Checkout API (Prod)',
    notifySeverities: [Severity::CRITICAL, Severity::WARNING],
    timeoutSeconds: 3,
    deduplicateOpenIssues: true // Adiciona comentário na issue se já estiver aberta!
);

$tracker->addHandler($githubHandler);</pre>
            </div>
          </div>

          <!-- Snippet 3: Webhook -->
          <div id="doc-webhook" class="docs-pane">
            <div class="snippet-box">
              <div class="snippet-header">
                <span>WebhookHandler.php (Slack & Discord)</span>
                <button class="btn-action-sm" onclick="copySnippet('snippetWebhook')">📋 Copiar</button>
              </div>
              <pre id="snippetWebhook" class="snippet-code">use HeliomarPM\ErrorTracker\Handlers\WebhookHandler;
use HeliomarPM\ErrorTracker\Severity;

// Envio para canal do Slack
$slackHandler = new WebhookHandler(
    webhookUrl: $_ENV['SLACK_WEBHOOK_URL'],
    notifySeverities: [Severity::CRITICAL, Severity::WARNING],
    format: 'slack',
    timeoutSeconds: 3
);

// Envio para canal do Discord
$discordHandler = new WebhookHandler(
    webhookUrl: $_ENV['DISCORD_WEBHOOK_URL'],
    format: 'discord',
    timeoutSeconds: 3
);

$tracker->addHandler($slackHandler)
        ->addHandler($discordHandler);</pre>
            </div>
          </div>

          <!-- Snippet 4: Database -->
          <div id="doc-database" class="docs-pane">
            <div class="snippet-box">
              <div class="snippet-header">
                <span>DatabaseHandler.php (MySQL / MariaDB / SQLite)</span>
                <button class="btn-action-sm" onclick="copySnippet('snippetDatabase')">📋 Copiar</button>
              </div>
              <pre id="snippetDatabase" class="snippet-code">use HeliomarPM\ErrorTracker\Handlers\DatabaseHandler;
use PDO;

// Conexão PDO existente da sua aplicação (MySQL ou SQLite)
$pdo = new PDO("mysql:host=localhost;dbname=meu_banco;charset=utf8mb4", "root", "senha");

// autoCreateTable: true cria a tabela 'error_tracker' com índices e campos automaticamente
$dbHandler = new DatabaseHandler(
    pdo: $pdo,
    tableName: 'error_tracker',
    autoCreateTable: true,
    notifySeverities: null // null registra todos os incidentes
);

$tracker->addHandler($dbHandler);</pre>
            </div>
          </div>

          <!-- Snippet 5: Filelog & Email -->
          <div id="doc-filelog" class="docs-pane">
            <div class="snippet-box">
              <div class="snippet-header">
                <span>FilelogHandler.php & EmailHandler.php</span>
                <button class="btn-action-sm" onclick="copySnippet('snippetFilelog')">📋 Copiar</button>
              </div>
              <pre id="snippetFilelog" class="snippet-code">use HeliomarPM\ErrorTracker\Handlers\FilelogHandler;
use HeliomarPM\ErrorTracker\Handlers\EmailHandler;
use HeliomarPM\ErrorTracker\Severity;

// Log rotacionado diariamente com proteção de escrita exclusiva LOCK_EX
$filelog = new FilelogHandler(
    logDirectory: __DIR__ . '/storage/logs',
    notifySeverities: [Severity::CRITICAL, Severity::WARNING]
);

// Disparo de e-mail formatado em HTML estilizado via mail()
$email = new EmailHandler(
    to: 'suporte@empresa.com',
    from: 'noreply@empresa.com',
    notifySeverities: [Severity::CRITICAL],
    format: 'html' // ou 'text'
);

$tracker->addHandler($filelog)->addHandler($email);</pre>
            </div>
          </div>

          <!-- Snippet 6: PSR-3 -->
          <div id="doc-psr3" class="docs-pane">
            <div class="snippet-box">
              <div class="snippet-header">
                <span>Uso como Logger PSR-3 (Logs do Dia a Dia)</span>
                <button class="btn-action-sm" onclick="copySnippet('snippetPsr3')">📋 Copiar</button>
              </div>
              <pre id="snippetPsr3" class="snippet-code">// 1. Log informativo de negócio (Sem ruído de exceção ou stack trace fake)
$tracker->info("Tentativa de login com senha inválida", [
    'username' => 'carlos.dev',
    'ip'       => '192.168.1.100',
    'tentativa' => 3
]);

// 2. Alerta Operacional
$tracker->warning("Estoque do produto #458 abaixo do limite mínimo.");

// 3. Falha Crítica com Exceção Encadeada
try {
    // rotina crítica...
} catch (Throwable $e) {
    $tracker->error("Falha no checkout de pagamento", [
        'pedido_id' => 1042,
        'exception' => $e // Se passado, o stack trace real da exceção é preservado!
    ]);
}</pre>
            </div>
          </div>
        </div>

      </section>
    </div>
  </main>

  <!-- Footer -->
  <footer>
    <div class="container">
      <p>
        <strong>HeliomarPM ErrorTracker</strong> — Desenvolvido por <a href="https://github.com/heliomarpm"
          target="_blank" rel="noopener">Heliomar P. Marques</a> sob a licença MIT.
      </p>
      <p style="margin-top: 4px; font-size: 11px;">
        Demo otimizada para ambiente Serverless com PHP 8.2+. Zero arquivos de log gerados no disco do servidor.
      </p>
    </div>
  </footer>

  <!-- Client-side Logic -->
  <script>
    const STORAGE_KEY = 'errortracker_demo_incidents';
    let currentIncidentData = null;
    let activeHandlerTabId = 'emailHtml';

    // Inicialização ao carregar página
    document.addEventListener('DOMContentLoaded', () => {
      loadHistoryTable();
      updateHistoryCount();
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

    // Copia comando composer
    function copyComposerCommand() {
      navigator.clipboard.writeText('composer require heliomarpm/error-tracker').then(() => {
        showToast('Comando composer copiado!', '📋');
      });
    }

    // Alterna Abas Principais (Visualizador, Playground, Histórico, Como Implementar)
    function switchMainTab(tabName, btn) {
      document.querySelectorAll('.main-tab-btn').forEach(b => b.classList.remove('active'));
      document.querySelectorAll('.workspace-pane').forEach(p => p.classList.remove('active'));

      if (btn) btn.classList.add('active');
      const pane = document.getElementById(`pane-${tabName}`);
      if (pane) pane.classList.add('active');
    }

    // Alterna Sub-Abas de Handlers no Visualizador
    function switchHandlerTab(handlerId, btn) {
      activeHandlerTabId = handlerId;
      document.querySelectorAll('.handler-pill').forEach(b => b.classList.remove('active'));
      document.querySelectorAll('.preview-box').forEach(p => p.classList.remove('active'));

      if (btn) btn.classList.add('active');
      const box = document.getElementById(`handler-${handlerId}`);
      if (box) box.classList.add('active');
    }

    // Alterna Sub-Abas de Documentação (Como Implementar)
    function switchDocTab(docId, btn) {
      document.querySelectorAll('.docs-tab-btn').forEach(b => b.classList.remove('active'));
      document.querySelectorAll('.docs-pane').forEach(p => p.classList.remove('active'));

      if (btn) btn.classList.add('active');
      const pane = document.getElementById(`doc-${docId}`);
      if (pane) pane.classList.add('active');
    }

    // Pula direto do Visualizador para a documentação do Handler atual
    function jumpToImplementationFromHandler() {
      const mapping = {
        'emailHtml': 'filelog',
        'emailText': 'filelog',
        'github': 'github',
        'slack': 'webhook',
        'discord': 'webhook',
        'filelog': 'filelog',
        'database': 'database'
      };

      const targetDoc = mapping[activeHandlerTabId] || 'bootstrap';

      // 1. Troca para a aba principal de Como Implementar
      const docMainBtn = document.querySelectorAll('.main-tab-btn')[3];
      switchMainTab('docs', docMainBtn);

      // 2. Seleciona a sub-aba de código correta
      const docSubBtn = Array.from(document.querySelectorAll('.docs-tab-btn')).find(b =>
        b.getAttribute('onclick')?.includes(`'${targetDoc}'`)
      );
      if (docSubBtn) {
        switchDocTab(targetDoc, docSubBtn);
      }

      showToast(`Exibindo instruções de código para ${activeHandlerTabId}!`, '💻');
    }

    // Copia snippet de código da documentação
    function copySnippet(elementId) {
      const el = document.getElementById(elementId);
      if (!el) return;
      navigator.clipboard.writeText(el.textContent || '').then(() => {
        showToast('Código copiado para a área de transferência!', '📋');
      });
    }

    // Copia conteúdo do preview do handler ativo
    function copyActivePreview() {
      if (!currentIncidentData) return;
      let content = '';

      if (activeHandlerTabId === 'emailHtml') {
        content = currentIncidentData.previews?.email_html || '';
      } else if (activeHandlerTabId === 'emailText') {
        content = currentIncidentData.previews?.email_text || '';
      } else if (activeHandlerTabId === 'github') {
        content = (currentIncidentData.previews?.github_title || '') + "\n\n" + (currentIncidentData.previews?.github_markdown || '');
      } else if (activeHandlerTabId === 'slack') {
        content = JSON.stringify(currentIncidentData.previews?.slack_json || {}, null, 2);
      } else if (activeHandlerTabId === 'discord') {
        content = JSON.stringify(currentIncidentData.previews?.discord_json || {}, null, 2);
      } else if (activeHandlerTabId === 'filelog') {
        content = currentIncidentData.previews?.filelog?.content || '';
      } else if (activeHandlerTabId === 'database') {
        const db = currentIncidentData.previews?.database || {};
        content = "-- INSTRUÇÃO SQL:\n" + (db.query_mysql || db.query_sqlite || '') + "\n\n-- REGISTRO:\n" + JSON.stringify(db.record || {}, null, 2);
      }

      navigator.clipboard.writeText(content).then(() => {
        showToast('Prévia copiada para a área de transferência!', '📋');
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
          updateHistoryCount();

          // Garante que o usuário veja o resultado direto na aba de Notificações
          const visBtn = document.querySelectorAll('.main-tab-btn')[0];
          switchMainTab('visualizer', visBtn);

          showToast(`Incidente [${data.context.severity}] capturado com sucesso!`, '⚡');
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
            updateHistoryCount();
          }

          if (data.previews) {
            currentIncidentData = data;
            updatePreviews(data);
          }

          btn.disabled = false;
          btn.innerHTML = '<span>🚀 Disparar Rajada de 50 Erros</span>';
          showToast(`Anti-Flood: 50 disparos! 1 enviado, 49 silenciados (${data.efficiency_percent} economia)`, '🛡️');
        }, 350);

      } catch (err) {
        btn.disabled = false;
        btn.innerHTML = '<span>🚀 Disparar Rajada de 50 Erros</span>';
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
        updateHistoryCount();

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
              <div style="color: #8B949E; font-size: 10px;">${f.title}</div>
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
              <div style="color: #949BA4; font-size: 10px;">${f.name}</div>
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
            <div style="background: rgba(14, 21, 36, 0.7); border: 1px solid var(--border); border-radius: 6px; padding: 6px 10px;">
              <div style="color: var(--text-muted); font-size: 10px; text-transform: uppercase;">${item.label}</div>
              <div style="color: #FFF; font-family: var(--font-mono); font-size: 11px; word-break: break-all; margin-top: 2px;">${displayVal}</div>
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
        if (incidents.length > 20) incidents.pop();
        localStorage.setItem(STORAGE_KEY, JSON.stringify(incidents));
        loadHistoryTable();
      } catch (e) {
        console.warn('Erro ao salvar no localStorage:', e);
      }
    }

    function updateHistoryCount() {
      try {
        const incidents = JSON.parse(localStorage.getItem(STORAGE_KEY) || '[]');
        const badge = document.getElementById('historyCountBadge');
        if (badge) badge.textContent = incidents.length;
      } catch {
        // noop
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
                Nenhum incidente registrado ainda nesta sessão. Dispare um erro na barra lateral!
              </td>
            </tr>
          `;
          return;
        }

        tbody.innerHTML = incidents.map((item, index) => `
          <tr>
            <td style="font-family: var(--font-mono); font-size: 11px; color: var(--text-muted);">${item.timestamp}</td>
            <td><span class="badge-severity ${item.severity}">${item.severity}</span></td>
            <td style="font-weight: 600; color: #FFF; font-size: 12px;">${escapeHtml(item.message)}</td>
            <td style="font-family: var(--font-mono); font-size: 11px; color: var(--text-dim);">${escapeHtml(item.shortFile || item.file)}</td>
            <td style="font-family: var(--font-mono); font-size: 11px; color: var(--cyan);">${(item.hash || '---').substring(0, 10)}...</td>
            <td>
              <button class="btn-action-sm" style="padding: 2px 8px; font-size: 11px;" onclick="openIncidentDetails(${index})">🔍 Detalhes</button>
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

    function closeIncidentModal() {
      document.getElementById('incidentModal').classList.remove('show');
    }

    function handleModalBackdropClick(e) {
      if (e.target.id === 'incidentModal') {
        closeIncidentModal();
      }
    }

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
      const visBtn = document.querySelectorAll('.main-tab-btn')[0];
      switchMainTab('visualizer', visBtn);
      showToast('Incidente carregado no visualizador!', '🔄');
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

    document.addEventListener('keydown', (e) => {
      if (e.key === 'Escape') {
        closeIncidentModal();
      }
    });

    function clearLocalHistory() {
      localStorage.removeItem(STORAGE_KEY);
      loadHistoryTable();
      updateHistoryCount();
      showToast('Histórico local limpo com sucesso!', '🗑️');
    }

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
