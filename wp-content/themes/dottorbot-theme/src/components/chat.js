export function createChatArea() {
  const container = document.createElement('div');
  container.className = 'flex-1 flex flex-col';
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
        <p class="text-xs text-slate-500 mt-2 text-center"><strong>Disclaimer:</strong> DottorBot è un assistente AI e non sostituisce un parere medico.</p>
      </div>
    </footer>
  `;
  return container;
}
