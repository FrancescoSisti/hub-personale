# Guida Completa Sistema Contatti & Gestione Ibernazione

## Panoramica del Sistema

Il sistema di contatti implementato fornisce:

1. **API REST** per ricevere form di contatto esterni
2. **Frontend completo** per gestire i contatti ricevuti
3. **Job di recupero** per contatti ricevuti durante l'ibernazione
4. **Notifiche in tempo reale** con badge nel menu
5. **Script JavaScript** pronto per l'integrazione

---

## 🔗 API per Form di Contatto

### Endpoints Disponibili

**POST `/api/v1/contacts`** - Invia nuovo contatto
- Rate limiting: 5 richieste ogni 5 minuti per IP
- Validazione completa dei dati
- Supporto per campi personalizzati

**GET `/api/v1/contacts/{id}`** - Controlla stato contatto

**GET `/api/v1/contacts`** - Info API e configurazione

### Esempio Integrazione

```html
<!DOCTYPE html>
<html>
<head>
    <title>Contattaci</title>
</head>
<body>
    <form class="contact-form">
        <input type="text" name="name" placeholder="Nome" required>
        <input type="email" name="email" placeholder="Email" required>
        <textarea name="message" placeholder="Messaggio" required></textarea>
        <button type="submit">Invia</button>
        <div class="contact-message"></div>
    </form>

    <script src="/contact-form-integration.js"></script>
</body>
</html>
```

### File Disponibili

- `/contact-form-integration.js` - Script JavaScript per l'integrazione
- `/contact-form-example.html` - Esempio completo funzionante
- `README-contact-api.md` - Documentazione API dettagliata

---

## 📱 Frontend Gestione Contatti

### Pagine Disponibili

**`/contacts`** - Lista contatti con:
- Filtri avanzati (stato, origine, data)
- Ricerca full-text
- Azioni bulk (marca come letto, elimina)
- Esportazione CSV
- Paginazione

**`/contacts/{id}`** - Dettaglio contatto con:
- Visualizzazione completa dei dati
- Azioni rapide (rispondi, copia email)
- Informazioni tecniche (IP, User-Agent)
- Export singolo contatto

### Caratteristiche

- ✅ Interfaccia moderna e responsive
- ✅ Filtri e ricerca in tempo reale
- ✅ Badge di notifica nel menu laterale
- ✅ Esportazione dati (CSV, JSON)
- ✅ Gestione azioni bulk

---

## 🤖 Sistema di Recupero durante Ibernazione

### Job: ProcessMissedContacts

Il job `ProcessMissedContacts` gestisce automaticamente i contatti ricevuti durante l'inattività dell'applicazione.

**Funzionalità:**

- 📊 Analisi dei contatti persi
- 🚨 Rilevamento contatti urgenti
- 📧 Notifiche email agli admin
- 📈 Statistiche dettagliate
- 💾 Cache delle informazioni

### Comando Artisan

```bash
# Elabora contatti persi
php artisan contacts:process-missed

# Con notifiche email
php artisan contacts:process-missed --notify

# Modalità dry-run per vedere cosa verrebbe elaborato
php artisan contacts:process-missed --dry-run

# Elabora da una data specifica
php artisan contacts:process-missed --since="2025-01-24 10:00:00"

# Mostra statistiche
php artisan contacts:process-missed --stats

# Reset ultimo tempo di elaborazione
php artisan contacts:process-missed --reset-last-time
```

### Esempi d'uso

```bash
# Scenario: L'app è stata spenta per manutenzione
php artisan contacts:process-missed --since="yesterday 18:00" --notify --dry-run

# Scenario: Riavvio dopo ibernazione
php artisan contacts:process-missed --notify

# Scenario: Check settimanale
php artisan contacts:process-missed --stats
```

---

## ⚙️ Configurazione e Deployment

### 1. Configurazione Database

```bash
# Esegui le migrazioni
php artisan migrate
```

### 2. Configurazione CORS

Il file `config/cors.php` è già configurato per accettare richieste da qualsiasi origine. Per produzione, personalizza:

```php
'allowed_origins' => [
    'https://your-website.com',
    'https://www.your-website.com',
],
```

### 3. Script di Integrazione

Personalizza l'URL base nello script:

```javascript
const contactAPI = new ContactFormAPI({
    baseUrl: 'https://your-domain.com/api/v1'
});
```

### 4. Notifiche Email

Per abilitare le notifiche email, configura il sistema di mail in `.env` e decommenta il codice nel job:

```php
Mail::raw($body, function ($message) use ($user, $subject) {
    $message->to($user->email)->subject($subject);
});
```

---

## 🔧 Configurazione Automatizzata

### Cron Job per Recupero Automatico

Aggiungi al crontab per elaborazione automatica:

```bash
# Ogni ora controlla contatti persi
0 * * * * cd /path/to/your/app && php artisan contacts:process-missed --notify >/dev/null 2>&1
```

### Job Queue

Per performance migliori, configura le code:

```bash
# .env
QUEUE_CONNECTION=database

# Esegui worker
php artisan queue:work
```

---

## 📊 Monitoraggio e Statistiche

### Dashboard Metrics

Il sistema fornisce metriche automatiche:

- Contatti totali, non letti, giornalieri
- Top origini del traffico
- Rilevamento contatti urgenti
- Statistiche temporali

### Log File

Tutti gli eventi sono loggati in `storage/logs/laravel.log`:

```bash
# Monitora log in tempo reale
tail -f storage/logs/laravel.log | grep "contact"
```

---

## 🚨 Gestione Contatti Urgenti

Il sistema rileva automaticamente contatti urgenti basandosi su parole chiave:

**Italiano:** urgente, emergenza, problema grave, non funziona, aiuto
**Inglese:** urgent, emergency, serious problem, not working, help

I contatti urgenti vengono evidenziati nelle notifiche e nell'interfaccia.

---

## 🔒 Sicurezza

### Rate Limiting
- 5 richieste per 5 minuti per IP
- Configurabile nel controller

### Validazione
- Controllo formato email con DNS
- Sanitizzazione input
- Limite lunghezza messaggi

### Logging
- Tracciamento IP e User-Agent
- Log di tutte le submission
- Rilevamento pattern spam

---

## 🧪 Test e Debug

### Test dell'API

```bash
# Test submission
curl -X POST http://your-domain.com/api/v1/contacts \
  -H "Content-Type: application/json" \
  -d '{
    "name": "Test User",
    "email": "test@example.com",
    "message": "Messaggio di test"
  }'

# Test status
curl http://your-domain.com/api/v1/contacts
```

### Debug Job

```bash
# Test job con debug
php artisan contacts:process-missed --dry-run -v
```

---

## 📦 File del Sistema

### Controller e API
- `app/Http/Controllers/Api/ContactController.php` - API endpoints
- `app/Http/Controllers/ContactManagementController.php` - Frontend
- `app/Http/Requests/StoreContactRequest.php` - Validazione

### Frontend
- `resources/js/pages/Contact/Index.vue` - Lista contatti
- `resources/js/pages/Contact/Show.vue` - Dettaglio contatto

### Job e Comandi
- `app/Jobs/ProcessMissedContacts.php` - Job recupero
- `app/Console/Commands/ProcessMissedContactsCommand.php` - Comando CLI

### Database
- `database/migrations/*_create_contacts_table.php` - Struttura base
- `database/migrations/*_add_processing_fields_to_contacts_table.php` - Campi job

### Integrazione
- `public/contact-form-integration.js` - Script JavaScript
- `public/contact-form-example.html` - Esempio integrazione

---

## 🎯 Prossimi Passi

1. **Configura CORS** per i tuoi domini specifici
2. **Personalizza notifiche email** con il tuo template
3. **Aggiungi cron job** per recupero automatico
4. **Testa l'integrazione** con i tuoi siti web
5. **Monitora i log** per eventuali problemi

Il sistema è pronto per l'uso in produzione! 🚀 