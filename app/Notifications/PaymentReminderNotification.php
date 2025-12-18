<?php

declare(strict_types=1);

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PaymentReminderNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly object $alert
    ) {}

    public function via(object $notifiable): array
    {
        $channels = ['database'];
        
        if ($notifiable->email) {
            $channels[] = 'mail';
        }
        
        return $channels;
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject(__('Payment Reminder'))
            ->greeting(__('Hello :name', ['name' => $notifiable->name]))
            ->line(__('This is a friendly reminder that you have an outstanding payment.'))
            ->line(__('Invoice: :reference', ['reference' => $this->alert->reference]))
            ->line(__('Amount Due: :amount', ['amount' => money($this->alert->amount_due)]))
            ->line(__('Due Date: :date', ['date' => $this->alert->due_date]))
            ->action(__('View Invoice'), url('/invoices/' . $this->alert->id))
            ->line(__('Thank you for your business!'));
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'payment_reminder',
            'reference' => $this->alert->reference,
            'amount_due' => $this->alert->amount_due,
            'due_date' => $this->alert->due_date,
            'message' => __('Payment reminder for invoice :reference', ['reference' => $this->alert->reference]),
        ];
    }
}
