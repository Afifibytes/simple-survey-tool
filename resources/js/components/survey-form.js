/**
 * Survey Form Handler
 * Handles the public survey form submission and AI follow-up flow
 */

class SurveyForm {
    constructor() {
        console.log('SurveyForm constructor called');
        this.form = document.getElementById('survey-form');
        this.aiFollowupContainer = document.getElementById('ai-followup');
        this.thankYouContainer = document.getElementById('thank-you');
        this.submitButton = null;
        this.responseId = null; // Store the response ID

        console.log('Form element found:', this.form);

        if (this.form) {
            console.log('Initializing SurveyForm');
            this.init();
        } else {
            console.log('No survey form found');
        }
    }

    init() {
        this.submitButton = this.form.querySelector('button[type="submit"]');
        this.bindEvents();
        this.setupNPSButtons();
    }

    bindEvents() {
        this.form.addEventListener('submit', this.handleSubmit.bind(this));

        // AI follow-up submission
        const followupButton = document.getElementById('submit-followup');
        if (followupButton) {
            followupButton.addEventListener('click', this.handleFollowupSubmit.bind(this));
        }
    }

    setupNPSButtons() {
        const npsButtons = this.form.querySelectorAll('.nps-button');
        npsButtons.forEach(button => {
            button.addEventListener('click', (e) => {
                const input = button.previousElementSibling;
                if (input && input.type === 'radio') {
                    input.checked = true;
                    this.updateNPSSelection();
                }
            });
        });
    }

    updateNPSSelection() {
        const npsButtons = this.form.querySelectorAll('.nps-button');
        const checkedInput = this.form.querySelector('input[name="nps_score"]:checked');

        npsButtons.forEach(button => {
            button.classList.remove('selected');
        });

        if (checkedInput) {
            const selectedButton = checkedInput.nextElementSibling;
            if (selectedButton) {
                selectedButton.classList.add('selected');
            }
        }
    }

    async handleSubmit(e) {
        console.log('SurveyForm handleSubmit called');
        e.preventDefault();

        if (!this.validateForm()) {
            return;
        }

        this.setLoading(true);

        try {
            const formData = new FormData(this.form);
            const response = await this.submitSurvey(formData);

            if (response.success) {
                this.responseId = response.response_id;

                this.hideForm();

                if (response.has_follow_up && response.response.ai_follow_up_question) {
                    this.showAIFollowup(response.response.ai_follow_up_question);
                } else {
                    this.showThankYou();
                }
            } else {
                this.showError('Failed to submit survey. Please try again.');
            }
        } catch (error) {
            console.error('Survey submission error:', error);
            this.showError('There was an error submitting your response. Please try again.');
        } finally {
            this.setLoading(false);
        }
    }

    async handleFollowupSubmit() {
        const answerTextarea = document.getElementById('ai-answer');
        const answer = answerTextarea.value.trim();

        if (!answer) {
            this.showError('Please provide an answer to the follow-up question.');
            return;
        }

        const followupButton = document.getElementById('submit-followup');
        const originalText = followupButton.textContent;
        followupButton.textContent = 'Submitting...';
        followupButton.disabled = true;

        try {
            const response = await this.submitFollowup(answer);

            if (response.success) {
                this.hideAIFollowup();
                this.showThankYou();
            } else {
                this.showError('Failed to submit follow-up answer. Please try again.');
            }
        } catch (error) {
            console.error('Follow-up submission error:', error);
            this.showError('There was an error submitting your answer. Please try again.');
        } finally {
            followupButton.textContent = originalText;
            followupButton.disabled = false;
        }
    }

    validateForm() {
        const npsScore = this.form.querySelector('input[name="nps_score"]:checked');
        const openText = this.form.querySelector('textarea[name="open_text"]');

        if (!npsScore && (!openText || !openText.value.trim())) {
            this.showError('Please provide either an NPS score or text response.');
            return false;
        }

        return true;
    }

    async submitSurvey(formData) {
        // Add response_id to form data if we have one
        if (this.responseId) {
            formData.append('response_id', this.responseId);
        }

        const response = await fetch(this.form.action, {
            method: 'POST',
            body: formData,
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            }
        });

        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }

        return await response.json();
    }

    async submitFollowup(answer) {
        const url = this.form.action.replace('/response', '/ai-follow-up');

        const payload = {
            ai_follow_up_answer: answer
        };

        // Include response_id if we have it
        if (this.responseId) {
            payload.response_id = this.responseId;
        }

        console.log('Follow-up payload:', payload);
        console.log('Current responseId:', this.responseId);

        const response = await fetch(url, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: JSON.stringify(payload)
        });

        console.log('Follow-up response status:', response.status, response.statusText);

        if (!response.ok) {
            const errorText = await response.text();
            console.error('Follow-up HTTP error response:', errorText);
            throw new Error(`HTTP error! status: ${response.status}`);
        }

        const contentType = response.headers.get('content-type');
        console.log('Follow-up response content-type:', contentType);

        if (!contentType || !contentType.includes('application/json')) {
            const responseText = await response.text();
            console.error('Follow-up non-JSON response:', responseText);
            throw new Error('Server returned non-JSON response');
        }

        return await response.json();
    }

    setLoading(loading) {
        if (this.submitButton) {
            this.submitButton.disabled = loading;
            this.submitButton.textContent = loading ? 'Submitting...' : 'Submit Response';
        }
    }

    hideForm() {
        this.form.style.display = 'none';
    }

    showAIFollowup(question) {
        if (this.aiFollowupContainer) {
            const questionElement = document.getElementById('ai-question');
            if (questionElement) {
                questionElement.textContent = question;
            }
            this.aiFollowupContainer.classList.remove('hidden');
        }
    }

    hideAIFollowup() {
        if (this.aiFollowupContainer) {
            this.aiFollowupContainer.style.display = 'none';
        }
    }

    showThankYou() {
        if (this.thankYouContainer) {
            this.thankYouContainer.classList.remove('hidden');
        }
    }

    showError(message) {
        // Create or update error message
        let errorDiv = document.querySelector('.survey-error');
        if (!errorDiv) {
            errorDiv = document.createElement('div');
            errorDiv.className = 'survey-error bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4';
            this.form.parentNode.insertBefore(errorDiv, this.form);
        }
        errorDiv.textContent = message;

        // Auto-hide after 5 seconds
        setTimeout(() => {
            if (errorDiv.parentNode) {
                errorDiv.parentNode.removeChild(errorDiv);
            }
        }, 5000);
    }
}

// Initialize when DOM is loaded
document.addEventListener('DOMContentLoaded', () => {
    new SurveyForm();
});
