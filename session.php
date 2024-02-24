<?php
session_start();
$db = new PDO('sqlite:forum.db');
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$db->exec("CREATE TABLE IF NOT EXISTS users (id INTEGER PRIMARY KEY AUTOINCREMENT, username TEXT NOT NULL, password TEXT NOT NULL)");
$db->exec("CREATE TABLE IF NOT EXISTS posts (id INTEGER PRIMARY KEY AUTOINCREMENT, title TEXT NOT NULL, content TEXT NOT NULL, user_id INTEGER NOT NULL, date DATETIME, FOREIGN KEY (user_id) REFERENCES users(id))");

if (isset($_POST['login'])) {
  $username = htmlspecialchars(trim($_POST['username']));
  $password = htmlspecialchars($_POST['password']);

  if (empty($username) || empty($password)) {
    echo 'Please enter username and password';
    exit;
  }

  $query = "SELECT * FROM users WHERE username=:username AND password=:password";
  $stmt = $db->prepare($query);
  $stmt->execute(array(':username' => $username, ':password' => $password));
  $user = $stmt->fetch();

  if ($user) {
    $_SESSION['user_id'] = $user['id'];
    setcookie('user_id', $user['id'], time() + (365 * 24 * 60 * 60), '/');
    header('Location: index.php');
    exit;
  } else {
    echo 'Invalid username or password';
    exit;
  }
} elseif (isset($_POST['register'])) {
  $username = htmlspecialchars(trim($_POST['username']));
  $password = htmlspecialchars($_POST['password']);

  if (empty($username) || empty($password)) {
    echo 'Please enter username and password';
    exit;
  }

  $query = "SELECT * FROM users WHERE username=:username";
  $stmt = $db->prepare($query);
  $stmt->execute(array(':username' => $username));
  $user = $stmt->fetch();

  if ($user) {
    echo 'Username already taken';
    exit;
  }

  $query = "INSERT INTO users (username, password) VALUES (:username, :password)";
  $stmt = $db->prepare($query);
  $stmt->execute(array(':username' => $username, ':password' => $password));

  $_SESSION['user_id'] = $db->lastInsertId();
  setcookie('user_id', $_SESSION['user_id'], time() + (365 * 24 * 60 * 60), '/');
  header('Location: index.php');
  exit;
}
?>

<!DOCTYPE html>
<html lang="en" data-bs-theme="dark">
  <head>
    <title>HiroFORUM - Login/Register</title>
    <meta charset="UTF-8"> 
    <meta name="viewport" content="width=device-width, initial-scale=1">
  </head>
  <body>
    <div align="center">
      <br><br><br><br><br><br>
      <h1>Login and Register</h1>
      <form method="post">
        <div>
          <input type="text" name="username" class="form-control rounded-3" id="floatingInput" placeholder="Username">
        </div>
        <br>
        <div>
          <input type="password" name="password" class="form-control rounded-3" id="floatingPassword" placeholder="Password">
        </div>
        <br>
        <div>
          <button class="btn btn-primary fw-bold rounded w-50" type="submit" name="login">Login</button>
          <button class="btn btn-primary fw-bold rounded w-50" type="submit" name="register">Register</button>
        </div>
      </form>
    </div>
  </body>
</html>