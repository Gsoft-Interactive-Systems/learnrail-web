/**
 * Learnrail API Client
 * Handles all API requests with CSRF protection
 */

const API = {
    /**
     * Make an API request
     */
    async request(method, endpoint, data = null, options = {}) {
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;

        const config = {
            method,
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                ...(csrfToken && { 'X-CSRF-Token': csrfToken })
            },
            credentials: 'same-origin',
            ...options
        };

        if (data && ['POST', 'PUT', 'PATCH'].includes(method)) {
            config.body = JSON.stringify(data);
        }

        try {
            const response = await fetch(endpoint, config);
            const json = await response.json();

            if (!response.ok) {
                throw {
                    status: response.status,
                    message: json.message || 'Request failed',
                    errors: json.errors || {}
                };
            }

            return json;
        } catch (error) {
            if (error.status) {
                throw error;
            }
            throw {
                status: 0,
                message: 'Network error. Please check your connection.',
                errors: {}
            };
        }
    },

    /**
     * GET request
     */
    get(endpoint, params = {}) {
        const queryString = new URLSearchParams(params).toString();
        const url = queryString ? `${endpoint}?${queryString}` : endpoint;
        return this.request('GET', url);
    },

    /**
     * POST request
     */
    post(endpoint, data = {}) {
        return this.request('POST', endpoint, data);
    },

    /**
     * PUT request
     */
    put(endpoint, data = {}) {
        return this.request('PUT', endpoint, data);
    },

    /**
     * DELETE request
     */
    delete(endpoint) {
        return this.request('DELETE', endpoint);
    },

    /**
     * Upload file
     */
    async upload(endpoint, formData) {
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;

        const config = {
            method: 'POST',
            headers: {
                'Accept': 'application/json',
                ...(csrfToken && { 'X-CSRF-Token': csrfToken })
            },
            credentials: 'same-origin',
            body: formData
        };

        try {
            const response = await fetch(endpoint, config);
            const json = await response.json();

            if (!response.ok) {
                throw {
                    status: response.status,
                    message: json.message || 'Upload failed',
                    errors: json.errors || {}
                };
            }

            return json;
        } catch (error) {
            if (error.status) {
                throw error;
            }
            throw {
                status: 0,
                message: 'Network error. Please check your connection.',
                errors: {}
            };
        }
    }
};

// Export for use in modules
if (typeof module !== 'undefined' && module.exports) {
    module.exports = API;
}
