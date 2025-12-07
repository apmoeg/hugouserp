<?php

declare(strict_types=1);

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class ScheduledReportMail extends Mailable
{
    use Queueable;
    use SerializesModels;

    public string $routeName;

    /**
     * @var array<string, mixed>
     */
    public array $filters;

    public string $url;

    public string $outputType;

    /**
     * @param  array<string, mixed>  $filters
     */
    public function __construct(string $routeName, array $filters, string $url, string $outputType = 'web')
    {
        $this->routeName = $routeName;
        $this->filters = $filters;
        $this->url = $url;
        $this->outputType = $outputType;
    }

    public function build(): self
    {
        return $this->subject(__('Scheduled report'))
            ->view('emails.scheduled-report');
    }
}
