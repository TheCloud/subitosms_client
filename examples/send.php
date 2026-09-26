<?php

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

use SubitoSMS\Client;

$client = new Client('la-tua-username', 'la-tua-password');

// true: simula l'invio, senza inviare SMS reali né scalare il credito.
$shipmentId = $client->send('MIOBRAND', ['+393351234567', '+393331234567'], 'Ciao da SubitoSMS!', true);

echo "Spedizione creata: {$shipmentId}\n";
