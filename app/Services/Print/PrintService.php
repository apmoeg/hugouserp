<?php

declare(strict_types=1);

namespace App\Services\Print;

use Illuminate\Support\Facades\View;

class PrintService
{
    public function renderPosReceipt(array $data): string
    {
        return View::make('prints.pos-receipt', $this->preparePosReceiptData($data))->render();
    }

    public function renderRentalContract(array $data): string
    {
        return View::make('prints.rental-contract', $this->prepareContractData($data))->render();
    }

    public function renderInvoice(array $data): string
    {
        return View::make('prints.invoice', $this->prepareInvoiceData($data))->render();
    }

    protected function preparePosReceiptData(array $data): array
    {
        return [
            'store' => $data['store'] ?? [
                'name' => config('app.name'),
                'address' => '',
                'phone' => '',
                'tax_number' => '',
            ],
            'receipt' => [
                'number' => $data['receipt_number'] ?? $this->generateReceiptNumber(),
                'date' => $data['date'] ?? now()->format('Y-m-d H:i'),
                'cashier' => $data['cashier'] ?? '',
                'customer' => $data['customer_name'] ?? null,
                'barcode' => $data['barcode'] ?? null,
            ],
            'items' => $data['items'] ?? [],
            'totals' => [
                'subtotal' => $data['subtotal'] ?? 0,
                'discount' => $data['discount'] ?? 0,
                'tax' => $data['tax'] ?? 0,
                'tax_rate' => $data['tax_rate'] ?? 0,
                'total' => $data['total'] ?? 0,
            ],
            'payment' => [
                'method' => $data['payment_method'] ?? 'Cash',
                'amount' => $data['amount_paid'] ?? 0,
                'change' => $data['change'] ?? 0,
            ],
            'currency' => $data['currency'] ?? '$',
            'footer' => [
                'message' => $data['footer_message'] ?? __('Thank you for your purchase!'),
                'return_policy' => $data['return_policy'] ?? null,
            ],
        ];
    }

    protected function prepareContractData(array $data): array
    {
        return [
            'company' => $data['company'] ?? [
                'name' => config('app.name'),
                'address' => '',
                'phone' => '',
                'email' => '',
                'license' => '',
            ],
            'contract' => [
                'number' => $data['contract_number'] ?? $this->generateContractNumber(),
                'date' => $data['contract_date'] ?? now()->format('Y-m-d'),
                'start_date' => $data['start_date'] ?? '',
                'end_date' => $data['end_date'] ?? '',
                'duration' => $data['duration'] ?? 12,
            ],
            'landlord' => $data['landlord'] ?? [],
            'tenant' => $data['tenant'] ?? [],
            'property' => $data['property'] ?? [],
            'financial' => [
                'monthly_rent' => $data['monthly_rent'] ?? 0,
                'deposit' => $data['deposit'] ?? 0,
                'annual_total' => $data['annual_total'] ?? 0,
                'payment_due' => $data['payment_due'] ?? __('Monthly'),
            ],
            'terms' => $data['terms'] ?? [],
            'currency' => $data['currency'] ?? '$',
        ];
    }

    protected function prepareInvoiceData(array $data): array
    {
        return [
            'company' => $data['company'] ?? [
                'name' => config('app.name'),
                'address' => '',
                'phone' => '',
                'email' => '',
                'tax_number' => '',
            ],
            'invoice' => [
                'number' => $data['invoice_number'] ?? $this->generateInvoiceNumber(),
                'date' => $data['invoice_date'] ?? now()->format('Y-m-d'),
                'due_date' => $data['due_date'] ?? now()->addDays(30)->format('Y-m-d'),
                'status' => $data['status'] ?? 'pending',
            ],
            'customer' => $data['customer'] ?? [],
            'items' => $data['items'] ?? [],
            'totals' => [
                'subtotal' => $data['subtotal'] ?? 0,
                'discount' => $data['discount'] ?? 0,
                'discount_rate' => $data['discount_rate'] ?? 0,
                'tax' => $data['tax'] ?? 0,
                'tax_rate' => $data['tax_rate'] ?? 0,
                'shipping' => $data['shipping'] ?? 0,
                'total' => $data['total'] ?? 0,
            ],
            'payment' => [
                'method' => $data['payment_method'] ?? 'Bank Transfer',
                'amount_paid' => $data['amount_paid'] ?? 0,
                'reference' => $data['payment_reference'] ?? null,
            ],
            'bank' => $data['bank'] ?? null,
            'notes' => $data['notes'] ?? null,
            'currency' => $data['currency'] ?? '$',
        ];
    }

    protected function generateReceiptNumber(): string
    {
        return 'RCP-'.date('Ymd').'-'.str_pad((string) random_int(1, 9999), 4, '0', STR_PAD_LEFT);
    }

    protected function generateContractNumber(): string
    {
        return 'CNT-'.date('Y').'-'.str_pad((string) random_int(1, 9999), 4, '0', STR_PAD_LEFT);
    }

    protected function generateInvoiceNumber(): string
    {
        $prefix = setting('sales.invoice_prefix', 'INV-');
        return $prefix.date('Ymd').'-'.str_pad((string) random_int(1, 9999), 4, '0', STR_PAD_LEFT);
    }
}
