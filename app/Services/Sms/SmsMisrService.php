<?php

declare(strict_types=1);

namespace App\Services\Sms;

use App\Services\SettingsService;
use App\Traits\HandlesServiceErrors;
use Illuminate\Support\Facades\Log;

class SmsMisrService implements SmsServiceInterface
{
    use HandlesServiceErrors;

    protected array $config;

    public function __construct(protected SettingsService $settings)
    {
        $this->config = $settings->getSmsConfig('smsmisr');
    }

    public function send(string $to, string $message, ?string $filePath = null): array
    {
        return $this->handleServiceOperation(
            callback: function () use ($to, $message) {
                if (! $this->isConfigured()) {
                    return [
                        'success' => false,
                        'error' => 'SMS provider not configured',
                    ];
                }

                $unicodeHexText = $this->convertToUnicodeHex($message);

                $data = http_build_query([
                    'environment' => $this->config['sandbox'] ? 2 : 1,
                    'username' => $this->config['username'],
                    'password' => $this->config['password'],
                    'sender' => $this->config['sender_id'],
                    'mobile' => $this->formatPhone($to),
                    'language' => 3,
                    'message' => $unicodeHexText,
                ]);

                $ch = curl_init();
                curl_setopt($ch, CURLOPT_URL, 'https://smsmisr.com/api/SMS/');
                curl_setopt($ch, CURLOPT_POST, 1);
                curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
                curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
                curl_setopt($ch, CURLOPT_TIMEOUT, 30);

                $response = curl_exec($ch);
                $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

                if (curl_errno($ch)) {
                    $error = curl_error($ch);
                    curl_close($ch);
                    throw new \RuntimeException('cURL Error: '.$error);
                }

                curl_close($ch);

                $result = json_decode($response, true) ?? ['raw' => $response];

                Log::info('SMSMISR SMS sent', [
                    'to' => $to,
                    'http_code' => $httpCode,
                    'response' => $result,
                ]);

                return [
                    'success' => $httpCode >= 200 && $httpCode < 300,
                    'response' => $result,
                    'provider' => 'smsmisr',
                ];
            },
            operation: 'send',
            context: ['to' => $to],
            defaultValue: ['success' => false, 'error' => 'SMS sending failed', 'provider' => 'smsmisr']
        );
    }

    public function isConfigured(): bool
    {
        return $this->config['enabled']
            && ! empty($this->config['username'])
            && ! empty($this->config['password'])
            && ! empty($this->config['sender_id']);
    }

    public function getProviderName(): string
    {
        return 'smsmisr';
    }

    protected function formatPhone(string $phone): string
    {
        $phone = preg_replace('/[^0-9]/', '', $phone);

        if (str_starts_with($phone, '0')) {
            $phone = '2'.$phone;
        }

        if (! str_starts_with($phone, '20')) {
            $phone = '20'.$phone;
        }

        return $phone;
    }

    protected function convertToUnicodeHex(string $text): string
    {
        $unicodeHex = '';
        for ($i = 0; $i < mb_strlen($text, 'UTF-8'); $i++) {
            $char = mb_substr($text, $i, 1, 'UTF-8');
            $unicodePoint = unpack('N', mb_convert_encoding($char, 'UCS-4BE', 'UTF-8'));
            $unicodeHex .= sprintf('%04x', $unicodePoint[1]);
        }

        return strtoupper($unicodeHex);
    }
}
