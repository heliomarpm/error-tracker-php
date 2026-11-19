<?php

declare(strict_types=1);

namespace HeliomarPM\ErrorTracker\Tests\Unit;

use HeliomarPM\ErrorTracker\Transports\SmtpTransport;
use PHPUnit\Framework\TestCase;

final class SmtpTransportTest extends TestCase
{
    public function testInitializesWithDefaultValues(): void
    {
        $transport = new SmtpTransport("smtp.example.com");

        $this->assertSame("smtp.example.com", $transport->host);
        $this->assertSame(465, $transport->port);
        $this->assertSame("ssl", $transport->secure);
        $this->assertSame(10, $transport->timeout);
        $this->assertSame("", $transport->username);
        $this->assertSame("", $transport->getLastError());
    }

    public function testInitializesWithCustomSmtpConfig(): void
    {
        $transport = new SmtpTransport(
            "smtp.custom.com",
            587,
            "user@custom.com",
            "secret123",
            "tls",
            15
        );

        $this->assertSame("smtp.custom.com", $transport->host);
        $this->assertSame(587, $transport->port);
        $this->assertSame("user@custom.com", $transport->username);
        $this->assertSame("tls", $transport->secure);
        $this->assertSame(15, $transport->timeout);
    }

    public function testSendReturnsFalseWhenRecipientsEmpty(): void
    {
        $transport = new SmtpTransport("smtp.example.com");
        $result = $transport->send("from@example.com", [], "Subject", "Body");

        $this->assertFalse($result);
        $this->assertStringContainsString("Nenhum destinatário", $transport->getLastError());
    }

    public function testSendFailsGracefullyOnInvalidHost(): void
    {
        // Conexão em porta inexistente com timeout reduzido para não travar o teste
        $transport = new SmtpTransport("127.0.0.1", 99999, "user", "pass", "ssl", 1);
        $result = $transport->send("from@example.com", "to@example.com", "Subject", "Body");

        $this->assertFalse($result);
        $this->assertNotEmpty($transport->getLastError());
    }
}
