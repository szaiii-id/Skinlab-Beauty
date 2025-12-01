importScripts('https://www.gstatic.com/firebasejs/9.0.0/firebase-app-compat.js');
importScripts('https://www.gstatic.com/firebasejs/9.0.0/firebase-messaging-compat.js');

// --- INI HASIL COPY DARI GAMBAR ANDA ---
const firebaseConfig = {
  apiKey: "AIzaSyDOt6hojiJAM6TmTNHTGeeWjqiQVuL2sfk",
  authDomain: "skinlab-beauty.firebaseapp.com",
  projectId: "skinlab-beauty",
  storageBucket: "skinlab-beauty.firebasestorage.app",
  messagingSenderId: "950871228878",
  appId: "1:950871228878:web:cfbf8a36d4492a57936b9f"
};
// ----------------------------------------

firebase.initializeApp(firebaseConfig);
const messaging = firebase.messaging();

messaging.onBackgroundMessage((payload) => {
  console.log('[firebase-messaging-sw.js] Received background message ', payload);
  const title = payload.notification.title;
  const options = {
    body: payload.notification.body,
    icon: '/favicon.ico' // Ganti icon jika ada
  };
  self.registration.showNotification(title, options);
});