=================================ERROR TRACKER=================================
🚨 [{{severity}}] ALERTA DE INCIDENTE
===============================================================================

Tipo:         {{error_title}}
Mensagem:     {{error_message}}
<!-- IF_ORIGIN -->
Origem:       {{error_file}}:{{error_line}}
<!-- /IF_ORIGIN -->
<!-- IF_PREVIOUS -->
Causa Raiz:   {{previous_class}}: {{previous_message}}
<!-- /IF_PREVIOUS -->
Rota/Método:  {{request_method}} {{request_uri}}
Usuário:      {{usuario_nome}}
Data/Hora:    {{timestamp}}
PHP / Serv.:  PHP {{php_version}} em {{server_name}}
Memória:      {{memory_usage}}

-------------------------------------------------------------------------------
🔍 RASTREAMENTO DA PILHA (STACK TRACE)
-------------------------------------------------------------------------------
{{stack_trace}}

--------------------------------------------------------------------------------
📦 PAYLOAD E AMBIENTE (SANITIZADO)
--------------------------------------------------------------------------------
{{payload}}

================================================================================
Este é um e-mail automático gerado pelo sistema de monitoramento do {{server_name}}.
