<?php

declare(strict_types=1);

namespace SubitoSMS;

use SubitoSMS\Exception\ApiException;
use SubitoSMS\Exception\TransportException;

final class Client
{
    public const DEFAULT_ENDPOINT = 'https://www.subitosms.it/gateway.php';

    private string $username;
    private string $password;
    private string $endpoint;
    private int $timeout;
    /** @var callable(array<string, string>, string, int): string */
    private $transport;

    /**
     * @param callable(array<string, string>, string, int): string|null $transport
     */
    public function __construct(
        string $username,
        string $password,
        string $endpoint = self::DEFAULT_ENDPOINT,
        int $timeout = 30,
        ?callable $transport = null
    ) {
        if ($username === '' || $password === '') {
            throw new \InvalidArgumentException('Username e password sono obbligatori.');
        }
        if ($timeout < 1) {
            throw new \InvalidArgumentException('Il timeout deve essere maggiore di zero.');
        }

        $this->username = $username;
        $this->password = $password;
        $this->endpoint = $endpoint;
        $this->timeout = $timeout;
        $this->transport = $transport ?? [$this, 'post'];
    }

    /**
     * Invia lo stesso testo a uno o più destinatari e restituisce l'ID spedizione.
     *
     * @param string|array<int, string> $destinations
     */
    public function send(string $sender, $destinations, string $message, bool $test = false, ?int $delayMinutes = null): int
    {
        if ($sender === '' || $message === '') {
            throw new \InvalidArgumentException('Mittente e testo sono obbligatori.');
        }
        if ($delayMinutes !== null && $delayMinutes < 0) {
            throw new \InvalidArgumentException('Il ritardo non può essere negativo.');
        }

        $numbers = is_array($destinations) ? $destinations : explode(',', $destinations);
        $numbers = array_values(array_filter(array_map('trim', $numbers), static function (string $number): bool {
            return $number !== '';
        }));
        if ($numbers === []) {
            throw new \InvalidArgumentException('Indicare almeno un destinatario.');
        }
        foreach ($numbers as $number) {
            if (!preg_match('/^\\+?[0-9]+$/', $number)) {
                throw new \InvalidArgumentException('Destinatario non valido: ' . $number);
            }
        }

        $parameters = ['mitt' => $sender, 'dest' => implode(',', $numbers), 'testo' => $message];
        if ($test) {
            $parameters['test'] = '1';
        }
        if ($delayMinutes !== null) {
            $parameters['delay'] = (string) $delayMinutes;
        }

        $response = $this->request($parameters);
        if (!preg_match('/^id:([0-9]+)\\s*$/i', trim($response), $matches)) {
            throw new ApiException('Risposta inattesa del gateway: ' . trim($response));
        }

        return (int) $matches[1];
    }

    public function balance(bool $includeForeign = false): int
    {
        $response = $this->request($includeForeign ? ['estero' => '1'] : []);
        if (!preg_match('/^credito:([0-9]+)\\s*$/i', trim($response), $matches)) {
            throw new ApiException('Risposta inattesa del gateway: ' . trim($response));
        }

        return (int) $matches[1];
    }

    /** @return array<int, DeliveryStatus> */
    public function status(int $shipmentId): array
    {
        if ($shipmentId < 1) {
            throw new \InvalidArgumentException('L\'ID della spedizione deve essere positivo.');
        }

        $response = trim($this->request(['id' => (string) $shipmentId]));
        $statuses = [];
        foreach (preg_split('/\\R+/', $response) ?: [] as $line) {
            if (!preg_match('/^dest:([^;]+);stato:(-?[0-9]+);desc:(.*);?$/i', trim($line), $matches)) {
                throw new ApiException('Riga di stato non valida: ' . $line);
            }
            $statuses[] = new DeliveryStatus($matches[1], (int) $matches[2], $matches[3]);
        }
        if ($statuses === []) {
            throw new ApiException('Il gateway non ha restituito stati di consegna.');
        }

        return $statuses;
    }

    /** @param array<string, string> $parameters */
    private function request(array $parameters): string
    {
        $parameters = array_merge(['username' => $this->username, 'password' => $this->password], $parameters);
        return ($this->transport)($parameters, $this->endpoint, $this->timeout);
    }

    /** @param array<string, string> $parameters */
    private function post(array $parameters, string $endpoint, int $timeout): string
    {
        $context = stream_context_create(['http' => [
            'method' => 'POST',
            'header' => "Content-Type: application/x-www-form-urlencoded\r\nAccept: text/plain\r\n",
            'content' => http_build_query($parameters, '', '&', PHP_QUERY_RFC3986),
            'timeout' => $timeout,
            'ignore_errors' => true,
        ]]);
        $response = @file_get_contents($endpoint, false, $context);
        if ($response === false) {
            throw new TransportException('Impossibile contattare il gateway SubitoSMS.');
        }

        return $response;
    }
}
