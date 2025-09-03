<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nuovo contatto ricevuto</title>
    <style>
        body { font-family: Arial, Helvetica, sans-serif; color: #111827; }
        .container { max-width: 640px; margin: 0 auto; padding: 24px; }
        .card { border: 1px solid #e5e7eb; border-radius: 12px; padding: 20px; }
        .label { color: #6b7280; font-size: 12px; text-transform: uppercase; letter-spacing: .04em; }
        .value { font-size: 14px; margin-top: 4px; }
        .muted { color: #6b7280; }
        .divider { height: 1px; background: #e5e7eb; margin: 16px 0; }
        pre { white-space: pre-wrap; word-wrap: break-word; font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono", "Courier New", monospace; }
    </style>

</head>
<body>
    <div class="container">
        <h2>Nuovo contatto ricevuto</h2>
        <p class="muted">Hai ricevuto un nuovo messaggio dal form contatti.</p>

        <div class="card">
            <div>
                <div class="label">Nome</div>
                <div class="value">{{ $contact->name }}</div>
            </div>
            <div class="divider"></div>
            <div>
                <div class="label">Email</div>
                <div class="value"><a href="mailto:{{ $contact->email }}">{{ $contact->email }}</a></div>
            </div>
            @if($contact->phone)
            <div class="divider"></div>
            <div>
                <div class="label">Telefono</div>
                <div class="value">{{ $contact->phone }}</div>
            </div>
            @endif
            @if($contact->company)
            <div class="divider"></div>
            <div>
                <div class="label">Azienda</div>
                <div class="value">{{ $contact->company }}</div>
            </div>
            @endif
            @if($contact->subject)
            <div class="divider"></div>
            <div>
                <div class="label">Oggetto</div>
                <div class="value">{{ $contact->subject }}</div>
            </div>
            @endif
            <div class="divider"></div>
            <div>
                <div class="label">Messaggio</div>
                <div class="value"><pre>{{ $contact->message }}</pre></div>
            </div>
            <div class="divider"></div>
            <div>
                <div class="label">Origine</div>
                <div class="value">{{ $contact->origin ?? 'sconosciuta' }}</div>
            </div>
            <div class="divider"></div>
            <div>
                <div class="label">Ricevuto</div>
                <div class="value">{{ $contact->created_at->format('d/m/Y H:i') }}</div>
            </div>
        </div>

        <p class="muted" style="margin-top: 16px;">Rispondi direttamente a questa email per contattare {{ $contact->name }}.</p>
    </div>
</body>
</html>


