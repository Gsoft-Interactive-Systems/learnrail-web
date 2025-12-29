/**
 * Learnrail Web App
 * Main JavaScript file
 */

// ============================================
// TOAST NOTIFICATIONS
// ============================================
const Toast = {
    container: null,

    init() {
        this.container = document.createElement('div');
        this.container.className = 'toast-container';
        document.body.appendChild(this.container);
    },

    show(message, type = 'info', duration = 4000) {
        const icons = {
            success: '<i class="iconoir-check-circle"></i>',
            error: '<i class="iconoir-xmark-circle"></i>',
            warning: '<i class="iconoir-warning-triangle"></i>',
            info: '<i class="iconoir-info-circle"></i>'
        };

        const toast = document.createElement('div');
        toast.className = `toast toast-${type}`;
        toast.innerHTML = `
            <span class="toast-icon">${icons[type] || icons.info}</span>
            <span class="toast-message">${message}</span>
            <button class="toast-close" onclick="this.parentElement.remove()">
                <i class="iconoir-xmark"></i>
            </button>
        `;

        this.container.appendChild(toast);

        setTimeout(() => {
            toast.classList.add('toast-exit');
            setTimeout(() => toast.remove(), 300);
        }, duration);
    },

    success(message) { this.show(message, 'success'); },
    error(message) { this.show(message, 'error'); },
    warning(message) { this.show(message, 'warning'); },
    info(message) { this.show(message, 'info'); }
};

// ============================================
// MODAL MANAGEMENT
// ============================================
const Modal = {
    open(modalId) {
        const modal = document.getElementById(modalId);
        if (modal) {
            modal.classList.add('active');
            document.body.style.overflow = 'hidden';
        }
    },

    close(modalId) {
        const modal = document.getElementById(modalId);
        if (modal) {
            modal.classList.remove('active');
            document.body.style.overflow = '';
        }
    },

    closeAll() {
        document.querySelectorAll('.modal-overlay.active').forEach(modal => {
            modal.classList.remove('active');
        });
        document.body.style.overflow = '';
    }
};

// ============================================
// SIDEBAR TOGGLE (Mobile)
// ============================================
const Sidebar = {
    init() {
        // Setup overlay click/touch handler
        const overlay = document.querySelector('.sidebar-overlay');
        if (overlay) {
            overlay.addEventListener('click', (e) => {
                e.preventDefault();
                this.close();
            });
            overlay.addEventListener('touchend', (e) => {
                e.preventDefault();
                this.close();
            });
        }

        // Close sidebar when clicking on main content area (mobile)
        const mainContent = document.querySelector('.main-content');
        if (mainContent) {
            mainContent.addEventListener('click', (e) => {
                const sidebar = document.querySelector('.sidebar');
                if (sidebar && sidebar.classList.contains('open')) {
                    // Don't close if clicking a link or button
                    if (!e.target.closest('a') && !e.target.closest('button')) {
                        this.close();
                    }
                }
            });
        }
    },

    toggle() {
        const sidebar = document.querySelector('.sidebar');
        const overlay = document.querySelector('.sidebar-overlay');

        if (sidebar) {
            sidebar.classList.toggle('open');
            document.body.classList.toggle('sidebar-open', sidebar.classList.contains('open'));

            if (overlay) {
                overlay.classList.toggle('active', sidebar.classList.contains('open'));
            }
        }
    },

    close() {
        const sidebar = document.querySelector('.sidebar');
        const overlay = document.querySelector('.sidebar-overlay');

        if (sidebar) {
            sidebar.classList.remove('open');
        }
        if (overlay) {
            overlay.classList.remove('active');
        }
        document.body.classList.remove('sidebar-open');
    }
};

// ============================================
// DROPDOWN MANAGEMENT
// ============================================
const Dropdown = {
    toggle(dropdownId) {
        const dropdown = document.getElementById(dropdownId);
        if (dropdown) {
            // Close all other dropdowns
            document.querySelectorAll('.dropdown.open').forEach(d => {
                if (d.id !== dropdownId) d.classList.remove('open');
            });

            dropdown.classList.toggle('open');
        }
    },

    closeAll() {
        document.querySelectorAll('.dropdown.open').forEach(d => {
            d.classList.remove('open');
        });
    }
};

// ============================================
// FORM UTILITIES
// ============================================
const Form = {
    /**
     * Serialize form data to object
     */
    serialize(form) {
        const formData = new FormData(form);
        const data = {};
        formData.forEach((value, key) => {
            if (data[key]) {
                if (!Array.isArray(data[key])) {
                    data[key] = [data[key]];
                }
                data[key].push(value);
            } else {
                data[key] = value;
            }
        });
        return data;
    },

    /**
     * Show form errors
     */
    showErrors(form, errors) {
        // Clear existing errors
        form.querySelectorAll('.form-error').forEach(el => el.remove());
        form.querySelectorAll('.form-input.error').forEach(el => el.classList.remove('error'));

        // Show new errors
        Object.entries(errors).forEach(([field, messages]) => {
            const input = form.querySelector(`[name="${field}"]`);
            if (input) {
                input.classList.add('error');
                const errorEl = document.createElement('div');
                errorEl.className = 'form-error';
                errorEl.textContent = Array.isArray(messages) ? messages[0] : messages;
                input.parentNode.appendChild(errorEl);
            }
        });
    },

    /**
     * Clear form errors
     */
    clearErrors(form) {
        form.querySelectorAll('.form-error').forEach(el => el.remove());
        form.querySelectorAll('.form-input.error').forEach(el => el.classList.remove('error'));
    },

    /**
     * Handle form submission with loading state
     */
    async submit(form, handler) {
        const submitBtn = form.querySelector('[type="submit"]');
        const originalText = submitBtn?.innerHTML;

        try {
            if (submitBtn) {
                submitBtn.disabled = true;
                submitBtn.innerHTML = '<span class="loading-spinner" style="width:20px;height:20px;border-width:2px;"></span> Loading...';
            }

            await handler();
        } finally {
            if (submitBtn) {
                submitBtn.disabled = false;
                submitBtn.innerHTML = originalText;
            }
        }
    }
};

// ============================================
// UTILITIES
// ============================================
const Utils = {
    /**
     * Debounce function
     */
    debounce(func, wait) {
        let timeout;
        return function executedFunction(...args) {
            const later = () => {
                clearTimeout(timeout);
                func(...args);
            };
            clearTimeout(timeout);
            timeout = setTimeout(later, wait);
        };
    },

    /**
     * Format date
     */
    formatDate(dateString, format = 'short') {
        const date = new Date(dateString);
        const options = format === 'long'
            ? { year: 'numeric', month: 'long', day: 'numeric' }
            : { year: 'numeric', month: 'short', day: 'numeric' };
        return date.toLocaleDateString('en-US', options);
    },

    /**
     * Format number
     */
    formatNumber(num) {
        return new Intl.NumberFormat('en-US').format(num);
    },

    /**
     * Format currency
     */
    formatCurrency(amount, currency = 'NGN') {
        const symbols = { NGN: '₦', USD: '$', EUR: '€', GBP: '£' };
        return (symbols[currency] || currency + ' ') + this.formatNumber(amount);
    },

    /**
     * Time ago
     */
    timeAgo(dateString) {
        const date = new Date(dateString);
        const now = new Date();
        const diff = Math.floor((now - date) / 1000);

        if (diff < 60) return 'Just now';
        if (diff < 3600) return Math.floor(diff / 60) + ' minutes ago';
        if (diff < 86400) return Math.floor(diff / 3600) + ' hours ago';
        if (diff < 604800) return Math.floor(diff / 86400) + ' days ago';

        return this.formatDate(dateString);
    },

    /**
     * Copy to clipboard
     */
    async copyToClipboard(text) {
        try {
            await navigator.clipboard.writeText(text);
            Toast.success('Copied to clipboard!');
        } catch (err) {
            Toast.error('Failed to copy');
        }
    },

    /**
     * Scroll to element
     */
    scrollTo(element, offset = 80) {
        const el = typeof element === 'string' ? document.querySelector(element) : element;
        if (el) {
            const top = el.getBoundingClientRect().top + window.pageYOffset - offset;
            window.scrollTo({ top, behavior: 'smooth' });
        }
    }
};

// ============================================
// ALPINE.JS COMPONENTS
// ============================================
document.addEventListener('alpine:init', () => {
    // AI Chat Component
    Alpine.data('aiChat', (initialCourseId = null) => ({
        messages: [],
        input: '',
        isTyping: false,
        sessionId: null,
        courseId: initialCourseId,

        init() {
            this.messages = [{
                role: 'assistant',
                content: "Hello! I'm your AI tutor. How can I help you learn today?"
            }];
        },

        async sendMessage() {
            if (!this.input.trim() || this.isTyping) return;

            const userMessage = this.input.trim();
            this.messages.push({ role: 'user', content: userMessage });
            this.input = '';
            this.isTyping = true;

            // Scroll to bottom
            this.$nextTick(() => {
                const container = this.$refs.messages;
                if (container) container.scrollTop = container.scrollHeight;
            });

            try {
                const response = await API.post('/ai/chat', {
                    message: userMessage,
                    session_id: this.sessionId,
                    course_id: this.courseId
                });

                if (response.success && response.data) {
                    this.sessionId = response.data.session_id || this.sessionId;
                    this.messages.push({
                        role: 'assistant',
                        content: response.data.message || response.data.reply || 'I apologize, but I could not generate a response.'
                    });
                }
            } catch (error) {
                Toast.error(error.message || 'Failed to get response');
                this.messages.push({
                    role: 'assistant',
                    content: "I'm sorry, I encountered an error. Please try again."
                });
            } finally {
                this.isTyping = false;
                this.$nextTick(() => {
                    const container = this.$refs.messages;
                    if (container) container.scrollTop = container.scrollHeight;
                });
            }
        }
    }));

    // Accountability Chat Component
    Alpine.data('accountabilityChat', (partnerId = null) => ({
        messages: [],
        input: '',
        isLoading: false,
        partnerId: partnerId,

        init() {
            // Messages would be loaded from server
        },

        async sendMessage() {
            if (!this.input.trim() || this.isLoading) return;

            const message = this.input.trim();
            this.messages.push({
                role: 'user',
                content: message,
                time: new Date().toISOString()
            });
            this.input = '';
            this.isLoading = true;

            try {
                await API.post('/accountability/messages', { message });
            } catch (error) {
                Toast.error('Failed to send message');
            } finally {
                this.isLoading = false;
            }
        }
    }));

    // Dropdown Component
    Alpine.data('dropdown', () => ({
        open: false,

        toggle() {
            this.open = !this.open;
        },

        close() {
            this.open = false;
        }
    }));

    // Modal Component
    Alpine.data('modal', () => ({
        open: false,

        show() {
            this.open = true;
            document.body.style.overflow = 'hidden';
        },

        hide() {
            this.open = false;
            document.body.style.overflow = '';
        }
    }));

    // Tabs Component
    Alpine.data('tabs', (defaultTab = 0) => ({
        activeTab: defaultTab,

        setTab(index) {
            this.activeTab = index;
        }
    }));

    // Confirm Dialog
    Alpine.data('confirm', () => ({
        open: false,
        title: '',
        message: '',
        callback: null,

        show(title, message, callback) {
            this.title = title;
            this.message = message;
            this.callback = callback;
            this.open = true;
        },

        confirm() {
            if (this.callback) this.callback();
            this.open = false;
        },

        cancel() {
            this.open = false;
        }
    }));
});

// ============================================
// INITIALIZATION
// ============================================
document.addEventListener('DOMContentLoaded', () => {
    // Initialize toast container
    Toast.init();

    // Initialize sidebar (mobile menu)
    Sidebar.init();

    // Close dropdowns when clicking outside
    document.addEventListener('click', (e) => {
        if (!e.target.closest('.dropdown')) {
            Dropdown.closeAll();
        }
    });

    // Close modals when clicking overlay
    document.querySelectorAll('.modal-overlay').forEach(overlay => {
        overlay.addEventListener('click', (e) => {
            if (e.target === overlay) {
                overlay.classList.remove('active');
                document.body.style.overflow = '';
            }
        });
    });

    // Close modals with Escape key
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') {
            Modal.closeAll();
            Dropdown.closeAll();
        }
    });

    // Handle flash messages from server
    const flashSuccess = document.querySelector('[data-flash-success]');
    const flashError = document.querySelector('[data-flash-error]');
    const flashWarning = document.querySelector('[data-flash-warning]');
    const flashInfo = document.querySelector('[data-flash-info]');

    if (flashSuccess) Toast.success(flashSuccess.dataset.flashSuccess);
    if (flashError) Toast.error(flashError.dataset.flashError);
    if (flashWarning) Toast.warning(flashWarning.dataset.flashWarning);
    if (flashInfo) Toast.info(flashInfo.dataset.flashInfo);

    // Add loading state to forms
    document.querySelectorAll('form[data-loading]').forEach(form => {
        form.addEventListener('submit', () => {
            const btn = form.querySelector('[type="submit"]');
            if (btn) {
                btn.disabled = true;
                btn.innerHTML = '<span class="loading-spinner" style="width:20px;height:20px;border-width:2px;"></span> Loading...';
            }
        });
    });

    // Smooth scroll for anchor links
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', (e) => {
            const href = anchor.getAttribute('href');
            if (href !== '#') {
                e.preventDefault();
                Utils.scrollTo(href);
            }
        });
    });

    console.log('Learnrail Web App initialized');
});

// Make utilities available globally
window.API = API;
window.Toast = Toast;
window.Modal = Modal;
window.Sidebar = Sidebar;
window.Dropdown = Dropdown;
window.Form = Form;
window.Utils = Utils;
