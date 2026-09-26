<?php

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

use SubitoSMS\Client;

$client = new Client('la-tua-username', 'la-tua-password');

echo 'Credito: ' . $client->balance() . PHP_EOL;

foreach ($client->status(12345678) as $status) {
    echo $status->getDestination() . ': ' . $status->getStatus() . ' - ' . $status->getDescription() . PHP_EOL;
}
