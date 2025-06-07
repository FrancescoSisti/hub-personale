# 💰 Gestione Stipendi - Hub Personale

## Panoramica

La funzionalità di **Gestione Stipendi** è stata aggiunta all'hub personale per permettere il monitoraggio completo dei guadagni mensili con analisi dettagliate, grafici interattivi e statistiche avanzate.

## 🚀 Funzionalità Implementate

### 📊 Dashboard Widget

- **Riepilogo mensile**: Visualizzazione dello stipendio del mese corrente
- **Statistiche annuali**: Totale annuale e media mensile
- **Progress bar**: Progresso dei mesi registrati (x/12)
- **Azioni rapide**: Link diretti per visualizzare tutto o aggiungere nuovo stipendio

### 📈 Pagina Principale Stipendi

- **Cards statistiche**: 4 card con metriche chiave (stipendio corrente, totale netto, totale lordo, straordinari)
- **Filtro per anno**: Selezione dell'anno per visualizzare dati specifici
- **Grafici interattivi**:
    - Grafico a linee per andamento lordo/netto
    - Grafico a barre per tasse, detrazioni e straordinari
- **Tabella dettagliata**: Elenco completo con azioni (visualizza, modifica, elimina)

### ✏️ Gestione Stipendi

- **Form di creazione**: Interfaccia intuitiva per inserire nuovo stipendio
- **Calcolo automatico**: Anteprima in tempo reale di lordo e netto
- **Validazione**: Controlli di validità sui dati inseriti
- **Campi supportati**:
    - Stipendio base (obbligatorio)
    - Bonus
    - Ore straordinari + tariffa oraria
    - Tasse
    - Detrazioni
    - Note

### 📊 Analisi e Statistiche

- **Statistiche dettagliate**: Medie mensili, aliquote fiscali, trend
- **Grafici avanzati**: Chart.js per visualizzazioni professionali
- **Percentuali**: Visualizzazione grafica delle percentuali di tasse/detrazioni
- **Trend analysis**: Calcolo automatico del trend mensile

## 🛠️ Architettura Tecnica

### Backend (Laravel)

```
app/
├── Models/
│   └── Salary.php              # Modello Eloquent con relazioni e scope
├── Services/
│   └── SalaryService.php       # Logica di business centralizzata
├── Http/
│   ├── Controllers/
│   │   └── SalaryController.php # Controller RESTful completo
│   └── Requests/
│       ├── StoreSalaryRequest.php
│       └── UpdateSalaryRequest.php
└── Policies/
    └── SalaryPolicy.php        # Autorizzazioni per operazioni CRUD
```

### Frontend (Vue 3 + TypeScript)

```
resources/js/
├── pages/Salary/
│   ├── Index.vue               # Pagina principale con dashboard
│   ├── Create.vue              # Form di creazione
│   ├── Edit.vue                # Form di modifica
│   └── Show.vue                # Visualizzazione dettaglio
├── components/salary/
│   ├── SalaryChart.vue         # Grafici Chart.js
│   ├── SalaryStatistics.vue    # Widget statistiche
│   ├── SalaryTable.vue         # Tabella dati
│   └── SalaryDashboardWidget.vue # Widget dashboard
└── components/ui/select/       # Componenti UI personalizzati
```

### Database

```sql
CREATE TABLE salaries (
    id BIGINT PRIMARY KEY,
    user_id BIGINT FOREIGN KEY,
    base_salary DECIMAL(10,2),
    bonus DECIMAL(10,2) DEFAULT 0,
    overtime_hours DECIMAL(8,2) DEFAULT 0,
    overtime_rate DECIMAL(8,2) DEFAULT 0,
    deductions DECIMAL(10,2) DEFAULT 0,
    net_salary DECIMAL(10,2),
    gross_salary DECIMAL(10,2),
    tax_amount DECIMAL(10,2) DEFAULT 0,
    month INTEGER,
    year INTEGER,
    notes TEXT,
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    UNIQUE(user_id, month, year)
);
```

## 🎨 UI/UX Features

### Design System

- **Tailwind CSS**: Styling consistente e responsive
- **Reka UI**: Componenti accessibili e moderni
- **Lucide Icons**: Iconografia coerente
- **Dark Mode**: Supporto completo tema scuro/chiaro

### Responsive Design

- **Mobile First**: Ottimizzato per dispositivi mobili
- **Grid Layout**: Layout adattivo per desktop/tablet
- **Touch Friendly**: Interfaccia ottimizzata per touch

### Accessibilità

- **ARIA Labels**: Etichette per screen reader
- **Keyboard Navigation**: Navigazione completa da tastiera
- **Color Contrast**: Contrasti conformi WCAG

## 📊 Metriche e KPI

### Calcoli Automatici

- **Stipendio Lordo**: Base + Bonus + (Ore Straordinari × Tariffa)
- **Stipendio Netto**: Lordo - Tasse - Detrazioni
- **Aliquota Fiscale**: (Tasse / Lordo) × 100
- **Trend Mensile**: Variazione percentuale primo/ultimo mese

### Statistiche Disponibili

- Media mensile lordo/netto
- Totali annuali
- Percentuali di tassazione
- Analisi straordinari
- Top earning months

## 🔒 Sicurezza

### Autorizzazioni

- **Policy-based**: Ogni operazione controllata da SalaryPolicy
- **User Isolation**: Ogni utente vede solo i propri dati
- **CSRF Protection**: Protezione contro attacchi CSRF

### Validazione

- **Server-side**: Validazione completa lato server
- **Client-side**: Feedback immediato per UX
- **Type Safety**: TypeScript per type checking

## 🚀 Installazione e Setup

### 1. Migrazione Database

```bash
php artisan migrate
```

### 2. Seeder Dati di Test

```bash
php artisan db:seed --class=SalarySeeder
```

### 3. Dipendenze Frontend

```bash
npm install chart.js vue-chartjs
```

### 4. Build Assets

```bash
npm run dev
# oppure per produzione
npm run build
```

## 📱 Utilizzo

### Accesso Rapido

1. **Dashboard**: Widget con riepilogo nella homepage
2. **Sidebar**: Link diretto "Stipendi" nella navigazione
3. **URL Diretti**: `/salaries` per la pagina principale

### Workflow Tipico

1. **Registrazione**: Aggiungi nuovo stipendio mensile
2. **Monitoraggio**: Visualizza grafici e statistiche
3. **Analisi**: Esamina trend e performance
4. **Gestione**: Modifica o elimina record esistenti

## 🔮 Sviluppi Futuri

### Funzionalità Pianificate

- **Export PDF**: Generazione report mensili/annuali
- **Notifiche**: Reminder per inserimento stipendio mensile
- **Obiettivi**: Setting e tracking obiettivi di guadagno
- **Confronti**: Analisi comparative anno su anno
- **Previsioni**: Proiezioni basate su dati storici

### Integrazioni Possibili

- **API Bancarie**: Import automatico da estratti conto
- **Sistemi HR**: Integrazione con software aziendali
- **Contabilità**: Export per commercialisti
- **Mobile App**: Applicazione mobile dedicata

## 🤝 Contributi

La funzionalità è stata sviluppata seguendo le best practice Laravel e Vue.js:

- **SOLID Principles**: Architettura modulare e estendibile
- **Clean Code**: Codice leggibile e manutenibile
- **Testing Ready**: Struttura preparata per unit/feature tests
- **Documentation**: Codice ben documentato con PHPDoc/JSDoc

---

**Sviluppato con ❤️ per l'Hub Personale**
