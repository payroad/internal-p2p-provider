<?php

declare(strict_types=1);

namespace Payroad\Provider\InternalP2P\Data;

use Payroad\Port\Provider\P2P\P2PRefundData;

final readonly class InternalP2PRefundData implements P2PRefundData
{
    public function __construct(
        private ?string $returnTransferReference = null,
        private ?string $reason = null,
    ) {}

    public function getReturnTransferReference(): ?string
    {
        return $this->returnTransferReference;
    }

    public function getReason(): ?string
    {
        return $this->reason;
    }

    public function toArray(): array
    {
        return [
            'returnTransferReference' => $this->returnTransferReference,
            'reason'                  => $this->reason,
        ];
    }

    public static function fromArray(array $data): static
    {
        return new self(
            returnTransferReference: $data['returnTransferReference'] ?? null,
            reason:                  $data['reason']                  ?? null,
        );
    }
}
