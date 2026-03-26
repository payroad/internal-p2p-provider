# payroad/internal-p2p-provider

Internal (mock) P2P bank transfer provider for the [Payroad](https://github.com/payroad/payroad-core) platform.

Simulates a bank transfer flow with no external API. Designed for demo and development.

## Features

- Generates transfer reference (`P2P-XXXXXXXX`) and recipient bank details
- Moves attempt to `AWAITING_CONFIRMATION` immediately
- Confirmation triggered manually via admin UI
- Instant refund (mock reverse transfer)
- No webhooks, no external API

## Requirements

- PHP 8.2+
- `payroad/payroad-core`

## Installation

```bash
composer require payroad/internal-p2p-provider
```

## Configuration

```yaml
# config/packages/payroad.yaml
payroad:
  providers:
    internal_p2p:
      factory: Payroad\Provider\InternalP2P\InternalP2PProviderFactory
```

No API keys needed.

## Payment flow

```
Customer                    Merchant UI                 Backend
────────────────────────────────────────────────────────────────
POST /api/payments/p2p
  ← { transferReference, recipientAccount, recipientBank }
Customer "sends" transfer
  (simulated — no real transfer)
                        POST /api/payments/{id}/confirm-p2p
                          { attemptId }
                                                  → Payment SUCCEEDED
```

---

## Using this as a reference for a real provider

This package simulates a bank transfer with no external API.
Real P2P providers (Wise, Adyen Bank Transfer, WeChat Pay, KuaiPay) follow the same structure.

### File structure to replicate

```
src/
├── YourP2PProviderFactory.php   — reads config, constructs provider
├── YourP2PProvider.php          — implements P2PProviderInterface
└── Data/
    ├── YourP2PAttemptData.php   — implements P2PAttemptData
    └── YourP2PRefundData.php    — implements P2PRefundData
```

### What to implement in each file

**`YourP2PProviderFactory`** — implement `ProviderFactoryInterface::create(array $config)`.
Read API credentials from `$config`, pass to the provider constructor.

**`YourP2PProvider::initiateP2PAttempt()`** — call your API to create a transfer order,
wrap recipient details in `YourP2PAttemptData`, then:
```php
$attempt = P2PPaymentAttempt::create($id, $paymentId, $providerName, $amount, $data);
$attempt->setProviderReference($apiResponse->orderId);
$attempt->applyTransition(AttemptStatus::AWAITING_CONFIRMATION, 'transfer_instructions_issued');
return $attempt;
```

**`YourP2PProvider::parseIncomingWebhook()`** — map provider transfer statuses to domain statuses.
P2P state machine: `AWAITING_CONFIRMATION → PROCESSING → SUCCEEDED`. Some providers skip
`PROCESSING` (instant confirmation); return `SUCCEEDED` directly in that case.
```php
return new WebhookResult(
    providerReference: $payload['order_id'],
    newStatus:         AttemptStatus::PROCESSING,
    providerStatus:    $payload['status'],
    statusChanged:     true,
);
```

**`YourP2PAttemptData`** — implement `P2PAttemptData`. Required methods:
- `getTransferReference()` — unique reference the customer must include in the transfer comment
- `getTransferTarget()` — account number / IBAN / phone to send funds to
- `getRecipientBankName()` — human-readable bank name
- `getRecipientHolderName()` — account holder name

Plus `toArray()` and `static fromArray(array): static` for persistence.

**`YourP2PRefundData`** — implement `P2PRefundData`. Store the reverse transfer reference
and reason. Same serialization requirement.

### Registration in `payroad.yaml`

```yaml
payroad:
    providers:
        your_p2p:
            factory: Vendor\YourP2P\YourP2PProviderFactory
            config:
                api_key:  '%env(YOUR_P2P_API_KEY)%'
                base_url: '%env(YOUR_P2P_BASE_URL)%'
```

### Checklist

- [ ] `supports()` matches the provider name from `payroad.yaml`
- [ ] `initiateP2PAttempt()` sets a `providerReference` (used by webhook routing)
- [ ] `AttemptData::toArray()` / `fromArray()` round-trip without data loss
- [ ] `parseIncomingWebhook()` covers all provider statuses — `null` for unknown ones
- [ ] State machine path matches provider behavior: if provider skips `PROCESSING`,
      return `SUCCEEDED` directly (allowed by `P2PStateMachine`)
- [ ] Refund: sync → apply `RefundStatus::SUCCEEDED` immediately; async → leave at `PENDING`
