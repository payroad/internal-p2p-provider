<?php

declare(strict_types=1);

namespace Payroad\Provider\InternalP2P;

use Payroad\Domain\Attempt\AttemptStatus;
use Payroad\Domain\Attempt\PaymentAttemptId;
use Payroad\Domain\Money\Money;
use Payroad\Domain\Payment\PaymentId;
use Payroad\Domain\Channel\P2P\P2PPaymentAttempt;
use Payroad\Domain\Channel\P2P\P2PRefund;
use Payroad\Domain\Refund\RefundId;
use Payroad\Domain\Refund\RefundStatus;
use Payroad\Port\Provider\P2P\P2PAttemptContext;
use Payroad\Port\Provider\P2P\P2PProviderInterface;
use Payroad\Port\Provider\P2P\P2PRefundContext;
use Payroad\Port\Provider\WebhookResult;
use Payroad\Provider\InternalP2P\Data\InternalP2PAttemptData;
use Payroad\Provider\InternalP2P\Data\InternalP2PRefundData;

/**
 * Mock P2P provider for demo and testing purposes.
 *
 * Simulates a bank transfer flow:
 *   1. initiateP2PAttempt() — generates transfer instructions, moves to AWAITING_CONFIRMATION
 *   2. Confirmation is triggered manually via the demo UI (no real transfer)
 *   3. initiateRefund()     — instant SUCCEEDED (mock reverse transfer)
 */
final class InternalP2PProvider implements P2PProviderInterface
{
    private const RECIPIENT_ACCOUNT = '4111 1111 1111 1111';
    private const RECIPIENT_BANK    = 'Demo National Bank';
    private const RECIPIENT_HOLDER  = 'Payroad Demo Merchant';

    public function supports(string $providerName): bool
    {
        return $providerName === 'internal_p2p';
    }

    public function initiateP2PAttempt(
        PaymentAttemptId  $id,
        PaymentId         $paymentId,
        string            $providerName,
        Money             $amount,
        P2PAttemptContext $context,
    ): P2PPaymentAttempt {
        $reference = 'P2P-' . strtoupper(substr($id->value, 0, 8));

        $data = new InternalP2PAttemptData(
            transferReference: $reference,
            recipientAccount:  self::RECIPIENT_ACCOUNT,
            recipientBank:     self::RECIPIENT_BANK,
            recipientHolder:   self::RECIPIENT_HOLDER,
        );

        $attempt = P2PPaymentAttempt::create($id, $paymentId, $providerName, $amount, $data);
        $attempt->setProviderReference('p2p_' . $id->value);
        $attempt->markAwaitingConfirmation('transfer_instructions_issued');

        return $attempt;
    }

    public function initiateRefund(
        RefundId         $id,
        PaymentId        $paymentId,
        PaymentAttemptId $originalAttemptId,
        string           $providerName,
        Money            $amount,
        string           $originalProviderReference,
        P2PRefundContext $context,
    ): P2PRefund {
        $returnRef = 'REF-' . strtoupper(substr($id->value, 0, 8));
        $data      = new InternalP2PRefundData($returnRef, $context->reason);

        $refund = P2PRefund::create($id, $paymentId, $originalAttemptId, $providerName, $amount, $data);
        $refund->setProviderReference($returnRef);
        $refund->markSucceeded('mock_refund_completed');

        return $refund;
    }

    public function parseIncomingWebhook(array $payload, array $headers): ?WebhookResult
    {
        // No real webhooks — confirmation is triggered manually
        return null;
    }
}
