<?php

namespace App\Services;

use App\Models\Setting;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class PayPalService
{
    private string $clientId;
    private string $clientSecret;
    private string $baseUrl;
    private bool $isSandbox;

    public function __construct()
    {
        $this->isSandbox = Setting::get('paypal_mode', 'sandbox') === 'sandbox';

        if ($this->isSandbox) {
            $this->clientId     = Setting::get('paypal_sandbox_client_id', '');
            $this->clientSecret = Setting::get('paypal_sandbox_client_secret', '');
            $this->baseUrl      = 'https://api-m.sandbox.paypal.com';
        } else {
            $this->clientId     = Setting::get('paypal_live_client_id', '');
            $this->clientSecret = Setting::get('paypal_live_client_secret', '');
            $this->baseUrl      = 'https://api-m.paypal.com';
        }
    }

    public function isConfigured(): bool
    {
        return !empty($this->clientId) && !empty($this->clientSecret);
    }

    public function isSandboxMode(): bool
    {
        return $this->isSandbox;
    }

    /**
     * Get an OAuth2 access token from PayPal.
     */
    public function getAccessToken(): string
    {
        $response = Http::withBasicAuth($this->clientId, $this->clientSecret)
            ->asForm()
            ->post("{$this->baseUrl}/v1/oauth2/token", [
                'grant_type' => 'client_credentials',
            ]);

        if (!$response->successful()) {
            Log::error('PayPal: failed to get access token', ['body' => $response->body()]);
            throw new \RuntimeException('No se pudo obtener el token de PayPal: ' . $response->body());
        }

        return $response->json('access_token');
    }

    /**
     * Create a PayPal order. If $payeeEmail is provided, the payment goes directly to that PayPal account.
     */
    public function createOrder(float $amount, string $currency, string $purchaseUuid, ?string $payeeEmail = null): array
    {
        $token = $this->getAccessToken();

        $purchaseUnit = [
            'reference_id' => $purchaseUuid,
            'amount'       => [
                'currency_code' => strtoupper($currency),
                'value'         => number_format($amount, 2, '.', ''),
            ],
        ];

        if ($payeeEmail) {
            $purchaseUnit['payee'] = ['email_address' => $payeeEmail];
        }

        $response = Http::withToken($token)
            ->post("{$this->baseUrl}/v2/checkout/orders", [
                'intent'         => 'CAPTURE',
                'purchase_units' => [$purchaseUnit],
            ]);

        if (!$response->successful()) {
            Log::error('PayPal: createOrder failed', ['body' => $response->body(), 'uuid' => $purchaseUuid]);
            throw new \RuntimeException('Error al crear la orden PayPal: ' . $response->body());
        }

        return $response->json();
    }

    /**
     * Capture an approved PayPal order.
     */
    public function captureOrder(string $orderId): array
    {
        $token = $this->getAccessToken();

        $response = Http::withToken($token)
            ->post("{$this->baseUrl}/v2/checkout/orders/{$orderId}/capture");

        if (!$response->successful()) {
            Log::error('PayPal: captureOrder failed', ['order_id' => $orderId, 'body' => $response->body()]);
            throw new \RuntimeException('Error al capturar la orden PayPal: ' . $response->body());
        }

        return $response->json();
    }

    /**
     * Get order details.
     */
    public function getOrder(string $orderId): array
    {
        $token = $this->getAccessToken();

        $response = Http::withToken($token)
            ->get("{$this->baseUrl}/v2/checkout/orders/{$orderId}");

        if (!$response->successful()) {
            throw new \RuntimeException('Error al obtener la orden PayPal.');
        }

        return $response->json();
    }

    public function getClientId(): string
    {
        return $this->clientId;
    }
}
