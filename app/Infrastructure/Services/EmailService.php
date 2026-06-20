<?php

namespace App\Infrastructure\Services;

use App\Infrastructure\Persistence\Models\Contact;
use App\Infrastructure\Persistence\Models\EmailLog;
use App\Infrastructure\Persistence\Models\EmailTemplate;
use App\Mail\GenericTemplateMail;
use Illuminate\Support\Facades\Mail;

class EmailService
{
    public function sendTemplate(
        string $slug,
        string $toEmail,
        array $vars = [],
        ?Contact $contact = null,
        ?string $toName = null,
    ): EmailLog {
        $template = EmailTemplate::where('slug', $slug)->where('is_active', true)->firstOrFail();

        $subject = $template->renderSubject($vars);
        $bodyHtml = $template->renderBody($vars);

        $log = EmailLog::create([
            'agency_id' => $template->agency_id,
            'email_template_id' => $template->id,
            'contact_id' => $contact?->id,
            'sent_by' => auth()->id(),
            'to_email' => $toEmail,
            'to_name' => $toName ?? $contact?->full_name,
            'subject' => $subject,
            'status' => 'queued',
        ]);

        try {
            Mail::to($toEmail, $toName)->send(new GenericTemplateMail($subject, $bodyHtml, $template->agency_id));
            $log->update(['status' => 'sent', 'sent_at' => now()]);
        } catch (\Throwable $e) {
            $log->update(['status' => 'failed', 'error_message' => $e->getMessage()]);
        }

        return $log;
    }

    public function sendRaw(
        string $toEmail,
        string $subject,
        string $bodyHtml,
        ?Contact $contact = null,
        int $agencyId = 0,
    ): EmailLog {
        $log = EmailLog::create([
            'agency_id' => $agencyId ?: (auth()->user()?->agency_id ?? 0),
            'contact_id' => $contact?->id,
            'sent_by' => auth()->id(),
            'to_email' => $toEmail,
            'to_name' => $contact?->full_name,
            'subject' => $subject,
            'status' => 'queued',
        ]);

        try {
            Mail::to($toEmail)->send(new GenericTemplateMail($subject, $bodyHtml, $log->agency_id));
            $log->update(['status' => 'sent', 'sent_at' => now()]);
        } catch (\Throwable $e) {
            $log->update(['status' => 'failed', 'error_message' => $e->getMessage()]);
        }

        return $log;
    }
}
