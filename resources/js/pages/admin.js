/**
 * Admin Page Functionality
 * Handles admin-specific interactions and features
 */

class AdminPage {
    constructor() {
        this.init();
    }

    init() {
        this.setupDeleteConfirmations();
        this.setupSurveyPreview();
        this.setupTableSorting();
        this.setupSearchFilters();
    }

    setupDeleteConfirmations() {
        const deleteButtons = document.querySelectorAll('[data-confirm-delete]');

        deleteButtons.forEach(button => {
            button.addEventListener('click', (e) => {
                const message = button.dataset.confirmDelete || 'Are you sure you want to delete this item?';
                if (!confirm(message)) {
                    e.preventDefault();
                }
            });
        });
    }

    setupSurveyPreview() {
        const previewButtons = document.querySelectorAll('[data-survey-preview]');

        previewButtons.forEach(button => {
            button.addEventListener('click', (e) => {
                e.preventDefault();
                const surveyId = button.dataset.surveyPreview;
                this.openSurveyPreview(surveyId);
            });
        });
    }

    setupTableSorting() {
        const sortableHeaders = document.querySelectorAll('[data-sort]');

        sortableHeaders.forEach(header => {
            header.addEventListener('click', () => {
                const column = header.dataset.sort;
                const currentDirection = header.dataset.direction || 'asc';
                const newDirection = currentDirection === 'asc' ? 'desc' : 'asc';

                this.sortTable(column, newDirection);
                this.updateSortIndicators(header, newDirection);
            });
        });
    }

    setupSearchFilters() {
        const searchInput = document.querySelector('[data-search]');
        if (searchInput) {
            let searchTimeout;

            searchInput.addEventListener('input', (e) => {
                clearTimeout(searchTimeout);
                searchTimeout = setTimeout(() => {
                    this.filterResults(e.target.value);
                }, 300);
            });
        }

        const filterSelects = document.querySelectorAll('[data-filter]');
        filterSelects.forEach(select => {
            select.addEventListener('change', (e) => {
                this.applyFilter(select.dataset.filter, e.target.value);
            });
        });
    }

    openSurveyPreview(surveyId) {
        const previewUrl = `/survey/${surveyId}`;
        const previewWindow = window.open(
            previewUrl,
            'survey-preview',
            'width=800,height=600,scrollbars=yes,resizable=yes'
        );

        if (previewWindow) {
            previewWindow.focus();
        } else {
            // Fallback if popup is blocked
            window.open(previewUrl, '_blank');
        }
    }

    sortTable(column, direction) {
        const table = document.querySelector('[data-sortable-table]');
        if (!table) return;

        const tbody = table.querySelector('tbody');
        const rows = Array.from(tbody.querySelectorAll('tr'));

        rows.sort((a, b) => {
            const aValue = this.getCellValue(a, column);
            const bValue = this.getCellValue(b, column);

            if (direction === 'asc') {
                return aValue.localeCompare(bValue, undefined, { numeric: true });
            } else {
                return bValue.localeCompare(aValue, undefined, { numeric: true });
            }
        });

        // Re-append sorted rows
        rows.forEach(row => tbody.appendChild(row));
    }

    getCellValue(row, column) {
        const cell = row.querySelector(`[data-column="${column}"]`);
        return cell ? cell.textContent.trim() : '';
    }

    updateSortIndicators(activeHeader, direction) {
        // Remove all existing sort indicators
        document.querySelectorAll('[data-sort]').forEach(header => {
            header.classList.remove('sort-asc', 'sort-desc');
            header.removeAttribute('data-direction');
        });

        // Add indicator to active header
        activeHeader.classList.add(`sort-${direction}`);
        activeHeader.dataset.direction = direction;
    }

    filterResults(searchTerm) {
        const filterableItems = document.querySelectorAll('[data-filterable]');
        const term = searchTerm.toLowerCase();

        filterableItems.forEach(item => {
            const searchableText = item.textContent.toLowerCase();
            const shouldShow = !term || searchableText.includes(term);

            item.style.display = shouldShow ? '' : 'none';
        });

        this.updateResultsCount();
    }

    applyFilter(filterType, filterValue) {
        const filterableItems = document.querySelectorAll(`[data-filter-${filterType}]`);

        filterableItems.forEach(item => {
            const itemValue = item.dataset[`filter${filterType.charAt(0).toUpperCase() + filterType.slice(1)}`];
            const shouldShow = !filterValue || itemValue === filterValue;

            item.style.display = shouldShow ? '' : 'none';
        });

        this.updateResultsCount();
    }

    updateResultsCount() {
        const countElement = document.querySelector('[data-results-count]');
        if (countElement) {
            const visibleItems = document.querySelectorAll('[data-filterable]:not([style*="display: none"])');
            countElement.textContent = visibleItems.length;
        }
    }
}

// Survey Form Builder (for create/edit pages)
class SurveyFormBuilder {
    constructor() {
        this.form = document.getElementById('admin-survey-form');
        if (this.form) {
            this.init();
        }
    }

    init() {
        this.setupQuestionPreview();
        this.setupFormValidation();
    }

    setupQuestionPreview() {
        const questionInputs = this.form.querySelectorAll('input[name*="[text]"]');

        questionInputs.forEach(input => {
            input.addEventListener('input', () => {
                this.updatePreview();
            });
        });
    }

    setupFormValidation() {
        this.form.addEventListener('submit', (e) => {
            if (!this.validateSurveyForm()) {
                e.preventDefault();
            }
        });
    }

    validateSurveyForm() {
        const nameInput = this.form.querySelector('input[name="name"]');
        const questionInputs = this.form.querySelectorAll('input[name*="[text]"]');

        let isValid = true;

        // Validate survey name
        if (!nameInput.value.trim()) {
            this.showFieldError(nameInput, 'Survey name is required.');
            isValid = false;
        }

        // Validate questions
        questionInputs.forEach(input => {
            if (!input.value.trim()) {
                this.showFieldError(input, 'Question text is required.');
                isValid = false;
            }
        });

        return isValid;
    }

    showFieldError(field, message) {
        field.classList.add('is-invalid');

        let errorElement = field.parentNode.querySelector('.form-error');
        if (!errorElement) {
            errorElement = document.createElement('div');
            errorElement.className = 'form-error';
            field.parentNode.appendChild(errorElement);
        }
        errorElement.textContent = message;
    }

    updatePreview() {
        // This could be expanded to show a live preview of the survey
        console.log('Survey preview updated');
    }
}

// Initialize when DOM is loaded
document.addEventListener('DOMContentLoaded', () => {
    if (document.body.classList.contains('admin-page')) {
        new AdminPage();
        new SurveyFormBuilder();
    }
});
