# Funzionalità Upload e Parsing Automatico Buste Paga

## Panoramica

Il sistema ora include una funzionalità avanzata per l'upload e il parsing automatico delle buste paga in formato PDF. Questa funzionalità permette di:

- Caricare file PDF delle buste paga
- Estrarre automaticamente i dati salariali dal PDF
- Generare automaticamente i record degli stipendi
- Monitorare lo stato di elaborazione
- Gestire errori e riprocessare file

## Architettura

### Backend (Laravel)

#### Modelli

**PaySlip Model**

- Gestisce i file delle buste paga caricati
- Traccia lo stato di elaborazione
- Memorizza i dati estratti e gli errori
- Relazioni con User e Salary

**Salary Model (Aggiornato)**

- Aggiunta relazione con PaySlip
- Campo `auto_generated` per distinguere stipendi generati automaticamente
- Campo `pay_slip_id` per collegamento alla busta paga

#### Servizi

**PaySlipParserService**

- Parsing intelligente dei PDF usando `smalot/pdfparser`
- Estrazione automatica di:
    - Stipendio base
    - Bonus e premi
    - Ore straordinari e tariffe
    - Tasse e contributi
    - Detrazioni
    - Totali lordi e netti
    - Periodo di riferimento (mese/anno)
- Pattern matching avanzato per diversi formati di buste paga
- Gestione errori e logging

#### Controller

**PaySlipController**

- Upload sicuro dei file PDF
- Processing asincrono delle buste paga
- Download dei file originali
- Gestione CRUD completa
- Autorizzazioni basate su Policy

### Frontend (Vue 3 + TypeScript)

#### Componenti

**PaySlipUpload.vue**

- Interfaccia drag & drop per upload
- Validazione file (tipo, dimensione)
- Progress bar e feedback visivo
- Gestione errori

**PaySlipList.vue**

- Tabella con stato elaborazione
- Azioni per download, riprocessing, eliminazione
- Badge di stato colorati
- Link agli stipendi generati

**PaySlip/Index.vue**

- Dashboard completa delle buste paga
- Statistiche di elaborazione
- Guida per nuovi utenti

## Funzionalità Principali

### 1. Upload Intelligente

- **Drag & Drop**: Interfaccia intuitiva per trascinare i file
- **Validazione**: Solo PDF, massimo 10MB
- **Feedback**: Progress bar e messaggi di stato
- **Sicurezza**: Validazione lato server e client

### 2. Parsing Automatico

Il sistema riconosce automaticamente:

```
- Stipendio Base: "stipendio base", "retribuzione base", "paga base"
- Bonus: "bonus", "premio", "incentivo", "gratifica"
- Straordinari: "straordinari X ore", "€X/ora"
- Tasse: "IRPEF", "tasse", "imposte", "ritenute"
- Contributi: "contributi", "detrazioni", "trattenute"
- Totali: "totale lordo", "totale netto", "netto in busta"
- Periodo: "MM/YYYY", "Gennaio 2024", dal nome file
```

### 3. Gestione Intelligente del Periodo

- Estrazione automatica da testo del PDF
- Fallback al nome del file (es: "01-2024.pdf")
- Default al mese corrente se non rilevabile

### 4. Prevenzione Duplicati

- Controllo automatico per mese/anno esistenti
- Avviso se esiste già uno stipendio per il periodo
- Opzioni per sovrascrivere o mantenere esistente

### 5. Monitoraggio e Debugging

- Stati di elaborazione: "In elaborazione", "Elaborata", "Errore"
- Log dettagliati degli errori
- Possibilità di riprocessare file falliti
- Visualizzazione dati estratti

## Utilizzo

### 1. Accesso alla Funzionalità

```
Dashboard → Buste Paga (nel menu laterale)
```

### 2. Upload di una Busta Paga

1. Clicca "Carica Busta Paga"
2. Trascina il PDF o clicca per selezionare
3. Il sistema valida e carica il file
4. L'elaborazione inizia automaticamente

### 3. Monitoraggio dell'Elaborazione

- **Verde**: Elaborazione completata con successo
- **Blu**: In elaborazione
- **Rosso**: Errore nell'elaborazione

### 4. Azioni Disponibili

- **Scarica PDF**: Download del file originale
- **Elabora Ora**: Riprocessa file falliti
- **Vedi Stipendio**: Vai al record stipendio generato
- **Elimina**: Rimuove file e stipendio collegato

## Configurazione

### Dipendenze

```bash
composer require smalot/pdfparser
```

### Storage

```bash
php artisan storage:link
```

### Migrazioni

```bash
php artisan migrate
```

## Pattern di Riconoscimento

Il sistema utilizza regex avanzate per riconoscere diversi formati:

### Importi Monetari

```regex
/€?\s*(\d+(?:\.\d{2})?)/i
```

### Periodi

```regex
/(\d{1,2})\/(\d{4})/i  # MM/YYYY
/(gennaio|febbraio|...)\s*(\d{4})/i  # Mese Anno
```

### Campi Specifici

```regex
/stipendio\s*base[:\s]*€?\s*(\d+(?:\.\d{2})?)/i
/straordinari[:\s]*(\d+(?:\.\d{1,2})?)\s*ore/i
/totale\s*netto[:\s]*€?\s*(\d+(?:\.\d{2})?)/i
```

## Sicurezza

### Validazioni

- Tipo file: Solo PDF
- Dimensione: Massimo 10MB
- Autorizzazioni: Solo proprietario può accedere

### Storage

- File memorizzati in `storage/app/public/pay-slips/`
- Nomi file univoci con timestamp
- Accesso controllato tramite Policy

### Privacy

- Ogni utente vede solo le proprie buste paga
- Dati estratti memorizzati in formato JSON crittografato
- Log degli errori senza dati sensibili

## Estensibilità

### Aggiungere Nuovi Pattern

```php
// In PaySlipParserService
private function extractCustomField(string $text): ?float
{
    $patterns = [
        '/nuovo\s*campo[:\s]*€?\s*(\d+(?:\.\d{2})?)/i',
    ];

    return $this->extractAmountByPatterns($text, $patterns);
}
```

### Supporto Nuovi Formati

Il sistema è progettato per essere facilmente estensibile:

1. Aggiungere nuovi pattern in `PaySlipParserService`
2. Estendere il metodo `extractDataFromText()`
3. Aggiornare la mappatura dei campi

## Troubleshooting

### Errori Comuni

**"Impossibile estrarre dati dal PDF"**

- PDF potrebbe essere scansionato (immagine)
- Formato non riconosciuto
- Testo non selezionabile

**"File non trovato"**

- Verificare permessi storage
- Controllare link simbolico

**"Periodo già esistente"**

- Esiste già uno stipendio per quel mese/anno
- Eliminare il record esistente o modificare il periodo

### Debug

```bash
# Visualizza log errori
tail -f storage/logs/laravel.log

# Test parsing manuale
php artisan tinker
$service = app(PaySlipParserService::class);
$paySlip = PaySlip::find(1);
$service->parsePaySlip($paySlip);
```

## Roadmap Future

- [ ] Supporto OCR per PDF scansionati
- [ ] Template personalizzabili per diversi datori di lavoro
- [ ] Batch upload multipli
- [ ] Integrazione con servizi cloud (Google Drive, Dropbox)
- [ ] Export dati per commercialisti
- [ ] Notifiche email per elaborazione completata
- [ ] Dashboard analytics avanzate
- [ ] Supporto formati aggiuntivi (Excel, CSV)

## Contribuire

Per migliorare il sistema di parsing:

1. Aggiungere esempi di buste paga in `tests/fixtures/`
2. Creare test per nuovi pattern
3. Documentare formati supportati
4. Segnalare pattern non riconosciuti

---

**Nota**: Questa funzionalità gestisce dati sensibili. Assicurarsi sempre di rispettare le normative sulla privacy e protezione dati applicabili.
