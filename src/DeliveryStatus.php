<?php

declare(strict_types=1);

namespace SubitoSMS;

final class DeliveryStatus
{
    private string $destination;
    private int $status;
    private string $description;

    public function __construct(string $destination, int $status, string $description)
    {
        $this->destination = $destination;
        $this->status = $status;
        $this->description = $description;
    }

    public function getDestination(): string
    {
        return $this->destination;
    }

    public function getStatus(): int
    {
        return $this->status;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function isTerminal(): bool
    {
        return in_array($this->status, [-100, -50, 1, 16], true);
    }
}
