<?php

namespace App\Http\Controllers\API\V1\Payment;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use Domain\Payment\Actions\{
    SetPaymentAsConfirmedAction,
    SetPaymentAsFailedAction
};
use Domain\Payment\DataTransferObjects\WebhookData;
use Domain\Payment\Models\Payment;
use Domain\Payment\ValueObjects\TransactionId;

class WebhookController extends Controller
{
    /**
     * Handle bank confirmation webhook
     * 
     * In production, this would be called by the bank
     * when payment status changes
     */
    public function __invoke(Request $request)
    {        
        try {
            // Making webhook data.
            $transactionId = TransactionId::fromString($request->get('transaction_id'));
        }
        catch(\InvalidArgumentException $e)
        {
            return $this->failedResponse(message: $e->getMessage(), status: 422);
        }
        
        $webhookData = WebhookData::validateAndCreate([
            ...$request->all(),
            'transaction_id' => $transactionId
        ]);

        // Find payment by transaction_id
        $payment = Payment::getByTransactionId($transactionId);

        if (!$payment) {
            logger()->warning(
                'Webhook: Payment not found', 
                ['transaction_id' => $webhookData->transaction_id->value]
            );
            //TODO: Here maybe an api would be called to inform bank the confirmation failed.
            return response()->json(['error' => 'Payment not found'], 404);
        }

        // Only process if payment is still pending
        if (!$payment->isPending()) {
            logger()->info('Webhook: Payment already processed', [
                'transaction_id' => $payment->transaction_id,
                'current_status' => $payment->status->value
            ]);
            
            // This is also for making the bank aware this is a duplicate notification.
            return response()->json([
                'message' => 'Payment already processed',
                'current_status' => $payment->status->value
            ], 200);
        }

        // Handle different webhook statuses
        match($webhookData->status) {
            'success' => SetPaymentAsConfirmedAction::execute($webhookData, $payment),
            'failed' => SetPaymentAsFailedAction::execute($webhookData, $payment),
            default => $this->handlePendingWebhook($webhookData, $payment),
        };

        //TODO: this line will be changed to 
        // bank api documentation required response format.
        return $this->successResponse(status: 200);
    }

    /**
     * Handle pending webhook (payment still processing)
     */
    private function handlePendingWebhook(WebhookData $data, Payment $payment)
    {
        // Update metadata but keep status as pending
        logger()->info('WEBHOOK_PENDING', [
                'transaction_id' => $data->transaction_id,
                'webhook_pending_at' => now()->toIso8601String(),
                'bank_reference' => $data['bank_reference'] ?? null,
        ]);
    }
}