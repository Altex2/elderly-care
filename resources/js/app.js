import './bootstrap';
import '../css/app.css';

import Alpine from 'alpinejs';

window.Alpine = Alpine;
Alpine.start();

// Browser Notifications Handler
document.addEventListener('DOMContentLoaded', function() {
    // Request notification permission when the page loads
    if ('Notification' in window) {
        if (Notification.permission !== 'granted' && Notification.permission !== 'denied') {
            Notification.requestPermission().then(function(permission) {
                if (permission === 'granted') {
                    console.log('Notification permission granted');
                }
            });
        }
    }
});

// Function to display browser notification
window.showBrowserNotification = function(title, options) {
    if ('Notification' in window) {
        if (Notification.permission === 'granted') {
            const notification = new Notification(title, options);
            
            // Handle notification click
            notification.onclick = function() {
                window.focus();
                if (options.url) {
                    window.location.href = options.url;
                }
                notification.close();
            };
            
            // Auto close after 10 seconds
            setTimeout(() => {
                notification.close();
            }, 10000);
            
            return notification;
        } else if (Notification.permission !== 'denied') {
            Notification.requestPermission().then(function(permission) {
                if (permission === 'granted') {
                    const notification = new Notification(title, options);
                    
                    notification.onclick = function() {
                        window.focus();
                        if (options.url) {
                            window.location.href = options.url;
                        }
                        notification.close();
                    };
                    
                    setTimeout(() => {
                        notification.close();
                    }, 10000);
                    
                    return notification;
                }
            });
        }
    }
};

// Listen for Laravel Echo broadcasts if Echo is configured
if (typeof window.Echo !== 'undefined') {
    if (window.userId) {
        window.Echo.private(`App.Models.User.${window.userId}`)
            .notification((notification) => {
                if (notification.type === 'App\\Notifications\\MissedReminderNotification') {
                    // Show browser notification
                    window.showBrowserNotification('Reminder Buddy', {
                        body: notification.data.message,
                        icon: '/images/logo.png',
                        url: '/caregiver/dashboard'
                    });
                }
            });
    }
}
