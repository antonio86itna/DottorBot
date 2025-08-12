import { createHeader } from './components/header.js';
import { createChatArea } from './components/chat.js';
import { createDisclaimerModal, createLoginModal, createJournalModal } from './components/modals.js';
import { getPreference, setPreference, clearPreferences } from './preferences.js';

function show(element) {
  element.classList.remove('hidden');
  element.setAttribute('aria-hidden', 'false');
}
function hide(element) {
  element.classList.add('hidden');
  element.setAttribute('aria-hidden', 'true');
}

function init() {
  const app = document.getElementById('app');
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

  const loginBtn = header.querySelector('#login-register-btn');
  const userControls = header.querySelector('#user-controls');
  const logoutBtn = header.querySelector('#logout-btn');
  const usernameDisplay = header.querySelector('#username-display');
  const journalBtn = header.querySelector('#journal-toggle-btn');

  const chatInput = chat.querySelector('#chat-input');
  const sendBtn = chat.querySelector('#send-btn');

  // Disclaimer acceptance
  if (getPreference('disclaimerAccepted')) {
    hide(disclaimerModal);
  } else {
    show(disclaimerModal);
    disclaimerModal.querySelector('#accept-disclaimer-btn').addEventListener('click', () => {
      setPreference('disclaimerAccepted', true);
      hide(disclaimerModal);
      loginBtn.focus();
    });
  }

  // Login modal
  function openLogin() {
    show(loginModal);
    loginModal.querySelector('#login-username').focus();
  }
  function closeLogin() {
    hide(loginModal);
  }
  loginBtn.addEventListener('click', openLogin);
  loginModal.querySelector('#close-login-modal').addEventListener('click', closeLogin);

  loginModal.querySelector('#login-form').addEventListener('submit', e => {
    e.preventDefault();
    const username = loginModal.querySelector('#login-username').value.trim();
    setPreference('username', username);
    if (!getPreference('disclaimerAccepted')) {
      setPreference('disclaimerAccepted', true);
    }
    usernameDisplay.textContent = username;
    hide(loginBtn);
    show(userControls);
    closeLogin();
    chatInput.disabled = false;
    sendBtn.disabled = false;
    chatInput.focus();
  });

  logoutBtn.addEventListener('click', () => {
    clearPreferences();
    show(loginBtn);
    hide(userControls);
    chatInput.disabled = true;
    sendBtn.disabled = true;
    chatInput.value = '';
    loginBtn.focus();
  });

  // Journal modal
  journalBtn.addEventListener('click', () => {
    show(journalModal);
    journalModal.querySelector('#journal-notes').focus();
    loadJournal();
  });
  journalModal.querySelector('#close-journal-modal').addEventListener('click', () => hide(journalModal));

  journalModal.querySelector('#journal-form').addEventListener('submit', e => {
    e.preventDefault();
    const mood = journalModal.querySelector('#selected-mood').value;
    const notes = journalModal.querySelector('#journal-notes').value.trim();
    const entries = getPreference('journalEntries') || [];
    entries.push({ mood, notes, date: new Date().toISOString() });
    setPreference('journalEntries', entries);
    journalModal.querySelector('#journal-notes').value = '';
    loadJournal();
  });

  journalModal.querySelector('#mood-selector').addEventListener('click', e => {
    if (e.target.matches('span')) {
      journalModal.querySelector('#selected-mood').value = e.target.textContent;
    }
  });

  function loadJournal() {
    const entries = getPreference('journalEntries') || [];
    const list = journalModal.querySelector('#journal-entries');
    list.innerHTML = '';
    entries.forEach(entry => {
      const item = document.createElement('div');
      item.className = 'p-2 rounded bg-slate-700';
      item.textContent = `${entry.date.substring(0,10)} ${entry.mood} - ${entry.notes}`;
      list.appendChild(item);
    });
  }
}

document.addEventListener('DOMContentLoaded', init);
