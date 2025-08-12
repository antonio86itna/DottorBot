function trapFocus(modal) {
  const focusable = modal.querySelectorAll('a[href], button:not([disabled]), textarea, input, select');
  const first = focusable[0];
  const last = focusable[focusable.length - 1];
  modal.addEventListener('keydown', e => {
    if (e.key === 'Tab') {
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

export function createDisclaimerModal() {
  const modal = document.createElement('div');
  modal.id = 'disclaimer-modal';
  modal.className = 'fixed inset-0 bg-black bg-opacity-70 flex items-center justify-center z-50 p-4';
  modal.setAttribute('role', 'dialog');
  modal.setAttribute('aria-modal', 'true');
  modal.innerHTML = `
    <div class="bg-slate-800 rounded-xl p-8 max-w-2xl w-full shadow-2xl border border-slate-700">
      <h2 class="text-2xl font-bold text-cyan-400 mb-4" id="disclaimer-title">Avviso Importante</h2>
      <div class="text-slate-300 space-y-4 text-sm" aria-describedby="disclaimer-title">
        <p>Benvenuto in DottorBot. Prima di procedere, è fondamentale che tu comprenda e accetti quanto segue:</p>
        <ul class="list-disc list-inside space-y-2 bg-slate-900 p-4 rounded-lg">
          <li><strong>NON È UN MEDICO:</strong> DottorBot è un'IA, non un professionista sanitario.</li>
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

export function createLoginModal() {
  const modal = document.createElement('div');
  modal.id = 'login-modal';
  modal.className = 'hidden fixed inset-0 bg-black bg-opacity-70 flex items-center justify-center z-40 p-4';
  modal.setAttribute('role', 'dialog');
  modal.setAttribute('aria-modal', 'true');
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
        <p class="text-xs text-slate-400 mt-4 text-center">Questo è un prototipo. Puoi inserire qualsiasi dato.</p>
      </form>
    </div>`;
  return modal;
}

export function createJournalModal() {
  const modal = document.createElement('div');
  modal.id = 'journal-modal';
  modal.className = 'hidden fixed inset-0 bg-black bg-opacity-70 flex items-center justify-center z-40 p-4';
  modal.setAttribute('role', 'dialog');
  modal.setAttribute('aria-modal', 'true');
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
              <span role="radio" aria-label="felice">😊</span>
              <span role="radio" aria-label="contento">🙂</span>
              <span role="radio" aria-label="neutro">😐</span>
              <span role="radio" aria-label="triste">😕</span>
              <span role="radio" aria-label="molto triste">😞</span>
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
