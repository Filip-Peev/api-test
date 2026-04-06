function getApiBaseUrl() {
  return document.getElementById("apiBaseUrl").value;
}

async function makeRequest(method, endpoint = "", data = null) {
  const baseUrl = getApiBaseUrl();
  const url = `${baseUrl}${endpoint}`;
  const options = {
    method: method,
    headers: {
      "Content-Type": "application/json",
    },
  };

  if (data) {
    options.body = JSON.stringify(data);
  }

  try {
    const response = await fetch(url, options);
    const responseText = await response.text();

    try {
      const jsonResponse = JSON.parse(responseText);
      displayResponse(jsonResponse);
    } catch (e) {
      displayResponse(
        { error: "Invalid JSON response from API", raw: responseText },
        true,
      );
    }

    if (!response.ok) {
      console.error(`HTTP error! Status: ${response.status}`, responseText);
    }
  } catch (error) {
    console.error("Fetch error:", error);
    displayResponse(
      { error: `Network or API connection error: ${error.message}` },
      true,
    );
  }
}

function displayResponse(data, isError = false) {
  const responseArea = document.getElementById("apiResponse");
  responseArea.className = "response-area";
  if (isError) {
    responseArea.classList.add("error");
  }
  responseArea.textContent = JSON.stringify(data, null, 2);
  responseArea.scrollTop = responseArea.scrollHeight;
}

function clearResponse() {
  document.getElementById("apiResponse").textContent =
    "Awaiting API response...";
  document.getElementById("apiResponse").className = "response-area";
}

function getUsers() {
  displayResponse("Fetching all users...");
  makeRequest("GET");
}

function getUserById() {
  const userId = document.getElementById("getUserId").value;
  if (userId) {
    displayResponse(`Fetching user with ID: ${userId}...`);
    makeRequest("GET", `?id=${userId}`);
  } else {
    displayResponse({ error: "Please enter a User ID to get by ID." }, true);
  }
}

function createUser() {
  const name = document.getElementById("postName").value;
  const email = document.getElementById("postEmail").value;

  if (!name || !email) {
    displayResponse(
      { error: "Name and Email are required to create a user." },
      true,
    );
    return;
  }

  displayResponse("Creating user...");
  makeRequest("POST", "", { name: name, email: email });
}

function updateUser() {
  const id = document.getElementById("putId").value;
  const name = document.getElementById("putName").value;
  const email = document.getElementById("putEmail").value;

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

  if (Object.keys(data).length === 1) {
    displayResponse(
      { error: "Please provide either a new name or a new email to update." },
      true,
    );
    return;
  }

  displayResponse(`Updating user with ID: ${id}...`);
  makeRequest("PUT", "", data);
}

function deleteUser() {
  const id = document.getElementById("deleteId").value;

  if (!id) {
    displayResponse({ error: "User ID is required to delete a user." }, true);
    return;
  }

  displayResponse(`Deleting user with ID: ${id}...`);
  makeRequest("DELETE", `?id=${id}`);
}

function deleteAllUsers() {
  if (
    confirm(
      "Are you sure you want to delete ALL users? This action cannot be undone.",
    )
  ) {
    displayResponse("Deleting all users...");
    makeRequest("DELETE", "?action=deleteAll");
  }
}

const firstNames = [
  "Alice",
  "Bob",
  "Charlie",
  "David",
  "Ella",
  "Frank",
  "Grace",
  "Hank",
  "Ivy",
  "Димитричко",
  "Jack",
];
const lastNames = [
  "Smith",
  "Johnson",
  "Brown",
  "Taylor",
  "Anderson",
  "Thomas",
  "Jackson",
  "White",
  "Harris",
  "Martin",
];

function getRandomItem(array) {
  return array[Math.floor(Math.random() * array.length)];
}

async function addRandomUser() {
  const feedback = document.getElementById("quickFeedback");
  const btn = document.getElementById("randomUserBtn");

  feedback.innerHTML = '<div class="spinner"></div>';
  btn.disabled = true;
  btn.style.opacity = "0.7";

  const fName = getRandomItem(firstNames);
  const lName = getRandomItem(lastNames);
  const fullName = `${fName} ${lName}`;
  const email = `${fName.toLowerCase()}.${lName.toLowerCase()}@example.com`;

  displayResponse(`Creating user: ${fullName}...`);

  try {
    const baseUrl = getApiBaseUrl();

    const minDelay = new Promise((resolve) => setTimeout(resolve, 500));

    const [response] = await Promise.all([
      fetch(baseUrl, {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({ name: fullName, email: email }),
      }),
      minDelay,
    ]);

    const result = await response.json();

    if (result.id) {
      await makeRequest("GET", `?id=${result.id}`);

      feedback.innerHTML = '<span class="success-text">✔ Added!</span>';

      setTimeout(() => {
        feedback.innerHTML = "";
      }, 3000);
    } else {
      feedback.innerHTML = "❌";
      displayResponse(result);
    }
  } catch (error) {
    feedback.innerHTML = "❌";
    displayResponse({ error: "Connection failed" }, true);
  } finally {
    btn.disabled = false;
    btn.style.opacity = "1";
  }
}
