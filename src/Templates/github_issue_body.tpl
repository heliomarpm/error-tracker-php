<!-- IF_EXCEPTION -->
### 🚨 Falha Capturada Automaticamente
<!-- /IF_EXCEPTION -->
<!-- IF_MESSAGE_ONLY -->
### ℹ️ Evento Informativo Capturado
<!-- /IF_MESSAGE_ONLY -->

**Aplicação:** `{{app_name}}` | **Ambiente:** `{{environment}}`
**Mensagem:** `{{message}}`
**Arquivo:** `{{file}}` (Linha: **{{line}}**)
<!-- IF_USER -->
**Usuário:** `{{user}}`
<!-- /IF_USER -->

<!-- IF_TRACE -->
#### 🔍 Stack Trace
<details><summary>Clique para expandir</summary>

```php
{{trace}}
```
</details>

<!-- /IF_TRACE -->
#### 📦 Payload e Ambiente (Sanitizado)
<details><summary>Clique para expandir</summary>

```json
{{payload}}
```
</details>

---
*ID do Erro: `{{hash}}` | Reportado via ErrorTracker*
