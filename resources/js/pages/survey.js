/**
 * Survey Page Functionality
 * Handles public survey page interactions and user experience enhancements
 */

class SurveyPage {
    constructor() {
        this.surveyContainer = document.querySelector('.survey-container');
        if (this.surveyContainer) {
            this.init();
        }
    }
    
    init() {
        this.setupProgressTracking();
        this.setupAccessibility();
        this.setupAutoSave();
        this.setupKeyboardNavigation();
    }
    
    setupProgressTracking() {
        const questions = document.querySelectorAll('.question-container');
        const totalQuestions = questions.length;
        
        if (totalQuestions > 0) {
            this.createProgressIndicator(totalQuestions);
            this.updateProgress();
        }
    }
    
    createProgressIndicator(total) {
        const progressContainer = document.createElement('div');
        progressContainer.className = 'progress-indicator mb-6';
        progressContainer.innerHTML = `
            <div class="flex justify-between items-center mb-2">
                <span class="text-sm text-gray-600">Progress</span>
                <span class="text-sm text-gray-600"><span id="current-step">0</span> of ${total}</span>
            </div>
            <div class="w-full bg-gray-200 rounded-full h-2">
                <div id="progress-bar" class="bg-blue-600 h-2 rounded-full transition-all duration-300" style="width: 0%"></div>
            </div>
        `;
        
        const surveyCard = document.querySelector('.survey-card') || this.surveyContainer;
        surveyCard.insertBefore(progressContainer, surveyCard.firstChild);
    }
    
    updateProgress() {
        const questions = document.querySelectorAll('.question-container');
        const progressBar = document.getElementById('progress-bar');
        const currentStepElement = document.getElementById('current-step');
        
        if (!progressBar || !currentStepElement) return;
        
        let completedQuestions = 0;
        
        questions.forEach(question => {
            const npsInput = question.querySelector('input[name="nps_score"]:checked');
            const textInput = question.querySelector('textarea[name="open_text"]');
            
            if (npsInput || (textInput && textInput.value.trim())) {
                completedQuestions++;
            }
        });
        
        const progressPercentage = (completedQuestions / questions.length) * 100;
        progressBar.style.width = `${progressPercentage}%`;
        currentStepElement.textContent = completedQuestions;
    }
    
    setupAccessibility() {
        // Add ARIA labels and descriptions
        const npsButtons = document.querySelectorAll('.nps-button');
        npsButtons.forEach((button, index) => {
            button.setAttribute('aria-label', `Rate ${index} out of 10`);
            button.setAttribute('role', 'button');
            button.setAttribute('tabindex', '0');
        });
        
        // Add keyboard support for NPS buttons
        npsButtons.forEach(button => {
            button.addEventListener('keydown', (e) => {
                if (e.key === 'Enter' || e.key === ' ') {
                    e.preventDefault();
                    button.click();
                }
            });
        });
        
        // Announce form submission status to screen readers
        this.setupScreenReaderAnnouncements();
    }
    
    setupScreenReaderAnnouncements() {
        const announcer = document.createElement('div');
        announcer.setAttribute('aria-live', 'polite');
        announcer.setAttribute('aria-atomic', 'true');
        announcer.className = 'sr-only';
        announcer.id = 'survey-announcer';
        document.body.appendChild(announcer);
    }
    
    announce(message) {
        const announcer = document.getElementById('survey-announcer');
        if (announcer) {
            announcer.textContent = message;
        }
    }
    
    setupAutoSave() {
        const form = document.getElementById('survey-form');
        if (!form) return;
        
        const inputs = form.querySelectorAll('input, textarea');
        let autoSaveTimeout;
        
        inputs.forEach(input => {
            input.addEventListener('input', () => {
                clearTimeout(autoSaveTimeout);
                autoSaveTimeout = setTimeout(() => {
                    this.autoSaveProgress();
                    this.updateProgress();
                }, 1000);
            });
        });
        
        // Restore saved progress on page load
        this.restoreProgress();
    }
    
    autoSaveProgress() {
        const form = document.getElementById('survey-form');
        if (!form) return;
        
        const formData = new FormData(form);
        const progressData = {};
        
        for (let [key, value] of formData.entries()) {
            progressData[key] = value;
        }
        
        // Save to localStorage
        const surveyId = this.getSurveyId();
        if (surveyId) {
            localStorage.setItem(`survey_progress_${surveyId}`, JSON.stringify(progressData));
        }
    }
    
    restoreProgress() {
        const surveyId = this.getSurveyId();
        if (!surveyId) return;
        
        const savedProgress = localStorage.getItem(`survey_progress_${surveyId}`);
        if (!savedProgress) return;
        
        try {
            const progressData = JSON.parse(savedProgress);
            
            // Restore form values
            Object.entries(progressData).forEach(([key, value]) => {
                const input = document.querySelector(`[name="${key}"]`);
                if (input) {
                    if (input.type === 'radio') {
                        const radioInput = document.querySelector(`[name="${key}"][value="${value}"]`);
                        if (radioInput) {
                            radioInput.checked = true;
                        }
                    } else {
                        input.value = value;
                    }
                }
            });
            
            this.updateProgress();
            this.announce('Previous progress restored');
        } catch (e) {
            console.warn('Failed to restore survey progress:', e);
        }
    }
    
    getSurveyId() {
        const pathParts = window.location.pathname.split('/');
        return pathParts[pathParts.length - 1];
    }
    
    setupKeyboardNavigation() {
        const form = document.getElementById('survey-form');
        if (!form) return;
        
        // Handle Enter key on form elements
        form.addEventListener('keydown', (e) => {
            if (e.key === 'Enter' && e.target.tagName !== 'TEXTAREA') {
                e.preventDefault();
                
                // Find next focusable element
                const focusableElements = form.querySelectorAll(
                    'input, textarea, button, select, [tabindex]:not([tabindex="-1"])'
                );
                const currentIndex = Array.from(focusableElements).indexOf(e.target);
                const nextElement = focusableElements[currentIndex + 1];
                
                if (nextElement) {
                    nextElement.focus();
                } else {
                    // If at the end, focus submit button
                    const submitButton = form.querySelector('button[type="submit"]');
                    if (submitButton) {
                        submitButton.focus();
                    }
                }
            }
        });
    }
    
    clearSavedProgress() {
        const surveyId = this.getSurveyId();
        if (surveyId) {
            localStorage.removeItem(`survey_progress_${surveyId}`);
        }
    }
}

// Initialize when DOM is loaded
document.addEventListener('DOMContentLoaded', () => {
    if (document.body.classList.contains('survey-page')) {
        new SurveyPage();
    }
});

// Clear saved progress when survey is completed
document.addEventListener('surveyCompleted', () => {
    const surveyPage = new SurveyPage();
    surveyPage.clearSavedProgress();
});
