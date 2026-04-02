<?php

declare(strict_types=1);

namespace Payroad\Provider\InternalP2P\Data;

use Payroad\Domain\Channel\P2P\P2PAttemptData;

final class InternalP2PAttemptData implements P2PAttemptData
{
    public function __construct(
        private readonly string $transferReference,
        private readonly string $recipientAccount,
        private readonly string $recipientBank,
        private readonly string $recipientHolder,
    ) {}

    /** Unique reference the sender must include in the transfer. */
    public function getTransferReference(): string
    {
        return $this->transferReference;
    }

    public function getTransferTarget(): string
    {
        return $this->recipientAccount;
    }

    public function getRecipientBankName(): string
    {
        return $this->recipientBank;
    }

    public function getRecipientHolderName(): string
    {
        return $this->recipientHolder;
    }

    public function toArray(): array
    {
        return [
            'transferReference' => $this->transferReference,
            'recipientAccount'  => $this->recipientAccount,
            'recipientBank'     => $this->recipientBank,
            'recipientHolder'   => $this->recipientHolder,
        ];
    }

    public static function fromArray(array $data): static
    {
        return new self(
            transferReference: $data['transferReference'] ?? '',
            recipientAccount:  $data['recipientAccount']  ?? '',
            recipientBank:     $data['recipientBank']     ?? '',
            recipientHolder:   $data['recipientHolder']   ?? '',
        );
    }
}
