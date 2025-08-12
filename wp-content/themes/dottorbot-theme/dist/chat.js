// DottorBot chat UI with paywall modal

document.addEventListener('DOMContentLoaded', () => {
  const root = document.getElementById('dottorbot-chat');
  if (!root) {
    return;
  }

  const messages = document.createElement('div');
  const form = document.createElement('form');
  const input = document.createElement('input');
  const send = document.createElement('button');

  messages.className = 'db-messages';
  messages.setAttribute('role', 'log');
  messages.setAttribute('aria-live', 'polite');
  messages.setAttribute('aria-label', 'Conversazione');
  messages.tabIndex = 0;

  form.className = 'db-form';

  input.type = 'text';
  input.className = 'db-input';
  input.setAttribute('aria-label', 'Scrivi il tuo messaggio');

  send.type = 'submit';
  send.className = 'db-send';
  send.setAttribute('aria-label', 'Invia messaggio');
  send.textContent = 'Invia';

  form.appendChild(input);
  form.appendChild(send);
  root.appendChild(messages);
  root.appendChild(form);

  const reduceMotionQuery = window.matchMedia('(prefers-reduced-motion: reduce)');
  if (reduceMotionQuery.matches) {
    document.documentElement.classList.add('reduced-motion');
  }
  reduceMotionQuery.addEventListener('change', (e) => {
    document.documentElement.classList.toggle('reduced-motion', e.matches);
  });

  const history = JSON.parse(localStorage.getItem('dottorbotHistory') || '[]');
  history.forEach(msg => appendMessage(msg.role, msg.text));

  form.addEventListener('submit', async (e) => {
    e.preventDefault();
    const text = input.value.trim();
    if (!text) return;
    appendMessage('user', text);
    input.value = '';
    try {
      const res = await fetch('/wp-json/dottorbot/v1/chat', {
        method: 'POST',
        credentials: 'same-origin',
        headers: { 'Content-Type': 'application/json', 'X-WP-Nonce': dottorbotChat.nonce },
        body: JSON.stringify({ message: text })
      });
      const data = await res.json();
      if (data.paywall) {
        showPaywall(data.upgrade_url);
      } else {
        appendMessage('bot', data.message || '');
      }
    } catch (err) {
      appendMessage('bot', 'Errore di rete');
    }
  });

  function appendMessage(role, text) {
    const p = document.createElement('p');
    p.className = 'db-' + role;
    p.textContent = text;
    messages.appendChild(p);
    messages.scrollTop = messages.scrollHeight;
    history.push({ role, text });
    localStorage.setItem('dottorbotHistory', JSON.stringify(history));
  }

  function showPaywall(url) {
    const prevFocus = document.activeElement;
    const overlay = document.createElement('div');
    overlay.className = 'db-paywall';
    overlay.innerHTML = '<div class="db-modal" role="dialog" aria-modal="true" aria-labelledby="db-paywall-text"><p id="db-paywall-text">Limite gratuito raggiunto.</p><button id="db-upgrade">Upgrade</button><button id="db-close" aria-label="Chiudi">Chiudi</button></div>';
    document.body.appendChild(overlay);
    const focusable = [document.getElementById('db-upgrade'), document.getElementById('db-close')];
    let idx = 0;
    focusable[0].focus();

    function trap(e) {
      if (e.key === 'Tab') {
        e.preventDefault();
        idx = e.shiftKey ? (idx + focusable.length - 1) % focusable.length : (idx + 1) % focusable.length;
        focusable[idx].focus();
      } else if (e.key === 'Escape') {
        close();
      }
    }

    function close() {
      document.removeEventListener('keydown', trap);
      overlay.remove();
      if (prevFocus) prevFocus.focus();
    }

    document.addEventListener('keydown', trap);

    document.getElementById('db-upgrade').addEventListener('click', async () => {
      try {
        const res = await fetch(url, { method: 'POST', credentials: 'same-origin', headers: { 'X-WP-Nonce': dottorbotChat.nonce } });
        const data = await res.json();
        if (data.url) {
          window.location.href = data.url;
        }
      } catch (err) {
        close();
      }
    });

    document.getElementById('db-close').addEventListener('click', close);
  }
});

