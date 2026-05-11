/**
 * Sends a POST request to api.php with the given action and optional payload.
 * Returns the parsed JSON response, or an error object on network failure.
 *
 * @param {string} action - The API action key (e.g. 'fetchuser', 'set_theme').
 * @param {Object} data   - Additional key/value pairs appended to the form body.
 * @returns {Promise<Object>} Parsed API response.
 */
async function apiCall(action, data = {}) {
    const formData = new FormData();
    formData.append('action', action);
    for (const key in data) {
        formData.append(key, data[key]);
    }

    try {
        const response = await fetch('api.php', {
            method: 'POST',
            body: formData
        });
        return await response.json();
    } catch (error) {
        return { status: 'error', output: 'ERR: Server connection failed.' };
    }
}
