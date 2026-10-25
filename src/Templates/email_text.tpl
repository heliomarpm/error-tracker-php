=================================ERROR TRACKER=================================
<!-- IF_EXCEPTION -->
🚨 [{{severity}}] ALERTA DE INCIDENTE
<!-- /IF_EXCEPTION -->
<!-- IF_MESSAGE_ONLY -->
ℹ️ [{{severity}}] REGISTRO DE EVENTO
<!-- /IF_MESSAGE_ONLY -->
===============================================================================

<!-- IF_EXCEPTION -->
Tipo:         {{error_title}}
<!-- /IF_EXCEPTION -->
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
<!-- IF_TRACE -->

-------------------------------------------------------------------------------
🔍 RASTREAMENTO DA PILHA (STACK TRACE)
-------------------------------------------------------------------------------
{{stack_trace}}
<!-- /IF_TRACE -->

--------------------------------------------------------------------------------
📦 PAYLOAD E AMBIENTE (SANITIZADO)
--------------------------------------------------------------------------------
{{payload}}

================================================================================
Este é um e-mail automático gerado pelo sistema de monitoramento do {{server_name}}.
