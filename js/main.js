document.addEventListener("DOMContentLoaded", function() {
    // Show/hide the profile menu when the profile icon is clicked
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
});
