<?php

namespace App\Mail;

use App\Models\Contact;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class NewContactNotification extends Mailable
{
    use Queueable, SerializesModels;

    public Contact $contact;

    public function __construct(Contact $contact)
    {
        $this->contact = $contact;
    }

    public function build(): self
    {
        $subject = 'Nuovo contatto ricevuto: ' . $this->contact->name;
        if (! empty($this->contact->subject)) {
            $subject .= ' - ' . $this->contact->subject;
        }

        return $this
            ->subject($subject)
            ->replyTo($this->contact->email, $this->contact->name)
            ->view('emails.new_contact_notification', [
                'contact' => $this->contact,
            ]);
    }
}
