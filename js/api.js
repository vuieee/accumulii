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