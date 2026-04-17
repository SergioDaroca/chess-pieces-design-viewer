// ============================================
// NotificationCenter - Cocoa-style event system
// ============================================

const NotificationCenter = {
    _observers: new Map(),
    
    // Add observer for a specific notification name
    addObserver(observer, selector, name) {
        if (!this._observers.has(name)) {
            this._observers.set(name, []);
        }
        this._observers.get(name).push({ observer, selector });
    },
    
    // Remove observer
    removeObserver(observer, name = null) {
        if (name) {
            const observers = this._observers.get(name);
            if (observers) {
                this._observers.set(name, observers.filter(obs => obs.observer !== observer));
            }
        } else {
            for (const [key, observers] of this._observers) {
                this._observers.set(key, observers.filter(obs => obs.observer !== observer));
            }
        }
    },
    
    // Post a notification
    postNotification(name, userInfo = {}) {
        const observers = this._observers.get(name);
        if (observers) {
            const notification = { name, userInfo };
            observers.forEach(({ observer, selector }) => {
                if (typeof observer[selector] === 'function') {
                    observer[selector].call(observer, notification);
                } else if (typeof selector === 'function') {
                    selector(notification);
                }
            });
        }
    },
    
    // Convenience: observe with callback
    observe(name, callback) {
        this.addObserver(this, callback, name);
    }
};

// Make global
window.NotificationCenter = NotificationCenter;