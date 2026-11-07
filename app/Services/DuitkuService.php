<?php

namespace App\Services;

use App\Exceptions\DuitkuException;
use App\Models\OnlinePaymentTransaction;
use App\Models\Student;
use App\Models\User;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class DuitkuService
{
    public function paymentMethod(int $amount, string $paymentMethod): array
    {
        $this->ensureConfigured();
        $datetime = now()->format('Y-m-d H:i:s');
        $merchantCode = $this->merchantCode();
        $response = $this->post('/webapi/api/merchant/paymentmethod/getpaymentmethod', [
            'merchantcode' => $merchantCode,
            'amount' => $amount,
            'datetime' => $datetime,
            'signature' => hash_hmac('sha256', $merchantCode.$amount.$datetime, $this->apiKey()),
        ]);

        if ((string) $response->json('responseCode') !== '00') {
            throw new DuitkuException('Duitku menolak permintaan daftar kanal pembayaran.');
        }

        $method = collect($response->json('paymentFee', []))
            ->first(fn ($item) => is_array($item) && ($item['paymentMethod'] ?? null) === $paymentMethod);

        if (! is_array($method)) {
            throw new DuitkuException('Kanal pembayaran tidak tersedia untuk merchant ini.');
        }

        $fee = filter_var($method['totalFee'] ?? null, FILTER_VALIDATE_INT);
        if ($fee === false || $fee < 0) {
            throw new DuitkuException('Biaya layanan dari Duitku tidak valid.');
        }

        return [
            'code' => $paymentMethod,
            'name' => Str::limit((string) ($method['paymentName'] ?? $paymentMethod), 255, ''),
            'fee' => $fee,
        ];
    }

    public function createTransaction(
        OnlinePaymentTransaction $transaction,
        User $user,
        Student $student,
        array $items,
    ): array {
        $this->ensureConfigured();
        $merchantCode = $this->merchantCode();
        $amount = (int) $transaction->amount;
        $orderId = $transaction->merchant_order_id;
        $customerName = Str::limit(trim((string) ($student->guardian_name ?: $user->name)), 20, '');
        $phone = preg_replace('/[^0-9+]/', '', (string) ($student->whatsapp ?: $student->father_whatsapp ?: $student->mother_whatsapp));
        $expiryMinutes = max(1, (int) config('services.duitku.expiry_minutes', 60));

        $response = $this->post('/webapi/api/merchant/v2/inquiry', [
            'merchantCode' => $merchantCode,
            'paymentAmount' => $amount,
            'paymentMethod' => $transaction->payment_method,
            'merchantOrderId' => $orderId,
            'productDetails' => 'Pembayaran tagihan MA\'WA CENTER',
            'email' => $user->email,
            'phoneNumber' => $phone ?: null,
            'customerVaName' => $customerName ?: 'Wali Santri',
            'itemDetails' => $items,
            'callbackUrl' => (string) config('services.duitku.callback_url'),
            'returnUrl' => (string) config('services.duitku.return_url'),
            'signature' => hash_hmac('sha256', $merchantCode.$orderId.$amount, $this->apiKey()),
            'expiryPeriod' => $expiryMinutes,
        ]);

        if ((string) $response->json('statusCode') !== '00') {
            throw new DuitkuException('Duitku menolak pembuatan transaksi pembayaran.');
        }

        $reference = trim((string) $response->json('reference'));
        $paymentUrl = trim((string) $response->json('paymentUrl'));
        if ($reference === '' || $paymentUrl === '' || (int) $response->json('amount') !== $amount) {
            throw new DuitkuException('Respons transaksi Duitku belum lengkap.');
        }

        return [
            'reference' => $reference,
            'payment_url' => $paymentUrl,
            'va_number' => $response->json('vaNumber'),
            'qr_string' => $response->json('qrString'),
            'app_url' => $response->json('AppUrl') ?? $response->json('appUrl'),
            'expires_at' => now()->addMinutes($expiryMinutes),
            'response' => [
                'merchantCode' => $response->json('merchantCode'),
                'reference' => $reference,
                'paymentUrl' => $paymentUrl,
                'vaNumber' => $response->json('vaNumber'),
                'qrString' => $response->json('qrString'),
                'AppUrl' => $response->json('AppUrl') ?? $response->json('appUrl'),
                'amount' => $response->json('amount'),
                'statusCode' => $response->json('statusCode'),
                'statusMessage' => $response->json('statusMessage'),
            ],
        ];
    }

    private function post(string $path, array $payload): Response
    {
        try {
            $response = Http::acceptJson()
                ->asJson()
                ->timeout(max(1, (int) config('services.duitku.timeout_seconds', 15)))
                ->post(rtrim((string) config('services.duitku.base_url'), '/').$path, $payload);
        } catch (ConnectionException $exception) {
            throw new DuitkuException('Layanan Duitku tidak dapat dihubungi.', previous: $exception);
        }

        if (! $response->successful()) {
            throw new DuitkuException('Layanan Duitku mengembalikan respons yang tidak berhasil.');
        }

        return $response;
    }

    private function ensureConfigured(): void
    {
        if (! config('services.duitku.enabled')) {
            throw new DuitkuException('Pembayaran online belum diaktifkan.');
        }

        foreach (['merchant_code', 'api_key', 'base_url', 'callback_url', 'return_url'] as $key) {
            if (blank(config('services.duitku.'.$key))) {
                throw new DuitkuException('Konfigurasi pembayaran online belum lengkap.');
            }
        }
    }

    private function merchantCode(): string
    {
        return (string) config('services.duitku.merchant_code');
    }

    private function apiKey(): string
    {
        return (string) config('services.duitku.api_key');
    }
}
