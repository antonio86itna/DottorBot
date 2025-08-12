document.addEventListener('DOMContentLoaded', function () {
  const root = document.getElementById('dottorbot-diary');
  if (!root) return;

  const canvas = document.createElement('canvas');
  root.appendChild(canvas);

  const btnJson = document.createElement('button');
  btnJson.textContent = 'Export JSON';
  const btnCsv = document.createElement('button');
  btnCsv.textContent = 'Export CSV';
  const btnPdf = document.createElement('button');
  btnPdf.textContent = 'Export PDF';
  const btnContainer = document.createElement('div');
  btnContainer.appendChild(btnJson);
  btnContainer.appendChild(btnCsv);
  btnContainer.appendChild(btnPdf);
  root.appendChild(btnContainer);

  fetch('/wp-json/dottorbot/v1/diary')
    .then(r => r.json())
    .then(entries => {
      if (!Array.isArray(entries)) return;
      const labels = entries.map(e => new Date(e.timestamp * 1000).toLocaleDateString());
      const mood = entries.map(e => e.mood);
      const symptoms = entries.map(e => e.symptoms);

      new Chart(canvas.getContext('2d'), {
        type: 'line',
        data: {
          labels,
          datasets: [
            { label: 'Umore', data: mood, borderColor: 'blue', fill: false },
            { label: 'Sintomi', data: symptoms, borderColor: 'red', fill: false }
          ]
        }
      });

      btnJson.addEventListener('click', () => {
        const blob = new Blob([JSON.stringify(entries, null, 2)], { type: 'application/json' });
        const a = document.createElement('a');
        a.href = URL.createObjectURL(blob);
        a.download = 'diary.json';
        a.click();
      });

      btnCsv.addEventListener('click', () => {
        const header = 'id,timestamp,mood,symptoms,notes\n';
        const rows = entries
          .map(e => [e.id, e.timestamp, e.mood, e.symptoms, '"' + (e.notes || '').replace(/"/g, '""') + '"'].join(','))
          .join('\n');
        const blob = new Blob([header + rows], { type: 'text/csv' });
        const a = document.createElement('a');
        a.href = URL.createObjectURL(blob);
        a.download = 'diary.csv';
        a.click();
      });

      btnPdf.addEventListener('click', () => {
        const { jsPDF } = window.jspdf;
        const doc = new jsPDF();
        doc.text('Diary Entries', 10, 10);
        let y = 20;
        entries.forEach(e => {
          const line = new Date(e.timestamp * 1000).toLocaleDateString() + ' Mood:' + e.mood + ' Symptoms:' + e.symptoms;
          doc.text(line, 10, y);
          y += 10;
        });
        doc.save('diary.pdf');
      });
    });
});
