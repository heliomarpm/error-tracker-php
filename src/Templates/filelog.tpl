================================================================================
<!-- IF_EXCEPTION -->
[{{timestamp}}] [{{severity}}] {{error_title}}: {{message}}
<!-- /IF_EXCEPTION -->
<!-- IF_MESSAGE_ONLY -->
[{{timestamp}}] [{{severity}}] {{message}}
<!-- /IF_MESSAGE_ONLY -->
Origem:      {{error_file}}:{{error_line}}
<!-- IF_PREVIOUS -->
Causa Raiz:  {{previous_class}}: {{previous_message}}
<!-- /IF_PREVIOUS -->
Aplicação:   {{app_name}} ({{environment}}) | Servidor: {{server_name}}
Rota/Método: {{request_method}} {{request_uri}}
Usuário:     {{usuario_nome}} | Memória: {{memory_usage}} | Hash: {{hash}}
<!-- IF_TRACE -->

Stack Trace:
{{trace}}
<!-- /IF_TRACE -->

Payload:
{{payload}}
--------------------------------------------------------------------------------
