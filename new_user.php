<?php
require_once __DIR__ . '/includes/database.php';
require_once __DIR__ . '/includes/auth.php';

requireAuth();

$user_info = getUserInfo();
$user_role = $user_info['role'];

// Only allow Admins
if ($user_role !== 'Admin') {
    echo "Access denied";
    exit;
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $firstname = htmlspecialchars($_POST['firstname']);
    $lastname  = htmlspecialchars($_POST['lastname']);
    $email     = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
    $password  = $_POST['password'];
    $role      = $_POST['role'];

    // Password validation
    if (!preg_match('/^(?=.*[A-Z])(?=.*[a-z])(?=.*\d).{8,}$/', $password)) {
        echo "Password must be at least 8 characters, include uppercase, lowercase, and a number.";
        exit;
    }

    // Hash password
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

    // Check for duplicate email
    $checkStmt = $conn->prepare("SELECT COUNT(*) FROM users WHERE email = :email");
    $checkStmt->execute([':email' => $email]);
    if ($checkStmt->fetchColumn() > 0) {
        echo "That email is already registered.";
        exit;
    }

    // Insert new user
    $stmt = $conn->prepare("INSERT INTO users (firstname, lastname, email, password, role, created_at) 
                            VALUES (:firstname, :lastname, :email, :password, :role, NOW())");
    $stmt->execute([
        ':firstname' => $firstname,
        ':lastname'  => $lastname,
        ':email'     => $email,
        ':password'  => $hashedPassword,
        ':role'      => $role
    ]);

    echo "User added successfully!";
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Add User - Dolphin CRM</title>
  <link rel="stylesheet" href="styler.css">
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body>
  <div class="form-container">
    <form id="newUserForm" class="user-form">
      <input type="text" name="firstname" placeholder="First Name" required>
      <input type="text" name="lastname" placeholder="Last Name" required>
      <input type="email" name="email" placeholder="Email" required>
      <input type="password" name="password" placeholder="Password" required>
      <select name="role" required>
        <option value="Admin">Admin</option>
        <option value="Member">Member</option>
      </select>
      <button type="submit">Save</button>
    </form>

    <div id="feedback"></div>
  </div>

  <script>
  document.getElementById("newUserForm").addEventListener("submit", function(e) {
    e.preventDefault();

    const formData = new FormData(this);

fetch("new_user.php", {
  method: "POST",
  body: formData
})
.then(response => response.text())
.then(data => {
  const feedback = document.getElementById("feedback");
  feedback.innerHTML = data;

  feedback.classList.remove("error");
  feedback.classList.remove("success");

if (data.includes("successfully")) {
  feedback.classList.add("success"); 
} else if (data.includes("Password") || data.includes("Access denied") || data.includes("registered")) {
  feedback.classList.add("error"); 
}
})
.catch(error => {
  document.getElementById("feedback").innerHTML = "Error submitting form.";
  console.error("Error:", error);
});
</script>
</body>
</html>

