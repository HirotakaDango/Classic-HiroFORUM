<?php
session_start();

if (!isset($_SESSION['user_id'])) {
  header('Location: session.php');
  exit();
}

// Establish database connection
try {
  $db = new PDO('sqlite:forum.db');
  $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
  die("Database connection failed: " . $e->getMessage());
}

// Get user ID from session
$user_id = $_SESSION['user_id'];

// Check if form submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  // Get input data
  $current_password = htmlspecialchars($_POST['current_password']);
  $new_password = htmlspecialchars($_POST['new_password']);
  $confirm_password = htmlspecialchars($_POST['confirm_password']);

  // Validate input data
  $errors = [];
  if (empty($current_password)) {
    $errors[] = "Please enter your current password.";
  }
  if (empty($new_password)) {
    $errors[] = "Please enter a new password.";
  }
  if ($new_password !== $confirm_password) {
    $errors[] = "New password and confirm password do not match.";
  }

  // Check if current password is correct
  $stmt = $db->prepare("SELECT * FROM users WHERE id = :user_id AND password = :password");
  $stmt->bindParam(":user_id", $user_id);
  $stmt->bindParam(":password", $current_password);
  $stmt->execute();
  $user = $stmt->fetch(PDO::FETCH_ASSOC);
  if (!$user) {
    $errors[] = "Current password is incorrect.";
  }

  // If no errors, update password in database
  if (empty($errors)) {
    $stmt = $db->prepare("UPDATE users SET password = :new_password WHERE id = :user_id");
    $stmt->bindParam(":new_password", $new_password);
    $stmt->bindParam(":user_id", $user_id);
    $stmt->execute();
    $success_message = "Password updated successfully.";
    echo "<script>alert('$success_message');</script>";
  } else {
    $error_message = implode("\\n", $errors);
    echo "<script>alert('$error_message');</script>";
  }
}
?>

<!DOCTYPE html>
<html lang="en" data-bs-theme="dark">
  <head>
    <title>Change Password</title>
    <meta charset="UTF-8"> 
    <meta name="viewport" content="width=device-width, initial-scale=1">
  </head>
  <body>
    <div align="center">
      <div align="right">
        <a href="index.php">home</a> | <a href="upload.php">upload your post</a>
      </div>
      <br><br><br><br><br>
      <h1>Change Password</h1>
      <form method="post">
        <div>
          <input type="password" class="form-control rounded border-3 focus-ring focus-ring-dark" name="current_password" placeholder="Enter current password" maxlength="40" pattern="^[a-zA-Z0-9_@.-]+$">
        </div>
        <br>
        <div>
          <input type="password" class="form-control rounded border-3 focus-ring focus-ring-dark" name="new_password" max placeholder="Type new password"length="40" pattern="^[a-zA-Z0-9_@.-]+$">
        </div>
        <br>
        <div>
          <input type="password" class="form-control rounded border-3 focus-ring focus-ring-dark" name="confirm_password" placeholder="Confirm new password" maxlength="40" pattern="^[a-zA-Z0-9_@.-]+$">
        </div>
        <br>
        <div>
          <button type="submit" name="submit">Save</button>
        </div> 
      </form>
    </div>
  </body>
</html>