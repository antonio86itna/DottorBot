# DottorBot — Architettura di agenti

## Obiettivo
Fornire risposte **informative** su salute e benessere con toni prudenti, citazioni quando servono e rispetto delle normative UE/Italia. **DottorBot non è un dispositivo medico e non sostituisce un medico.**

## Panoramica agenti

- **Router** (LLM piccolo: OpenAI GPT‑5 nano): classifica la richiesta in:
  - `general` → risposta diretta
  - `needs_sources` → attiva Ricercatore
  - `emergency` → mostra solo il messaggio 112
- **Consulente** (LLM standard: OpenAI GPT‑5 nano/mini o GPT‑5): scrive la risposta finale, in italiano, strutturata in: *Sintesi → Cosa puoi fare adesso (non clinico) → Quando contattare un medico → Fonti (se presenti)*. Aggiunge il **disclaimer** fisso.
- **Ricercatore** (Perplexity Sonar/Sonar‑Pro): esegue ricerca web con citazioni, restituisce snippet + elenco fonti (title, publisher, url). Parametri: `search_context_size` bilanciato per costo/completezza. 
- **Safety/Triage** (LLM piccolo): rileva emergenze, autolesionismo, sostanze/terapie non sicure, gravidanza/neonati. Se emergenza → blocco output + 112. 
- **PII Scrubber**: rimuove o maschera dati personali/sanitari prima di inviare a provider esterni (se l’utente non ha acconsentito).
- **Journal Analyst**: su richiesta utente, analizza ultime voci del diario (massimo N), segnala pattern **senza diagnosi**.
- **Localization/A11y Helper**: uniforma tono, leggibilità, heading, liste, link accessibili; evita gergo.

## Orchestrazione

1. **Classifica** → `Router`  
2. **Se `needs_sources`** → `Ricercatore` (Perplexity) → `Consulente` fonde contesto+fonti  
3. **Altrimenti** → `Consulente` risponde direttamente  
4. **Sempre** → `Safety/Triage` filtra prima dell’invio  
5. **Render** → UI mostra corpo, note pratiche, sezione “Fonti” (facoltativa), disclaimer fisso

## Prompt & policy (estratto)

- **System (Consulente)**:  
  - “Non fare diagnosi. Usa linguaggio ipotetico (‘potrebbe’). Fornisci consigli generali e indicazioni su quando rivolgersi a un medico. Se richieste terapie/farmaci, mantieniti informativo e invita a consulto. Aggiungi sempre il disclaimer.”
- **System (Ricercatore)**:  
  - “Cerca solo fonti affidabili (enti pubblici sanitari, linee guida, riviste peer‑review), evita blog non verificati; restituisci citazioni complete.”
- **Emergency rule**:  
  - “Se sospetti emergenza, rispondi esclusivamente: ‘Se pensi sia un’emergenza, contatta il 112…’”.

## Metriche & logging
- `source_usage` (usato Perplexity: sì/no), `emergency_flags`, `user_feedback`, `click_sources`.  
- Nessuna PII nei log. Conservazione minima, purge schedulato.

## Limiti dichiarati
- Non è telemedicina; non fornisce diagnosi o prescrizioni.
- Contenuti aggiornabili via ricerca web, ma potrebbero non riflettere l’ultima evidenza clinica.
- Per questioni urgenti o personali, consultare un professionista sanitario.

## Note per sviluppatori
- Dopo ogni clone o pull eseguire `npm install` e `npm run build` per rigenerare CSS/JS.
- Nella cartella `wp-content/themes/dottorbot-theme` lanciare `composer install --no-dev`.
- Garantire accessibilità (WCAG 2.2 AA) e internazionalizzazione di tutte le interfacce.

