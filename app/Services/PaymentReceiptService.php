<?php

namespace App\Services;

use App\Models\OtherPayment;
use App\Models\SppPayment;

class PaymentReceiptService
{
    public function number(SppPayment|OtherPayment $payment): string
    {
        return $this->prefix($payment)
            .'-'.$payment->transaction_at->format('Ymd')
            .'-'.str_pad((string) $payment->id, 6, '0', STR_PAD_LEFT);
    }

    public function prefix(SppPayment|OtherPayment $payment): string
    {
        if ($payment instanceof SppPayment) {
            return 'SPP';
        }

        $payment->loadMissing('feeType');

        return match ($payment->feeType?->payment_group) {
            'pendaftaran' => 'PD',
            'daftar-ulang' => 'DU',
            'laundry' => 'LD',
            default => 'LL',
        };
    }
}
