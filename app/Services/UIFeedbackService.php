<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Support\Facades\Session;

/**
 * UI Feedback Service
 *
 * Provides consistent user feedback messages and notifications
 * across the application with support for multiple types and internationalization.
 */
class UIFeedbackService
{
    /**
     * Flash a success message
     */
    public function success(string $message, ?string $title = null): void
    {
        Session::flash('notification', [
            'type' => 'success',
            'title' => $title ?? __('Success'),
            'message' => $message,
            'icon' => '✅',
        ]);
    }

    /**
     * Flash an error message
     */
    public function error(string $message, ?string $title = null): void
    {
        Session::flash('notification', [
            'type' => 'error',
            'title' => $title ?? __('Error'),
            'message' => $message,
            'icon' => '❌',
        ]);
    }

    /**
     * Flash a warning message
     */
    public function warning(string $message, ?string $title = null): void
    {
        Session::flash('notification', [
            'type' => 'warning',
            'title' => $title ?? __('Warning'),
            'message' => $message,
            'icon' => '⚠️',
        ]);
    }

    /**
     * Flash an info message
     */
    public function info(string $message, ?string $title = null): void
    {
        Session::flash('notification', [
            'type' => 'info',
            'title' => $title ?? __('Information'),
            'message' => $message,
            'icon' => 'ℹ️',
        ]);
    }

    /**
     * Flash a confirmation request
     */
    public function confirm(string $message, string $action, ?string $title = null): void
    {
        Session::flash('confirmation', [
            'title' => $title ?? __('Please Confirm'),
            'message' => $message,
            'action' => $action,
            'icon' => '❓',
        ]);
    }

    /**
     * Common success messages for CRUD operations
     */
    public function created(string $entityName): void
    {
        $this->success(__(':entity has been created successfully.', ['entity' => $entityName]));
    }

    public function updated(string $entityName): void
    {
        $this->success(__(':entity has been updated successfully.', ['entity' => $entityName]));
    }

    public function deleted(string $entityName): void
    {
        $this->success(__(':entity has been deleted successfully.', ['entity' => $entityName]));
    }

    public function restored(string $entityName): void
    {
        $this->success(__(':entity has been restored successfully.', ['entity' => $entityName]));
    }

    /**
     * Common error messages
     */
    public function notFound(string $entityName): void
    {
        $this->error(__(':entity not found.', ['entity' => $entityName]));
    }

    public function unauthorized(): void
    {
        $this->error(__('You are not authorized to perform this action.'));
    }

    public function validationFailed(): void
    {
        $this->error(__('Please check your input and try again.'), __('Validation Failed'));
    }

    public function serverError(): void
    {
        $this->error(__('An unexpected error occurred. Please try again later.'), __('Server Error'));
    }

    /**
     * Operation feedback messages
     */
    public function operationInProgress(string $operation): void
    {
        $this->info(__(':operation is in progress...', ['operation' => $operation]));
    }

    public function operationCompleted(string $operation): void
    {
        $this->success(__(':operation completed successfully.', ['operation' => $operation]));
    }

    public function operationFailed(string $operation): void
    {
        $this->error(__(':operation failed. Please try again.', ['operation' => $operation]));
    }

    /**
     * Stock-related messages
     */
    public function insufficientStock(string $productName, float $available): void
    {
        $this->warning(
            __('Insufficient stock for :product. Available quantity: :qty', [
                'product' => $productName,
                'qty' => $available,
            ]),
            __('Low Stock')
        );
    }

    public function stockUpdated(string $productName): void
    {
        $this->success(__('Stock updated for :product', ['product' => $productName]));
    }

    /**
     * Payment-related messages
     */
    public function paymentReceived(float $amount, string $currency): void
    {
        $this->success(__('Payment of :amount :currency received successfully.', [
            'amount' => number_format($amount, 2),
            'currency' => $currency,
        ]));
    }

    public function paymentFailed(string $reason = ''): void
    {
        $message = __('Payment failed.');
        if ($reason) {
            $message .= ' ' . $reason;
        }
        $this->error($message);
    }

    /**
     * Import/Export messages
     */
    public function importStarted(int $totalRecords): void
    {
        $this->info(__('Importing :count records...', ['count' => $totalRecords]));
    }

    public function importCompleted(int $successCount, int $failedCount = 0): void
    {
        if ($failedCount > 0) {
            $this->warning(
                __('Import completed. :success successful, :failed failed.', [
                    'success' => $successCount,
                    'failed' => $failedCount,
                ]),
                __('Import Summary')
            );
        } else {
            $this->success(__('Successfully imported :count records.', ['count' => $successCount]));
        }
    }

    public function exportCompleted(string $filename): void
    {
        $this->success(__('Export completed. File: :filename', ['filename' => $filename]));
    }

    /**
     * Email/Notification messages
     */
    public function emailSent(string $recipient): void
    {
        $this->success(__('Email sent to :recipient', ['recipient' => $recipient]));
    }

    public function emailFailed(string $recipient): void
    {
        $this->error(__('Failed to send email to :recipient', ['recipient' => $recipient]));
    }

    public function notificationSent(): void
    {
        $this->success(__('Notification sent successfully.'));
    }

    /**
     * File upload messages
     */
    public function fileUploaded(string $filename): void
    {
        $this->success(__('File uploaded: :filename', ['filename' => $filename]));
    }

    public function fileUploadFailed(string $reason = ''): void
    {
        $message = __('File upload failed.');
        if ($reason) {
            $message .= ' ' . $reason;
        }
        $this->error($message);
    }

    public function fileTooLarge(int $maxSizeMB): void
    {
        $this->error(__('File is too large. Maximum size: :size MB', ['size' => $maxSizeMB]));
    }

    public function invalidFileType(string $allowedTypes): void
    {
        $this->error(__('Invalid file type. Allowed types: :types', ['types' => $allowedTypes]));
    }

    /**
     * Sync/Integration messages
     */
    public function syncStarted(string $integration): void
    {
        $this->info(__('Starting sync with :integration...', ['integration' => $integration]));
    }

    public function syncCompleted(string $integration, int $recordsProcessed): void
    {
        $this->success(
            __('Sync with :integration completed. :count records processed.', [
                'integration' => $integration,
                'count' => $recordsProcessed,
            ])
        );
    }

    public function syncFailed(string $integration, string $reason = ''): void
    {
        $message = __('Sync with :integration failed.', ['integration' => $integration]);
        if ($reason) {
            $message .= ' ' . $reason;
        }
        $this->error($message);
    }

    /**
     * Batch operation messages
     */
    public function batchOperationStarted(int $totalItems): void
    {
        $this->info(__('Processing :count items...', ['count' => $totalItems]));
    }

    public function batchOperationCompleted(int $successCount, int $failedCount = 0): void
    {
        if ($failedCount > 0) {
            $this->warning(
                __('Batch operation completed. :success successful, :failed failed.', [
                    'success' => $successCount,
                    'failed' => $failedCount,
                ])
            );
        } else {
            $this->success(__('Successfully processed :count items.', ['count' => $successCount]));
        }
    }

    /**
     * System maintenance messages
     */
    public function maintenanceScheduled(\DateTime $scheduledTime): void
    {
        $this->warning(
            __('System maintenance scheduled for :time', ['time' => $scheduledTime->format('Y-m-d H:i')]),
            __('Maintenance Notice')
        );
    }

    public function backupCompleted(): void
    {
        $this->success(__('Database backup completed successfully.'));
    }

    public function backupFailed(): void
    {
        $this->error(__('Database backup failed. Please check logs.'));
    }
}
