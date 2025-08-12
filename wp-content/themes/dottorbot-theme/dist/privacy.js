// DottorBot privacy modal for data export and purge

document.addEventListener('DOMContentLoaded', () => {
  const trigger = document.getElementById('dottorbot-privacy-open');
  if (!trigger) {
    return;
  }

  trigger.addEventListener('click', () => {
    const prevFocus = document.activeElement;
    const overlay = document.createElement('div');
    overlay.className = 'db-paywall';
    overlay.innerHTML = '<div class="db-modal" role="dialog" aria-modal="true" aria-labelledby="db-privacy-text"><p id="db-privacy-text">Gestione privacy</p><button id="db-export-json">Export JSON</button><button id="db-export-csv">Export CSV</button><button id="db-purge">Purge</button><button id="db-privacy-close" aria-label="Chiudi">Chiudi</button></div>';
    document.body.appendChild(overlay);

    const focusable = [
      document.getElementById('db-export-json'),
      document.getElementById('db-export-csv'),
      document.getElementById('db-purge'),
      document.getElementById('db-privacy-close')
    ];
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

    async function exportData(format) {
      try {
        const res = await fetch('/wp-json/dottorbot/v1/export?format=' + format, { credentials: 'include' });
        const text = await res.text();
        const blob = new Blob([text], { type: format === 'csv' ? 'text/csv' : 'application/json' });
        const url = URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = 'dottorbot.' + format;
        document.body.appendChild(a);
        a.click();
        a.remove();
        URL.revokeObjectURL(url);
      } catch (e) {
        // ignore errors
      }
    }

    document.getElementById('db-export-json').addEventListener('click', () => exportData('json'));
    document.getElementById('db-export-csv').addEventListener('click', () => exportData('csv'));
    document.getElementById('db-purge').addEventListener('click', async () => {
      if (!confirm('Eliminare tutti i dati?')) return;
      try {
        await fetch('/wp-json/dottorbot/v1/purge', { method: 'DELETE', credentials: 'include' });
      } catch (e) {
        // ignore errors
      } finally {
        close();
      }
    });
    document.getElementById('db-privacy-close').addEventListener('click', close);
  });
});

