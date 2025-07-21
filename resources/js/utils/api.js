/**
 * API Utility Functions
 * Provides centralized API communication methods
 */

class ApiClient {
    constructor() {
        this.baseUrl = window.location.origin;
        this.csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
    }
    
    async request(url, options = {}) {
        const config = {
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': this.csrfToken,
                ...options.headers
            },
            ...options
        };
        
        // Handle FormData
        if (options.body instanceof FormData) {
            delete config.headers['Content-Type'];
        }
        
        try {
            const response = await fetch(`${this.baseUrl}${url}`, config);
            
            if (!response.ok) {
                throw new ApiError(`HTTP ${response.status}: ${response.statusText}`, response.status);
            }
            
            const contentType = response.headers.get('content-type');
            if (contentType && contentType.includes('application/json')) {
                return await response.json();
            }
            
            return await response.text();
        } catch (error) {
            if (error instanceof ApiError) {
                throw error;
            }
            throw new ApiError(`Network error: ${error.message}`, 0);
        }
    }
    
    async get(url, params = {}) {
        const queryString = new URLSearchParams(params).toString();
        const fullUrl = queryString ? `${url}?${queryString}` : url;
        
        return this.request(fullUrl, {
            method: 'GET'
        });
    }
    
    async post(url, data = {}) {
        return this.request(url, {
            method: 'POST',
            body: JSON.stringify(data)
        });
    }
    
    async postForm(url, formData) {
        return this.request(url, {
            method: 'POST',
            body: formData
        });
    }
    
    async put(url, data = {}) {
        return this.request(url, {
            method: 'PUT',
            body: JSON.stringify(data)
        });
    }
    
    async delete(url) {
        return this.request(url, {
            method: 'DELETE'
        });
    }
}

class ApiError extends Error {
    constructor(message, status) {
        super(message);
        this.name = 'ApiError';
        this.status = status;
    }
}

// Survey-specific API methods
class SurveyApi extends ApiClient {
    async submitSurveyResponse(surveyId, formData) {
        return this.postForm(`/survey/${surveyId}/response`, formData);
    }
    
    async submitFollowupResponse(surveyId, answer) {
        return this.post(`/survey/${surveyId}/ai-follow-up`, {
            ai_follow_up_answer: answer
        });
    }
    
    async getSurvey(surveyId) {
        return this.get(`/survey/${surveyId}`);
    }
    
    async createSurvey(surveyData) {
        return this.post('/admin/surveys', surveyData);
    }
    
    async updateSurvey(surveyId, surveyData) {
        return this.put(`/admin/surveys/${surveyId}`, surveyData);
    }
    
    async deleteSurvey(surveyId) {
        return this.delete(`/admin/surveys/${surveyId}`);
    }
    
    async getSurveyResponses(surveyId, page = 1) {
        return this.get(`/admin/surveys/${surveyId}/responses`, { page });
    }
}

// Export instances
window.apiClient = new ApiClient();
window.surveyApi = new SurveyApi();

export { ApiClient, ApiError, SurveyApi };
