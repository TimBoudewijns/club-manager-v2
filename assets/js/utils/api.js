// API utility for AJAX calls
export const API = {
    /**
     * Make AJAX POST request
     */
    async post(action, data = {}) {
        const formData = new FormData();
        formData.append('action', action);
        formData.append('nonce', clubManagerAjax.nonce);
        
        // Add all data fields
        Object.keys(data).forEach(key => {
            formData.append(key, data[key]);
        });
        
        try {
            const response = await fetch(clubManagerAjax.ajax_url, {
                method: 'POST',
                body: formData
            });
            
            const result = await response.json();
            
            if (!result.success) {
                throw new Error(result.data || 'Request failed');
            }
            
            return result.data;
        } catch (error) {
            console.error('API Error:', error);
            throw error;
        }
    },
    
    /**
     * Handle API errors
     */
    handleError(error) {
        if (error.message) {
            alert(error.message);
        } else {
            alert('An unexpected error occurred. Please try again.');
        }
    }
}; 
