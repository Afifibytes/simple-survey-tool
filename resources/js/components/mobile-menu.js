/**
 * Mobile Menu Handler
 * Handles mobile navigation menu toggle functionality
 */

class MobileMenu {
    constructor() {
        this.menuButton = document.querySelector('.mobile-menu-button');
        this.mobileMenu = document.querySelector('.mobile-menu');
        this.isOpen = false;
        
        if (this.menuButton && this.mobileMenu) {
            this.init();
        }
    }
    
    init() {
        this.bindEvents();
        this.setupKeyboardNavigation();
    }
    
    bindEvents() {
        this.menuButton.addEventListener('click', this.toggle.bind(this));
        
        // Close menu when clicking outside
        document.addEventListener('click', (e) => {
            if (this.isOpen && !this.menuButton.contains(e.target) && !this.mobileMenu.contains(e.target)) {
                this.close();
            }
        });
        
        // Close menu on window resize if it becomes desktop size
        window.addEventListener('resize', () => {
            if (window.innerWidth >= 768 && this.isOpen) {
                this.close();
            }
        });
        
        // Close menu when pressing Escape key
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && this.isOpen) {
                this.close();
                this.menuButton.focus();
            }
        });
    }
    
    setupKeyboardNavigation() {
        const menuLinks = this.mobileMenu.querySelectorAll('a');
        
        menuLinks.forEach((link, index) => {
            link.addEventListener('keydown', (e) => {
                if (e.key === 'Tab') {
                    // If this is the last link and user is tabbing forward, close menu
                    if (index === menuLinks.length - 1 && !e.shiftKey) {
                        this.close();
                    }
                    // If this is the first link and user is shift-tabbing, close menu
                    if (index === 0 && e.shiftKey) {
                        this.close();
                        this.menuButton.focus();
                        e.preventDefault();
                    }
                }
            });
        });
    }
    
    toggle() {
        if (this.isOpen) {
            this.close();
        } else {
            this.open();
        }
    }
    
    open() {
        this.mobileMenu.classList.remove('hidden');
        this.menuButton.setAttribute('aria-expanded', 'true');
        this.isOpen = true;
        
        // Focus first menu item
        const firstLink = this.mobileMenu.querySelector('a');
        if (firstLink) {
            firstLink.focus();
        }
        
        // Update button icon to show close state
        this.updateButtonIcon();
    }
    
    close() {
        this.mobileMenu.classList.add('hidden');
        this.menuButton.setAttribute('aria-expanded', 'false');
        this.isOpen = false;
        
        // Update button icon to show menu state
        this.updateButtonIcon();
    }
    
    updateButtonIcon() {
        const svg = this.menuButton.querySelector('svg');
        if (!svg) return;
        
        if (this.isOpen) {
            // Show X icon
            svg.innerHTML = '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>';
        } else {
            // Show hamburger icon
            svg.innerHTML = '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>';
        }
    }
}

// Initialize when DOM is loaded
document.addEventListener('DOMContentLoaded', () => {
    new MobileMenu();
});
