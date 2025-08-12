(() => {
  // src/components/header.js
  function createHeader() {
    const header = document.createElement("header");
    header.className = "bg-slate-800/50 backdrop-blur-sm border-b border-slate-700 p-4 flex justify-between items-center shadow-lg sticky top-0 z-30";
    header.innerHTML = `
    <div class="flex items-center gap-3">
      <svg class="w-8 h-8 text-cyan-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M7.5 8.25h9m-9 3H12m-9.75 1.51c0 1.6 1.123 2.994 2.707 3.227 1.129.166 2.27.293 3.423.379.35.026.67.21.865.501L12 21l2.755-4.133a1.14 1.14 0 0 1 .865-.501 48.172 48.172 0 0 0 3.423-.379c1.584-.233 2.707-1.626 2.707-3.228V6.741c0-1.602-1.123-2.995-2.707-3.228A48.394 48.394 0 0 0 12 3c-2.392 0-4.744.175-7.043.513C3.373 3.746 2.25 5.14 2.25 6.741v6.018Z" /></svg>
      <h1 class="text-xl font-bold text-white">DottorBot</h1>
    </div>
    <div id="user-controls" class="hidden items-center gap-4">
      <button id="subscription-toggle-btn" aria-label="Abbonamento" title="Gestisci Abbonamento"><svg class="w-6 h-6 text-yellow-400 hover:text-yellow-300 transition" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M15.362 5.214A8.252 8.252 0 0 1 12 21 8.25 8.25 0 0 1 6.038 7.047 8.287 8.287 0 0 0 9 9.601a8.287 8.287 0 0 0 3.962-2.558 8.287 8.287 0 0 0-1.599-4.435Z" /><path stroke-linecap="round" stroke-linejoin="round" d="M12 6V21M6.038 7.048A8.25 8.25 0 0 0 9 9.601a8.287 8.287 0 0 0 3.962-2.558 8.287 8.287 0 0 0-1.599-4.435M15.362 5.214A8.25 8.25 0 0 1 18 9.601a8.287 8.287 0 0 1-3.962 2.558 8.287 8.287 0 0 1 1.599 4.435" /></svg></button>
      <button id="privacy-toggle-btn" aria-label="Privacy" title="Impostazioni Privacy"><svg class="w-6 h-6 text-slate-400 hover:text-white transition" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9.594 3.94c.09-.542.56-.94 1.11-.94h2.593c.55 0 1.02.398 1.11.94l.213 1.281c.063.374.313.686.645.87.074.04.147.083.22.127.325.196.72.257 1.075.124l1.217-.456a1.125 1.125 0 0 1 1.37.49l1.296 2.247a1.125 1.125 0 0 1-.26 1.431l-1.003.827c-.293.24-.438.613-.43.992a6.759 6.759 0 0 1 0 1.905c.008.379.137.75.43.99l1.005.828c.424.35.534.954.26 1.43l-1.298 2.247a1.125 1.125 0 0 1-1.369.491l-1.217-.456c-.355-.133-.75-.072-1.076.124a6.47 6.47 0 0 1-.22.128c-.333.183-.582.495-.644.869l-.213 1.28c-.09.543-.56.94-1.11.94h-2.594c-.55 0-1.02-.398-1.11-.94l-.213-1.281c-.063-.374-.313-.686-.645-.87a6.52 6.52 0 0 1-.22-.127c-.325-.196-.72-.257-1.075-.124l-1.217.456a1.125 1.125 0 0 1-1.37-.49l-1.296-2.247a1.125 1.125 0 0 1 .26-1.431l1.004-.827c.292-.24.437-.613.43-.992a6.759 6.759 0 0 1 0-1.905c-.008-.379-.137-.75-.43-.99l-1.004-.828a1.125 1.125 0 0 1-.26-1.43l1.297-2.247a1.125 1.125 0 0 1 1.37-.49l1.217.456c.355.133.75.072 1.076-.124.072-.044.146-.087.22-.128.332-.183.582-.495.644-.869l.214-1.281Z" /><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" /></svg></button>
      <button id="journal-toggle-btn" aria-label="Diario" title="Diario della Salute"><svg class="w-6 h-6 text-slate-400 hover:text-white transition" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M21 8.25c0-2.485-2.099-4.5-4.688-4.5-1.935 0-3.597 1.126-4.312 2.733-.715-1.607-2.377-2.733-4.313-2.733C5.1 3.75 3 5.765 3 8.25c0 7.22 9 12 9 12s9-4.78 9-12Z" /></svg></button>
      <button id="history-toggle-btn" aria-label="Cronologia" title="Cronologia Chat"><svg class="w-6 h-6 text-slate-400 hover:text-white transition" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" /></svg></button>
      <span id="username-display" class="font-medium"></span>
      <button id="logout-btn" class="bg-red-500 hover:bg-red-600 text-white font-bold py-2 px-4 rounded-lg">Esci</button>
    </div>
    <button id="login-register-btn" class="bg-cyan-500 hover:bg-cyan-600 text-white font-bold py-2 px-4 rounded-lg">Accedi / Registrati</button>
  `;
    return header;
  }

  // src/components/chat.js
  function createChatArea() {
    const container = document.createElement("div");
    container.className = "flex-1 flex flex-col";
    container.innerHTML = `
    <main id="chat-container" class="flex-1 p-4 md:p-6 overflow-y-auto chat-scrollbar" aria-live="polite">
      <div id="chat-messages" class="max-w-4xl mx-auto w-full flex flex-col gap-4" aria-label="Messaggi della chat"></div>
    </main>
    <footer class="bg-slate-800/50 backdrop-blur-sm border-t border-slate-700 p-4 sticky bottom-0 z-20">
      <div class="max-w-4xl mx-auto">
        <form id="chat-form" class="flex items-center gap-3">
          <button type="button" id="symptom-checker-btn" class="bg-slate-600 hover:bg-slate-500 text-white rounded-lg p-3 disabled:opacity-50" aria-label="Sintomi" disabled>
            <svg class="w-6 h-6" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" /></svg>
          </button>
          <input type="text" id="chat-input" placeholder="Accedi per iniziare a chattare..." class="flex-1 bg-slate-700 border border-slate-600 rounded-lg p-3 focus:ring-2 focus:ring-cyan-500 disabled:opacity-50" aria-label="Messaggio" disabled>
          <button type="submit" id="send-btn" class="bg-cyan-500 hover:bg-cyan-600 text-white rounded-lg p-3 disabled:bg-slate-600" aria-label="Invia" disabled>
            <svg class="w-6 h-6" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 12 3.269 3.125A59.769 59.769 0 0 1 21.485 12 59.768 59.768 0 0 1 3.27 20.875L5.999 12Zm0 0h7.5" /></svg>
          </button>
        </form>
        <p id="message-counter" class="text-xs text-slate-400 mt-2 text-center"></p>
        <p class="text-xs text-slate-500 mt-2 text-center"><strong>Disclaimer:</strong> DottorBot \xE8 un assistente AI e non sostituisce un parere medico.</p>
      </div>
    </footer>
  `;
    return container;
  }

  // src/components/modals.js
  function trapFocus(modal) {
    const focusable = modal.querySelectorAll("a[href], button:not([disabled]), textarea, input, select");
    const first = focusable[0];
    const last = focusable[focusable.length - 1];
    modal.addEventListener("keydown", (e) => {
      if (e.key === "Tab") {
        if (e.shiftKey && document.activeElement === first) {
          e.preventDefault();
          last.focus();
        } else if (!e.shiftKey && document.activeElement === last) {
          e.preventDefault();
          first.focus();
        }
      }
    });
    first && first.focus();
  }
  function createDisclaimerModal() {
    const modal = document.createElement("div");
    modal.id = "disclaimer-modal";
    modal.className = "fixed inset-0 bg-black bg-opacity-70 flex items-center justify-center z-50 p-4";
    modal.setAttribute("role", "dialog");
    modal.setAttribute("aria-modal", "true");
    modal.innerHTML = `
    <div class="bg-slate-800 rounded-xl p-8 max-w-2xl w-full shadow-2xl border border-slate-700">
      <h2 class="text-2xl font-bold text-cyan-400 mb-4" id="disclaimer-title">Avviso Importante</h2>
      <div class="text-slate-300 space-y-4 text-sm" aria-describedby="disclaimer-title">
        <p>Benvenuto in DottorBot. Prima di procedere, \xE8 fondamentale che tu comprenda e accetti quanto segue:</p>
        <ul class="list-disc list-inside space-y-2 bg-slate-900 p-4 rounded-lg">
          <li><strong>NON \xC8 UN MEDICO:</strong> DottorBot \xE8 un'IA, non un professionista sanitario.</li>
          <li><strong>SCOPO INFORMATIVO:</strong> Le informazioni sono a scopo puramente informativo.</li>
          <li><strong>NON SOSTITUISCE IL PARERE MEDICO:</strong> Consulta sempre un medico qualificato.</li>
          <li><strong>EMERGENZE:</strong> In caso di emergenza, chiama il 112.</li>
        </ul>
        <p>Cliccando su "Accetto e Continuo", dichiari di aver letto, compreso e accettato questi termini.</p>
      </div>
      <button id="accept-disclaimer-btn" class="mt-6 w-full bg-cyan-500 hover:bg-cyan-600 text-white font-bold py-3 px-4 rounded-lg text-lg">Accetto e Continuo</button>
    </div>`;
    trapFocus(modal);
    return modal;
  }
  function createLoginModal() {
    const modal = document.createElement("div");
    modal.id = "login-modal";
    modal.className = "hidden fixed inset-0 bg-black bg-opacity-70 flex items-center justify-center z-40 p-4";
    modal.setAttribute("role", "dialog");
    modal.setAttribute("aria-modal", "true");
    modal.innerHTML = `
    <div class="bg-slate-800 rounded-xl p-8 max-w-md w-full shadow-2xl border border-slate-700 relative">
      <button id="close-login-modal" class="absolute top-4 right-4 text-slate-400 hover:text-white text-2xl" aria-label="Chiudi">&times;</button>
      <h2 class="text-2xl font-bold text-center mb-6">Accedi a DottorBot</h2>
      <form id="login-form" class="space-y-4">
        <input type="text" id="login-username" placeholder="Nome utente" class="w-full bg-slate-700 border border-slate-600 rounded-lg p-3 focus:ring-2 focus:ring-cyan-500" required aria-label="Nome utente">
        <input type="password" id="login-password" placeholder="Password" class="w-full bg-slate-700 border border-slate-600 rounded-lg p-3 focus:ring-2 focus:ring-cyan-500" required aria-label="Password">
        <div class="flex items-center">
          <input type="checkbox" id="history-consent" class="h-4 w-4 bg-slate-700 border-slate-600 text-cyan-600 focus:ring-cyan-500 rounded" aria-label="Salva dati localmente">
          <label for="history-consent" class="ml-2 block text-sm text-slate-300">Acconsento a salvare i dati sul mio dispositivo.</label>
        </div>
        <button type="submit" class="mt-4 w-full bg-cyan-500 hover:bg-cyan-600 text-white font-bold py-3 px-4 rounded-lg">Accedi</button>
        <p class="text-xs text-slate-400 mt-4 text-center">Questo \xE8 un prototipo. Puoi inserire qualsiasi dato.</p>
      </form>
    </div>`;
    return modal;
  }
  function createJournalModal() {
    const modal = document.createElement("div");
    modal.id = "journal-modal";
    modal.className = "hidden fixed inset-0 bg-black bg-opacity-70 flex items-center justify-center z-40 p-4";
    modal.setAttribute("role", "dialog");
    modal.setAttribute("aria-modal", "true");
    modal.innerHTML = `
    <div class="bg-slate-800 rounded-xl p-8 max-w-2xl w-full shadow-2xl border border-slate-700 flex flex-col max-h-[90vh]">
      <div class="flex justify-between items-center mb-4">
        <h2 class="text-2xl font-bold text-cyan-400">Diario della Salute</h2>
        <button id="close-journal-modal" class="text-slate-400 hover:text-white text-2xl" aria-label="Chiudi">&times;</button>
      </div>
      <div class="flex-1 flex flex-col md:flex-row gap-6 overflow-hidden">
        <div class="w-full md:w-1/2 flex flex-col">
          <h3 class="font-semibold mb-3">Nuova Voce del Diario</h3>
          <form id="journal-form" class="flex flex-col gap-4">
            <p>Come ti senti oggi?</p>
            <div id="mood-selector" class="flex gap-3 text-2xl cursor-pointer" aria-label="Seleziona umore">
              <span role="radio" aria-label="felice">\u{1F60A}</span>
              <span role="radio" aria-label="contento">\u{1F642}</span>
              <span role="radio" aria-label="neutro">\u{1F610}</span>
              <span role="radio" aria-label="triste">\u{1F615}</span>
              <span role="radio" aria-label="molto triste">\u{1F61E}</span>
            </div>
            <input type="hidden" id="selected-mood">
            <textarea id="journal-notes" rows="4" class="w-full bg-slate-700 border border-slate-600 rounded-lg p-3 focus:ring-2 focus:ring-cyan-500" placeholder="Aggiungi note su sintomi, alimentazione..." aria-label="Note"></textarea>
            <button type="submit" class="bg-cyan-500 hover:bg-cyan-600 text-white font-bold py-2 px-4 rounded-lg">Salva Voce</button>
          </form>
        </div>
        <div class="w-full md:w-1/2 flex flex-col">
          <div class="flex justify-between items-center mb-3">
            <h3 class="font-semibold">Le tue Voci</h3>
          </div>
          <div id="journal-entries" class="flex-1 overflow-y-auto chat-scrollbar bg-slate-900 p-2 rounded-lg space-y-3"></div>
        </div>
      </div>
    </div>`;
    return modal;
  }

  // src/preferences.js
  var STORAGE_KEY = "dottorbot-prefs";
  function readPrefs() {
    try {
      return JSON.parse(localStorage.getItem(STORAGE_KEY)) || {};
    } catch {
      return {};
    }
  }
  function savePrefs(prefs) {
    localStorage.setItem(STORAGE_KEY, JSON.stringify(prefs));
  }
  function getPreference(key) {
    const prefs = readPrefs();
    return prefs[key];
  }
  function setPreference(key, value) {
    const prefs = readPrefs();
    prefs[key] = value;
    savePrefs(prefs);
  }
  function clearPreferences() {
    localStorage.removeItem(STORAGE_KEY);
  }

  // src/main.js
  function show(element) {
    element.classList.remove("hidden");
    element.setAttribute("aria-hidden", "false");
  }
  function hide(element) {
    element.classList.add("hidden");
    element.setAttribute("aria-hidden", "true");
  }
  function init() {
    const app = document.getElementById("app");
    const header = createHeader();
    const chat = createChatArea();
    const disclaimerModal = createDisclaimerModal();
    const loginModal = createLoginModal();
    const journalModal = createJournalModal();
    app.appendChild(header);
    app.appendChild(chat);
    document.body.appendChild(disclaimerModal);
    document.body.appendChild(loginModal);
    document.body.appendChild(journalModal);
    const loginBtn = header.querySelector("#login-register-btn");
    const userControls = header.querySelector("#user-controls");
    const logoutBtn = header.querySelector("#logout-btn");
    const usernameDisplay = header.querySelector("#username-display");
    const journalBtn = header.querySelector("#journal-toggle-btn");
    const chatInput = chat.querySelector("#chat-input");
    const sendBtn = chat.querySelector("#send-btn");
    if (getPreference("disclaimerAccepted")) {
      hide(disclaimerModal);
    } else {
      show(disclaimerModal);
      disclaimerModal.querySelector("#accept-disclaimer-btn").addEventListener("click", () => {
        setPreference("disclaimerAccepted", true);
        hide(disclaimerModal);
        loginBtn.focus();
      });
    }
    function openLogin() {
      show(loginModal);
      loginModal.querySelector("#login-username").focus();
    }
    function closeLogin() {
      hide(loginModal);
    }
    loginBtn.addEventListener("click", openLogin);
    loginModal.querySelector("#close-login-modal").addEventListener("click", closeLogin);
    loginModal.querySelector("#login-form").addEventListener("submit", (e) => {
      e.preventDefault();
      const username = loginModal.querySelector("#login-username").value.trim();
      setPreference("username", username);
      if (!getPreference("disclaimerAccepted")) {
        setPreference("disclaimerAccepted", true);
      }
      usernameDisplay.textContent = username;
      hide(loginBtn);
      show(userControls);
      closeLogin();
      chatInput.disabled = false;
      sendBtn.disabled = false;
      chatInput.focus();
    });
    logoutBtn.addEventListener("click", () => {
      clearPreferences();
      show(loginBtn);
      hide(userControls);
      chatInput.disabled = true;
      sendBtn.disabled = true;
      chatInput.value = "";
      loginBtn.focus();
    });
    journalBtn.addEventListener("click", () => {
      show(journalModal);
      journalModal.querySelector("#journal-notes").focus();
      loadJournal();
    });
    journalModal.querySelector("#close-journal-modal").addEventListener("click", () => hide(journalModal));
    journalModal.querySelector("#journal-form").addEventListener("submit", (e) => {
      e.preventDefault();
      const mood = journalModal.querySelector("#selected-mood").value;
      const notes = journalModal.querySelector("#journal-notes").value.trim();
      const entries = getPreference("journalEntries") || [];
      entries.push({ mood, notes, date: (/* @__PURE__ */ new Date()).toISOString() });
      setPreference("journalEntries", entries);
      journalModal.querySelector("#journal-notes").value = "";
      loadJournal();
    });
    journalModal.querySelector("#mood-selector").addEventListener("click", (e) => {
      if (e.target.matches("span")) {
        journalModal.querySelector("#selected-mood").value = e.target.textContent;
      }
    });
    function loadJournal() {
      const entries = getPreference("journalEntries") || [];
      const list = journalModal.querySelector("#journal-entries");
      list.innerHTML = "";
      entries.forEach((entry) => {
        const item = document.createElement("div");
        item.className = "p-2 rounded bg-slate-700";
        item.textContent = `${entry.date.substring(0, 10)} ${entry.mood} - ${entry.notes}`;
        list.appendChild(item);
      });
    }
  }
  document.addEventListener("DOMContentLoaded", init);
})();
