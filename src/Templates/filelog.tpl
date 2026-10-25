================================================================================
[{{timestamp}}] [{{severity}}] {{error_title}}: {{message}}
Origem:      {{error_file}}:{{error_line}}
<!-- IF_PREVIOUS -->
Causa Raiz:  {{previous_class}}: {{previous_message}}
<!-- /IF_PREVIOUS -->
Rota/Método: {{request_method}} {{request_uri}}
Usuário:     {{usuario_nome}} | Memória: {{memory_usage}} | Hash: {{hash}}

Stack Trace:
{{trace}}

Payload:
{{payload}}
--------------------------------------------------------------------------------
