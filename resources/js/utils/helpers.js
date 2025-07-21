/**
 * Helper Utility Functions
 * Common utility functions used throughout the application
 */

// DOM Utilities
export const dom = {
    /**
     * Get element by ID
     */
    id(id) {
        return document.getElementById(id);
    },
    
    /**
     * Query selector
     */
    qs(selector, context = document) {
        return context.querySelector(selector);
    },
    
    /**
     * Query selector all
     */
    qsa(selector, context = document) {
        return Array.from(context.querySelectorAll(selector));
    },
    
    /**
     * Create element with attributes and content
     */
    create(tag, attributes = {}, content = '') {
        const element = document.createElement(tag);
        
        Object.entries(attributes).forEach(([key, value]) => {
            if (key === 'className') {
                element.className = value;
            } else if (key === 'dataset') {
                Object.entries(value).forEach(([dataKey, dataValue]) => {
                    element.dataset[dataKey] = dataValue;
                });
            } else {
                element.setAttribute(key, value);
            }
        });
        
        if (content) {
            element.innerHTML = content;
        }
        
        return element;
    },
    
    /**
     * Remove element from DOM
     */
    remove(element) {
        if (element && element.parentNode) {
            element.parentNode.removeChild(element);
        }
    },
    
    /**
     * Add event listener with optional delegation
     */
    on(element, event, handler, delegate = null) {
        if (delegate) {
            element.addEventListener(event, (e) => {
                if (e.target.matches(delegate)) {
                    handler.call(e.target, e);
                }
            });
        } else {
            element.addEventListener(event, handler);
        }
    }
};

// String Utilities
export const str = {
    /**
     * Capitalize first letter
     */
    capitalize(string) {
        return string.charAt(0).toUpperCase() + string.slice(1);
    },
    
    /**
     * Convert to kebab-case
     */
    kebab(string) {
        return string.replace(/([a-z])([A-Z])/g, '$1-$2').toLowerCase();
    },
    
    /**
     * Convert to camelCase
     */
    camel(string) {
        return string.replace(/-([a-z])/g, (match, letter) => letter.toUpperCase());
    },
    
    /**
     * Truncate string with ellipsis
     */
    truncate(string, length = 100, suffix = '...') {
        if (string.length <= length) return string;
        return string.substring(0, length) + suffix;
    },
    
    /**
     * Escape HTML characters
     */
    escapeHtml(string) {
        const div = document.createElement('div');
        div.textContent = string;
        return div.innerHTML;
    },
    
    /**
     * Generate random string
     */
    random(length = 8) {
        const chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';
        let result = '';
        for (let i = 0; i < length; i++) {
            result += chars.charAt(Math.floor(Math.random() * chars.length));
        }
        return result;
    }
};

// Array Utilities
export const arr = {
    /**
     * Remove item from array
     */
    remove(array, item) {
        const index = array.indexOf(item);
        if (index > -1) {
            array.splice(index, 1);
        }
        return array;
    },
    
    /**
     * Get unique values from array
     */
    unique(array) {
        return [...new Set(array)];
    },
    
    /**
     * Chunk array into smaller arrays
     */
    chunk(array, size) {
        const chunks = [];
        for (let i = 0; i < array.length; i += size) {
            chunks.push(array.slice(i, i + size));
        }
        return chunks;
    },
    
    /**
     * Shuffle array
     */
    shuffle(array) {
        const shuffled = [...array];
        for (let i = shuffled.length - 1; i > 0; i--) {
            const j = Math.floor(Math.random() * (i + 1));
            [shuffled[i], shuffled[j]] = [shuffled[j], shuffled[i]];
        }
        return shuffled;
    }
};

// Validation Utilities
export const validate = {
    /**
     * Check if email is valid
     */
    email(email) {
        const regex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return regex.test(email);
    },
    
    /**
     * Check if string is not empty
     */
    required(value) {
        return value !== null && value !== undefined && value.toString().trim() !== '';
    },
    
    /**
     * Check minimum length
     */
    minLength(value, min) {
        return value && value.toString().length >= min;
    },
    
    /**
     * Check maximum length
     */
    maxLength(value, max) {
        return !value || value.toString().length <= max;
    },
    
    /**
     * Check if value is numeric
     */
    numeric(value) {
        return !isNaN(value) && !isNaN(parseFloat(value));
    },
    
    /**
     * Check if value is within range
     */
    range(value, min, max) {
        const num = parseFloat(value);
        return !isNaN(num) && num >= min && num <= max;
    }
};

// Storage Utilities
export const storage = {
    /**
     * Set localStorage item with JSON serialization
     */
    set(key, value) {
        try {
            localStorage.setItem(key, JSON.stringify(value));
            return true;
        } catch (e) {
            console.warn('Failed to save to localStorage:', e);
            return false;
        }
    },
    
    /**
     * Get localStorage item with JSON parsing
     */
    get(key, defaultValue = null) {
        try {
            const item = localStorage.getItem(key);
            return item ? JSON.parse(item) : defaultValue;
        } catch (e) {
            console.warn('Failed to parse localStorage item:', e);
            return defaultValue;
        }
    },
    
    /**
     * Remove localStorage item
     */
    remove(key) {
        try {
            localStorage.removeItem(key);
            return true;
        } catch (e) {
            console.warn('Failed to remove localStorage item:', e);
            return false;
        }
    },
    
    /**
     * Clear all localStorage
     */
    clear() {
        try {
            localStorage.clear();
            return true;
        } catch (e) {
            console.warn('Failed to clear localStorage:', e);
            return false;
        }
    }
};

// Date Utilities
export const date = {
    /**
     * Format date to readable string
     */
    format(date, options = {}) {
        const defaults = {
            year: 'numeric',
            month: 'short',
            day: 'numeric'
        };
        
        return new Intl.DateTimeFormat('en-US', { ...defaults, ...options }).format(new Date(date));
    },
    
    /**
     * Get relative time string (e.g., "2 hours ago")
     */
    relative(date) {
        const now = new Date();
        const target = new Date(date);
        const diffInSeconds = Math.floor((now - target) / 1000);
        
        const intervals = [
            { label: 'year', seconds: 31536000 },
            { label: 'month', seconds: 2592000 },
            { label: 'day', seconds: 86400 },
            { label: 'hour', seconds: 3600 },
            { label: 'minute', seconds: 60 },
            { label: 'second', seconds: 1 }
        ];
        
        for (const interval of intervals) {
            const count = Math.floor(diffInSeconds / interval.seconds);
            if (count >= 1) {
                return `${count} ${interval.label}${count > 1 ? 's' : ''} ago`;
            }
        }
        
        return 'just now';
    }
};

// Debounce function
export function debounce(func, wait, immediate = false) {
    let timeout;
    return function executedFunction(...args) {
        const later = () => {
            timeout = null;
            if (!immediate) func(...args);
        };
        const callNow = immediate && !timeout;
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
        if (callNow) func(...args);
    };
}

// Throttle function
export function throttle(func, limit) {
    let inThrottle;
    return function(...args) {
        if (!inThrottle) {
            func.apply(this, args);
            inThrottle = true;
            setTimeout(() => inThrottle = false, limit);
        }
    };
}

// Make utilities available globally
window.utils = {
    dom,
    str,
    arr,
    validate,
    storage,
    date,
    debounce,
    throttle
};
