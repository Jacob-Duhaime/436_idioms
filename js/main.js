document.getElementById("profileIcon").addEventListener("click", function(event) {
    const menu = document.getElementById("profileMenu");
    // Toggle visibility of the menu (snackbar)
    menu.style.display = (menu.style.display === "block") ? "none" : "block";
});

// Function to show the login, signup, or edit account forms
function showForm(type) {
    const menu = document.getElementById("profileMenu");
    if (type === "login") {
        menu.innerHTML = `
            <form method="post" action="login.php" id="loginForm">
                <h4>Log In</h4>
                <input type="email" name="email" placeholder="Email" required>
                <input type="password" name="password" placeholder="Password" required>
                <button type="submit">Log In</button>
                <a href="#" onclick="resetMenu()">← Back</a>
            </form>`;
    } else if (type === "signup") {
        menu.innerHTML = `
            <form method="post" action="signup.php" id="signupForm">
                <h4>Sign Up</h4>
                <input type="text" name="name" placeholder="Username" required>
                <input type="email" name="email" placeholder="Email" required>
                <input type="password" name="password" placeholder="Password" required>
                <button type="submit">Create Account</button>
                <a href="#" onclick="resetMenu()">← Back</a>
            </form>`;
    } else if (type === "edit") {
        menu.innerHTML = `
            <form method="post" action="edit_account.php" id="editForm">
                <h4>Edit Account</h4>
                <input type="password" name="currentPassword" placeholder="Current Password">
                <input type="text" name="newUsername" placeholder="New Username">
                <input type="email" name="newEmail" placeholder="New Email">
                <input type="password" name="newPassword" placeholder="New Password">
                <button type="submit">Update Account</button>
                <a href="#" onclick="resetLoggedIn()">← Back</a>
            </form>`;
    }
}

// Function to reset the menu to initial state
function resetMenu() {
    const menu = document.getElementById("profileMenu");
    menu.innerHTML = `
        <a href="#" onclick="showForm('signup')">Sign Up</a>
        <a href="#" onclick="showForm('login')">Log In</a>
    `;
}

// Function to show logged-in menu
function resetLoggedIn() {
    const menu = document.getElementById("profileMenu");
    menu.innerHTML = `
        <a href="#" onclick="showForm('edit')">Edit Account</a>
        <a href="logout.php">Log Out</a>
    `;
}

// Close the profile menu if the user clicks outside of it, but only if not interacting with the form
document.addEventListener("click", function(event) {
    const icon = document.getElementById("profileIcon");
    const menu = document.getElementById("profileMenu");
    const form = menu.querySelector('form');
    
    if (!icon.contains(event.target) && !menu.contains(event.target) && !form?.contains(event.target)) {
        menu.style.display = "none"; // Close the menu
    }
});

// Prevent form submission from closing the menu (when the user is inside the form)
document.getElementById("profileMenu").addEventListener("click", function(event) {
    event.stopPropagation(); // Prevent the click event from bubbling up to document
});

document.querySelectorAll('.upvote-btn, .downvote-btn').forEach(button => {
    button.addEventListener('click', async () => {
        const idiomID = button.dataset.idiomId;
        const voteType = button.classList.contains('upvote-btn') ? 1 : 0;

        const response = await fetch('vote.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({ idiomID, voteType })
        });

        const result = await response.json();
        if (result.failure) {
            alert(result.error || 'Voting failed');
        }
        // if (result.success) {
            document.querySelector(`.upvote-count[data-idiom-id="${idiomID}"]`).textContent = result.votes_up;
            document.querySelector(`.downvote-count[data-idiom-id="${idiomID}"]`).textContent = result.votes_down;

        // } else {
        //     alert(result.error || 'Voting failed');
        // }
    });
});