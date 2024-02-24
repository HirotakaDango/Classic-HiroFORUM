<?php
session_start();
$db = new PDO('sqlite:forum.db');
if (!isset($_SESSION['user_id'])) {
  header('Location: session.php');
}

if (isset($_POST['submit'])) {
  $title = htmlspecialchars($_POST['title']);
  $content = htmlspecialchars($_POST['content']);
  $date = date('Y-m-d H:i:s'); // format the current date as "YYYY-MM-DD"
  $stmt = $db->prepare("INSERT INTO posts (title, content, user_id, date) VALUES (:title, :content, :user_id, :date)"); // added the "date" column
  $stmt->execute(array(':title' => $title, ':content' => $content, ':user_id' => $_SESSION['user_id'], ':date' => $date)); // insert the formatted date into the "date" column
  header('Location: index.php');
}
?>

<!DOCTYPE html>
<html lang="en" data-bs-theme="dark">
  <head>
    <title>Upload</title>
    <meta charset="UTF-8"> 
    <meta name="viewport" content="width=device-width, initial-scale=1">
  </head>
  <body>
    <div width="100%" align="center">
      <div align="right">
        <a href="index.php">home</a> | <a href="settings.php">settings</a>
      </div>
      <div align="center">
        <h1>Classic HiroFORUM</h1>
      </div>
      <br>
      <form method="post" enctype="multipart/form-data">
        <div>
          <input style="width: 90%;" type="text" name="title" placeholder="Enter title" maxlength="100" required>
        </div>
        <br>
        <div>
          <textarea style="width: 90%;" name="content" rows="25" onkeydown="if(event.keyCode == 13) { document.execCommand('insertHTML', false, '<br><br>'); return false; }" placeholder="Enter content" required></textarea>
        </div>
        <br>
        <button type="submit" name="submit">Submit</button>
      </form>
    </div>
  </body>
</html>