// Get the base URL from the input field
function getApiBaseUrl() {
    return document.getElementById('apiBaseUrl').value;
}

async function makeRequest(method, endpoint = '', data = null) {
    const baseUrl = getApiBaseUrl();
    const url = `${baseUrl}${endpoint}`;
    const options = {
        method: method,
        headers: {
            'Content-Type': 'application/json',
        },
    };

    if (data) {
        options.body = JSON.stringify(data);
    }

    try {
        const response = await fetch(url, options);
        const responseText = await response.text(); // Get raw text first

        // Try to parse as JSON, but handle cases where it's not valid JSON
        try {
            const jsonResponse = JSON.parse(responseText);
            displayResponse(jsonResponse);
        } catch (e) {
            displayResponse({ error: "Invalid JSON response from API", raw: responseText }, true);
        }

        if (!response.ok) {
            // Log the error even if it's not JSON
            console.error(`HTTP error! Status: ${response.status}`, responseText);
        }

    } catch (error) {
        console.error('Fetch error:', error);
        displayResponse({ error: `Network or API connection error: ${error.message}` }, true);
    }
}

function displayResponse(data, isError = false) {
    const responseArea = document.getElementById('apiResponse');
    responseArea.className = 'response-area'; // Reset class
    if (isError) {
        responseArea.classList.add('error');
    }
    responseArea.textContent = JSON.stringify(data, null, 2); // Pretty print JSON
    responseArea.scrollTop = responseArea.scrollHeight; // Scroll to bottom of the response
}

function clearResponse() {
    document.getElementById('apiResponse').textContent = 'Awaiting API response...';
    document.getElementById('apiResponse').className = 'response-area'; // Reset class
}

// --- GET Requests ---
function getUsers() {
    displayResponse("Fetching all users...");
    makeRequest('GET');
}

function getUserById() {
    const userId = document.getElementById('getUserId').value;
    if (userId) {
        displayResponse(`Fetching user with ID: ${userId}...`);
        makeRequest('GET', `?id=${userId}`);
    } else {
        displayResponse({ error: "Please enter a User ID to get by ID." }, true);
    }
}

// --- POST Request ---
function createUser() {
    const name = document.getElementById('postName').value;
    const email = document.getElementById('postEmail').value;

    if (!name || !email) {
        displayResponse({ error: "Name and Email are required to create a user." }, true);
        return;
    }

    displayResponse("Creating user...");
    makeRequest('POST', '', { name: name, email: email });
}

// --- PUT Request ---
function updateUser() {
    const id = document.getElementById('putId').value;
    const name = document.getElementById('putName').value;
    const email = document.getElementById('putEmail').value;

    if (!id) {
        displayResponse({ error: "User ID is required to update a user." }, true);
        return;
    }

    const data = { id: parseInt(id) };
    if (name) {
        data.name = name;
    }
    if (email) {
        data.email = email;
    }

    if (Object.keys(data).length === 1) { // Only 'id' is present
        displayResponse({ error: "Please provide either a new name or a new email to update." }, true);
        return;
    }

    displayResponse(`Updating user with ID: ${id}...`);
    makeRequest('PUT', '', data);
}

// --- DELETE Request ---
function deleteUser() {
    const id = document.getElementById('deleteId').value;

    if (!id) {
        displayResponse({ error: "User ID is required to delete a user." }, true);
        return;
    }

    // For DELETE, ID is expected as a query parameter in your PHP API
    displayResponse(`Deleting user with ID: ${id}...`);
    makeRequest('DELETE', `?id=${id}`);
}

function deleteAllUsers() {
    if (confirm("Are you sure you want to delete ALL users? This action cannot be undone.")) {
        displayResponse("Deleting all users...");
        // Send a DELETE request with a specific parameter to indicate 'all'
        makeRequest('DELETE', '?action=deleteAll');
    }
}