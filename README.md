# SubitoSMS PHP

Client PHP leggero e senza dipendenze per il [gateway HTTP di SubitoSMS](https://www.subitosms.it/gateway_sms_http.php).

Supporta invio singolo o multiplo, invio di prova, invio ritardato, verifica del credito e lettura dello stato di consegna.

## Requisiti

- PHP 7.4 o successivo
- estensione OpenSSL abilitata (per le chiamate HTTPS predefinite)
- account SubitoSMS

## Installazione

Quando il pacchetto sarà pubblicato su Packagist:

```bash
composer require subitosms/subitosms-php
```

Durante lo sviluppo locale, dalla cartella del progetto:

```bash
composer install
```

## Invio di un SMS

```php
<?php

require 'vendor/autoload.php';

use SubitoSMS\Client;

$sms = new Client('la-tua-username', 'la-tua-password');

$shipmentId = $sms->send(
    'MIOBRAND',
    '+393351234567',
    'Ciao da SubitoSMS!'
);

echo "Spedizione: {$shipmentId}";
```

Il valore restituito è l'ID della spedizione assegnato dal gateway.

### Più destinatari

Passa un array (oppure una stringa con numeri separati da virgola). Ogni numero può iniziare con `+`; non inserire spazi o altri caratteri.

```php
$shipmentId = $sms->send(
    'MIOBRAND',
    ['+393351234567', '+393331234567'],
    'Promemoria appuntamento domani alle 10:00.'
);
```

### Invio di prova e ritardato

Per lo sviluppo usa `true` come quarto parametro: il gateway simula l'invio e non scala il credito. Il quinto parametro ritarda l'invio di un numero di minuti.

```php
$shipmentId = $sms->send(
    'MIOBRAND',
    '+393351234567',
    'Messaggio di prova',
    true,
    15
);
```

## Credito residuo

```php
$credit = $sms->balance();
$creditWithForeign = $sms->balance(true);
```

## Stato della spedizione

```php
foreach ($sms->status($shipmentId) as $delivery) {
    printf(
        "%s — stato %d: %s%s",
        $delivery->getDestination(),
        $delivery->getStatus(),
        $delivery->getDescription(),
        PHP_EOL
    );

    if ($delivery->isTerminal()) {
        // Stati terminali: -100, -50, 1, 16.
    }
}
```

Puoi anche configurare nell'area clienti una URL di callback: SubitoSMS le invia in POST `id`, `dest` e `stato` a ogni aggiornamento. La callback dovrebbe rispondere rapidamente; il gateway attende fino a 120 secondi.

## Gestione degli errori

Il client segnala con `SubitoSMS\Exception\ApiException` una risposta del gateway non riconosciuta (ad esempio `credito insufficiente`), e con `SubitoSMS\Exception\TransportException` un problema nel raggiungere il gateway.

```php
use SubitoSMS\Exception\ApiException;
use SubitoSMS\Exception\TransportException;

try {
    $shipmentId = $sms->send('MIOBRAND', '+393351234567', 'Ciao');
} catch (TransportException $e) {
    // Problema di rete/HTTPS: l'invio potrebbe non essere arrivato al gateway.
} catch (ApiException $e) {
    // Risposta del gateway non valida o non accettata.
}
```

Non ritentare automaticamente un invio quando non sai se il gateway lo abbia ricevuto: potresti inviare un SMS duplicato. Conserva l'ID di ogni spedizione riuscita e usa `status()` o la callback per seguirne la consegna.

## Esempi eseguibili

- [`examples/send.php`](examples/send.php): invio simulato a più destinatari.
- [`examples/status.php`](examples/status.php): credito e stato di una spedizione.

Sostituisci le credenziali e gli esempi di numero/ID prima di eseguirli.

## Pubblicazione su Packagist

1. Crea un repository Git pubblico, ad esempio `subitosms/subitosms-php`.
2. Aggiorna in `composer.json` le sezioni `authors`, `homepage` e `support` se necessario.
3. Pubblica una release Git taggata, per esempio `v1.0.0`.
4. Su [Packagist](https://packagist.org/packages/submit), collega il repository e abilita l'aggiornamento automatico via GitHub/GitLab webhook.
5. Verifica l'installazione in un progetto vuoto con `composer require subitosms/subitosms-php`.

## Licenza

MIT. Vedi [LICENSE](LICENSE).
