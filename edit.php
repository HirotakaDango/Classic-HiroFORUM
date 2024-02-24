<?php
session_start();
$db = new PDO('sqlite:forum.db');
if (!isset($_SESSION['user_id'])) {
  header('Location: session.php');
}

$user_id = $_SESSION['user_id'];

if (isset($_POST['submit'])) {
  $post_id = $_POST['post_id'];
  $title = htmlspecialchars($_POST['title']);
  $content = htmlspecialchars($_POST['content']);
  $content = nl2br($content);
  $query = "UPDATE posts SET title='$title', content='$content' WHERE id='$post_id' AND user_id='$user_id'";
  $db->exec($query);
  header("Location: reply.php?id=" . $post_id);
}

if (isset($_GET['id'])) {
  $post_id = $_GET['id'];
  $query = "SELECT * FROM posts WHERE id='$post_id' AND user_id='$user_id'";
  $post = $db->query($query)->fetch();
  if (!$post) {
    header("Location: index.php");
  }
}
?>

<!DOCTYPE html>
<html lang="en" data-bs-theme="dark">
  <head>
    <title>Edit <?php echo $post['title'] ?></title>
    <meta charset="UTF-8"> 
    <meta name="viewport" content="width=device-width, initial-scale=1">
  </head>
  <body>
    <div width="100%" align="center">
      <div align="right">
        <a href="index.php">home</a> | <a href="delete.php?id=<?php echo $post_id; ?>" onclick="return confirm('Are you sure?');">delete this thread</a> | <a href="settings.php">settings</a>
      </div>
      <div align="center">
        <h1>Classic HiroFORUM</h1>
      </div>
      <br>
      <form method="post" enctype="multipart/form-data">
        <input type="hidden" name="post_id" value="<?php echo $post_id ?>">
        <div>
          <input style="width: 90%;" type="text" name="title" placeholder="Enter title" maxlength="100" value="<?php echo $post['title'] ?>" required>
        </div>
        <br>
        <div>
          <textarea style="width: 90%;" name="content" rows="25" oninput="stripHtmlTags(this)" placeholder="Enter content" required><?php echo strip_tags($post['content']) ?></textarea>
        </div>
        <br>
        <div>
          <button type="submit" name="submit">Submit</button>
        </div>
      </form>
    </div>
  </body>
</html>