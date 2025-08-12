const STORAGE_KEY = 'dottorbot-prefs';

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

export function getPreference(key) {
  const prefs = readPrefs();
  return prefs[key];
}

export function setPreference(key, value) {
  const prefs = readPrefs();
  prefs[key] = value;
  savePrefs(prefs);
}

export function clearPreferences() {
  localStorage.removeItem(STORAGE_KEY);
}
