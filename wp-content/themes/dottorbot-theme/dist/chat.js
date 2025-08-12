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
  input.type = 'text';
  input.className = 'db-input';
  send.type = 'submit';
  send.textContent = 'Invia';

  form.appendChild(input);
  form.appendChild(send);
  root.appendChild(messages);
  root.appendChild(form);

  form.addEventListener('submit', async (e) => {
    e.preventDefault();
    const text = input.value.trim();
    if (!text) return;
    appendMessage('user', text);
    input.value = '';
    try {
      const res = await fetch('/wp-json/dottorbot/v1/chat', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
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
  }

  function showPaywall(url) {
    const overlay = document.createElement('div');
    overlay.className = 'db-paywall';
    overlay.innerHTML = '<div class="db-modal"><p>Limite gratuito raggiunto.</p><button id="db-upgrade">Upgrade</button></div>';
    document.body.appendChild(overlay);
    document.getElementById('db-upgrade').addEventListener('click', async () => {
      try {
        const res = await fetch(url, { method: 'POST' });
        const data = await res.json();
        if (data.url) {
          window.location.href = data.url;
        }
      } catch (err) {
        overlay.remove();
      }
    });
  }
});

