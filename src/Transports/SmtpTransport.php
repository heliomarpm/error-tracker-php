<?php

declare(strict_types=1);

namespace HeliomarPM\ErrorTracker\Transports;

use RuntimeException;
use Throwable;

/**
 * Cliente de transporte SMTP nativo leve e seguro (sem dependências externas).
 * Suporta TLS (STARTTLS na porta 587) e SSL (porta 465) com autenticação AUTH LOGIN.
 */
final class SmtpTransport
{
    private string $lastError = "";

    public function __construct(
        public readonly string $host,
        public readonly int $port = 465,
        public readonly string $username = "",
        private readonly string $password = "",
        public readonly string $secure = "ssl", // "ssl", "tls" ou "none"
        public readonly int $timeout = 10
    ) {}

    public function getLastError(): string
    {
        return $this->lastError;
    }

    /**
     * Envia mensagem via conexão SMTP autenticada
     *
     * @param string $from E-mail do remetente
     * @param array<string>|string $to Destinatário(s)
     * @param string $subject Assunto da mensagem
     * @param string $body Corpo completo (com headers MIME ou texto puro)
     * @param array<string> $extraHeaders Headers adicionais opcionais
     */
    public function send(string $from, array|string $to, string $subject, string $body, array $extraHeaders = []): bool
    {
        $recipients = is_array($to) ? $to : array_filter(array_map(trim(...), explode(",", str_replace(";", ",", $to))));
        if (empty($recipients)) {
            $this->lastError = "Nenhum destinatário informado.";
            return false;
        }

        $isSsl = strtolower($this->secure) === "ssl" || $this->port === 465;
        $isTls = strtolower($this->secure) === "tls" || $this->port === 587;

        $hostPrefix = $isSsl ? "ssl://" : "tcp://";
        $remoteSocket = $hostPrefix . $this->host . ":" . $this->port;

        $context = stream_context_create([
            "ssl" => [
                "verify_peer" => true,
                "verify_peer_name" => true,
                "allow_self_signed" => false,
            ],
        ]);

        $errno = 0;
        $errstr = "";
        $socket = @stream_socket_client($remoteSocket, $errno, $errstr, (float)$this->timeout, STREAM_CLIENT_CONNECT, $context);

        if (!$socket) {
            $this->lastError = sprintf("Falha ao abrir conexão com %s (%d: %s)", $remoteSocket, $errno, $errstr);
            return false;
        }

        stream_set_timeout($socket, $this->timeout);

        try {
            $response = $this->readResponse($socket);
            if (!str_starts_with($response, "220")) {
                throw new RuntimeException("Banner inicial inesperado do servidor SMTP: " . $response);
            }

            $clientHost = $_SERVER["SERVER_NAME"] ?? (gethostname() ?: "localhost");
            $this->sendCommand($socket, "EHLO " . $clientHost, "250");

            if ($isTls) {
                $this->sendCommand($socket, "STARTTLS", "220");
                $cryptoMethod = STREAM_CRYPTO_METHOD_TLS_CLIENT | STREAM_CRYPTO_METHOD_TLSv1_2_CLIENT;
                if (defined("STREAM_CRYPTO_METHOD_TLSv1_3_CLIENT")) {
                    $cryptoMethod |= STREAM_CRYPTO_METHOD_TLSv1_3_CLIENT;
                }
                $cryptoOk = @stream_socket_enable_crypto($socket, true, $cryptoMethod);
                if (!$cryptoOk) {
                    throw new RuntimeException("Falha na negociação STARTTLS com o servidor SMTP.");
                }
                $this->sendCommand($socket, "EHLO " . $clientHost, "250");
            }

            if (!empty($this->username) && !empty($this->password)) {
                $this->sendCommand($socket, "AUTH LOGIN", "334");
                $this->sendCommand($socket, base64_encode($this->username), "334");
                $this->sendCommand($socket, base64_encode($this->password), "235");
            }

            $this->sendCommand($socket, "MAIL FROM:<" . $from . ">", "250");

            foreach ($recipients as $recipient) {
                $this->sendCommand($socket, "RCPT TO:<" . $recipient . ">", "250");
            }

            $this->sendCommand($socket, "DATA", "354");

            // Monta cabeçalhos da mensagem
            $allHeaders = array_merge([
                "Date: " . date("r"),
                "From: <" . $from . ">",
                "To: " . implode(", ", array_map(fn($r) => "<{$r}>", $recipients)),
                "Subject: " . $subject,
            ], $extraHeaders);

            $messagePayload = implode("\r\n", $allHeaders) . "\r\n\r\n" . $body;

            // Escapa pontos solitários no início de linha conforme RFC 5321 (dot-stuffing)
            $messagePayload = preg_replace("/^\./m", "..", $messagePayload);

            fwrite($socket, $messagePayload . "\r\n.\r\n");

            $dataResponse = $this->readResponse($socket);
            if (!str_starts_with($dataResponse, "250")) {
                throw new RuntimeException("Falha na entrega de dados (DATA): " . $dataResponse);
            }

            $this->sendCommand($socket, "QUIT", "221");
            fclose($socket);

            return true;
        } catch (Throwable $e) {
            $this->lastError = $e->getMessage();
            @fclose($socket);
            return false;
        }
    }

    /**
     * @param resource $socket
     */
    private function sendCommand($socket, string $command, string $expectedCode): string
    {
        fwrite($socket, $command . "\r\n");
        $response = $this->readResponse($socket);

        if (!str_starts_with($response, $expectedCode)) {
            throw new RuntimeException(sprintf("Comando '%s' falhou com resposta: %s", explode(" ", $command)[0], trim($response)));
        }

        return $response;
    }

    /**
     * @param resource $socket
     */
    private function readResponse($socket): string
    {
        $response = "";
        while (!feof($socket)) {
            $line = fgets($socket, 512);
            if ($line === false) {
                break;
            }
            $response .= $line;
            // Linhas intermediárias de resposta multiline terminam com '-' após o código (ex: "250-SIZE")
            // A linha final tem espaço após o código de 3 dígitos (ex: "250 OK")
            if (preg_match('/^[0-9]{3} /', $line)) {
                break;
            }
        }

        return $response;
    }
}
