import { initializeApp } from "firebase/app";
import { getMessaging, getToken, onMessage } from "firebase/messaging";
import axios from 'axios';

// --- CONFIG ---
// (Pastikan ini sesuai dengan config project Anda)
const firebaseConfig = {
    apiKey: "AIzaSyDOt6hojiJAM6TmTNHTGeeWjqiQVuL2sfk",
    authDomain: "skinlab-beauty.firebaseapp.com",
    projectId: "skinlab-beauty",
    storageBucket: "skinlab-beauty.firebasestorage.app",
    messagingSenderId: "950871228878",
    appId: "1:950871228878:web:cfbf8a36d4492a57936b9f"
};

const app = initializeApp(firebaseConfig);
const messaging = getMessaging(app);

// 1. Request Permission & Save Token
export const requestPermission = async () => {
    try {
        const permission = await Notification.requestPermission();
        if (permission === 'granted') {
            const token = await getToken(messaging, {
                // VAPID Key Anda
                vapidKey: 'BCSllDqGEH1mL5iQEVNauB1kGbmIEKFCgq3Ixlxz1Q7QodxGEvUNkzN-Y-RupG4LNo2NLW66D9dXQGLbFG3NDdA'
            });
            
            if (token) {
                // Kirim ke backend
                await axios.post('/api/fcm-token', { token });
                console.log("FCM Token Updated.");
            }
        }
    } catch (error) {
        console.error("FCM Permission Error:", error);
    }
}

// 2. LISTENER FOREGROUND (Saat Tab Dibuka)
// Fungsi ini menerima 'callback' agar UI bisa bereaksi
export const listenForMessages = (callback) => {
    onMessage(messaging, (payload) => {
        console.log("Pesan Foreground diterima:", payload);
        
        // Panggil callback yang dikirim dari Vue Component
        // Kita kirim Title & Body
        if (callback) {
            callback({
                title: payload.notification.title,
                body: payload.notification.body,
                image: payload.notification.image || '/favicon.ico'
            });
        }

        // Opsional: Tetap coba trigger notifikasi sistem juga
        // (Siapa tahu user pindah tab pas notif masuk)
        new Notification(payload.notification.title, {
            body: payload.notification.body,
            icon: '/favicon.ico'
        });
    });
};