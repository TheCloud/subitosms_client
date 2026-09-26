<?php

declare(strict_types=1);

namespace SubitoSMS\Tests;

use PHPUnit\Framework\TestCase;
use SubitoSMS\Client;
use SubitoSMS\Exception\ApiException;

final class ClientTest extends TestCase
{
    public function testSendBuildsTheExpectedParameters(): void
    {
        $seen = [];
        $client = new Client('user', 'secret', Client::DEFAULT_ENDPOINT, 30, static function (array $parameters) use (&$seen): string {
            $seen = $parameters;
            return 'id:12345';
        });

        self::assertSame(12345, $client->send('MIOBRAND', ['+393351234567', '3331234567'], 'Ciao', true, 10));
        self::assertSame([
            'username' => 'user', 'password' => 'secret', 'mitt' => 'MIOBRAND',
            'dest' => '+393351234567,3331234567', 'testo' => 'Ciao', 'test' => '1', 'delay' => '10',
        ], $seen);
    }

    public function testParsesDeliveryStatusesAndTerminalStates(): void
    {
        $client = new Client('user', 'secret', Client::DEFAULT_ENDPOINT, 30, static function (): string {
            return "dest:+393351234567;stato:1;desc:Ricevuto dal destinatario;\n"
                . 'dest:+393331234567;stato:8;desc:Spedito;';
        });

        $statuses = $client->status(123);
        self::assertCount(2, $statuses);
        self::assertSame('+393351234567', $statuses[0]->getDestination());
        self::assertTrue($statuses[0]->isTerminal());
        self::assertFalse($statuses[1]->isTerminal());
    }

    public function testRejectsGatewayErrors(): void
    {
        $client = new Client('user', 'secret', Client::DEFAULT_ENDPOINT, 30, static function (): string {
            return 'credito insufficiente';
        });

        $this->expectException(ApiException::class);
        $client->send('MIOBRAND', '+393351234567', 'Ciao');
    }
}
