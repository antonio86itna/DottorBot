# DottorBot

Assistente AI **informativo** per la salute. Chat, diario, suggerimenti prudenti e (quando serve) **fonti verificate**.  
**Non sostituisce un medico.**

## Perché
- Trovare rapidamente informazioni di qualità con un tono umano e prudente.
- Tenere un diario di sintomi/umore e visualizzare pattern.
- Capire **quando** rivolgersi a un professionista.

## Architettura (alto livello)
- **Tema WordPress**: UI Tailwind, blocco/shortcode chat, pagine legali, i18n, accessibilità.
- **Plugin WordPress**: orchestrazione AI, REST API, consensi, storage cifrato, export/cancellazione, abbonamenti.
- **PWA**: manifest + service worker con cache offline e push giornalieri per il diario.

### Modelli & provider
- **Default**: OpenAI *GPT‑5 nano* (rapido/low‑cost).  
- **Ricerca web + fonti**: Perplexity *Sonar/Sonar‑Pro* (citazioni integrate).  
- **Opzione basic**: Google *Gemini 2.5 Flash* (selezionabile in impostazioni).

> Le chiavi API sono gestite solo lato server. Niente chiavi nel frontend.

## Come funziona la risposta
1. Classificazione (GPT‑5 nano): `general`, `needs_sources`, `emergency`.  
2. Se servono fonti: Perplexity → elenco citazioni → sintesi finale.  
3. Safety: filtro emergenze e contenuti non sicuri.  
4. Output strutturato + **disclaimer** obbligatorio.

## Installazione (dev)
1. Clona la repo.  
2. Copia il tema `wp-content/themes/dottorbot-theme` e il plugin `wp-content/plugins/dottorbot`.  
3. In WordPress → Attiva tema e plugin.  
4. In **Impostazioni → DottorBot** incolla le API key e scegli il modello default.
5. Aggiungi lo shortcode `[dottorbot]` per la chat o `[dottorbot_diary]` per il diario con grafici.

## Abbonamenti
- Free: X risposte/mese, diario base.  
- Premium: risposte illimitate, ricerca web con citazioni, insight avanzati e export PDF.  
- Supporto WooCommerce Subscriptions / Stripe.

## Privacy & sicurezza (riassunto)
- Consenso esplicito per trattare dati sanitari nell’account.  
- “Local‑only mode” opzionale.  
- Esporta/cancella dati (art. 20 GDPR), retention minima, cifratura a riposo.  
- Le API key sono lato server; PII scrubber prima delle chiamate esterne.

## Accessibilità
- Conformità **WCAG 2.2 AA**. Focus management, ARIA, modalità ridotta animazioni.  
- Test con tastiera e screen reader.

## Roadmap
- Wearables (passerelle dati) in sola lettura.
- A/B test per onboarding e paywall.  
- Evals di qualità e allineamento medico‑informativo.

## Note legali
DottorBot **non è un dispositivo medico** e non fornisce diagnosi o prescrizioni. In caso di emergenza chiama il **112**.  
Consulta sempre un medico per valutazioni cliniche personalizzate.

## Contribuire
Issue, PR e feedback benvenuti. Stile di codice: PHP 8.1+, WP Coding Standards, ESLint/Prettier per JS.

## Licenza
GPLv2 o successiva (compatibile con l’ecosistema WordPress).
