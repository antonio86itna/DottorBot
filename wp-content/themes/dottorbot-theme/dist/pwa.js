document.addEventListener('DOMContentLoaded', () => {
  if ('serviceWorker' in navigator) {
    navigator.serviceWorker.register(dottorbotPwa.swUrl)
      .then(async reg => {
        if ('PushManager' in window) {
          const perm = await Notification.requestPermission();
          if (perm === 'granted') {
            const key = urlBase64ToUint8Array(dottorbotPwa.vapidPublicKey);
            const sub = await reg.pushManager.subscribe({
              userVisibleOnly: true,
              applicationServerKey: key
            });
            await fetch(dottorbotPwa.restUrl, {
              method: 'POST',
              headers: { 'Content-Type': 'application/json' },
              body: JSON.stringify(sub)
            });
          }
        }
      })
      .catch(err => console.error('SW registration failed', err));
  }
});

function urlBase64ToUint8Array(base64String) {
  const padding = '='.repeat((4 - base64String.length % 4) % 4);
  const base64 = (base64String + padding).replace(/-/g, '+').replace(/_/g, '/');
  const rawData = window.atob(base64);
  const outputArray = new Uint8Array(rawData.length);
  for (let i = 0; i < rawData.length; ++i) {
    outputArray[i] = rawData.charCodeAt(i);
  }
  return outputArray;
}
