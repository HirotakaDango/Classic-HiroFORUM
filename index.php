<?php
session_start();

$db = new PDO('sqlite:forum.db');
$db->exec("CREATE TABLE IF NOT EXISTS users (id INTEGER PRIMARY KEY AUTOINCREMENT, username TEXT NOT NULL, password TEXT NOT NULL)");
$db->exec("CREATE TABLE IF NOT EXISTS posts (id INTEGER PRIMARY KEY AUTOINCREMENT, title TEXT NOT NULL, content TEXT NOT NULL, user_id INTEGER NOT NULL, date DATETIME, FOREIGN KEY (user_id) REFERENCES users(id))");
$db->exec("CREATE TABLE IF NOT EXISTS comments (id INTEGER PRIMARY KEY AUTOINCREMENT, username TEXT, comment TEXT, date DATETIME, post_id TEXT)");

$posts_per_page = 50;
$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
$start_index = ($page - 1) * $posts_per_page;

// Modify your existing query based on the selected sorting option
$sort_option = isset($_GET['sort']) ? $_GET['sort'] : 'latest';

switch ($sort_option) {
  case 'oldest':
    $order_by = 'ORDER BY posts.id ASC';
    break;
  case 'most_replied':
    $order_by = 'ORDER BY reply_count DESC, posts.id DESC';
    break;
  default:
  $order_by = 'ORDER BY posts.id DESC';
}

$query = "SELECT posts.*, users.username, users.id AS userid, COUNT(comments.id) AS reply_count FROM posts JOIN users ON posts.user_id = users.id LEFT JOIN comments ON posts.id = comments.post_id GROUP BY posts.id $order_by LIMIT $start_index, $posts_per_page";
$posts = $db->query($query)->fetchAll();

$count_query = "SELECT COUNT(*) FROM posts";
$total_posts = $db->query($count_query)->fetchColumn();
$total_pages = ceil($total_posts / $posts_per_page);

// Count the number of music records for the user
$queryPostCount = "SELECT COUNT(*) FROM posts";
$stmtPostCount = $db->prepare($queryPostCount);
$stmtPostCount->execute();
$postCount = $stmtPostCount->fetchColumn();

// Count the number of music records for the user
$queryReplyCount = "SELECT COUNT(*) FROM comments";
$stmtReplyCount = $db->prepare($queryReplyCount);
$stmtReplyCount->execute();
$replyCount = $stmtReplyCount->fetchColumn();
?>

<!DOCTYPE html>
<html lang="en" data-bs-theme="dark">
  <head>
    <title>Classic HiroFORUM</title>
    <meta charset="UTF-8"> 
    <meta name="viewport" content="width=device-width, initial-scale=1">
  </head>
  </head>
  <body>
    <div align="center">
      <div>
        <div align="right">
          <a href="index.php">home</a> | <a href="upload.php">upload your post</a> | <a href="settings.php">settings</a>
        </div>
        <div align="center">
          <h1>Classic HiroFORUM</h1>
        </div>
        <div align="left">
          <h4><?php echo $postCount; ?> posts | <?php echo $replyCount; ?> replies</h4>
          <form method="get" action="index.php">
            <label for="sort">Sort by:</label>
            <select name="sort" id="sort" onchange="this.form.submit()" style="max-width: 130px;">
              <option value="latest" <?php echo (isset($_GET['sort']) && $_GET['sort'] == 'latest') ? 'selected' : ''; ?>>latest</option>
              <option value="oldest" <?php echo (isset($_GET['sort']) && $_GET['sort'] == 'oldest') ? 'selected' : ''; ?>>oldest</option>
              <option value="most_replied" <?php echo (isset($_GET['sort']) && $_GET['sort'] == 'most_replied') ? 'selected' : ''; ?>>most replied</option>
            </select>
          </form>
        </div>
      </div>
      <br><br>
      <div align="center">
        <?php foreach ($posts as $post): ?>
          <table border="1" cellspacing="0" cellpadding="5" width="100%">
            <tr>
              <td>
                <div>
                  <?php if (isset($_SESSION['user_id']) && $_SESSION['user_id'] == $post['userid']): ?>
                    <div align="right">
                      <a href="edit.php?id=<?php echo $post['id']; ?>">edit this post</a>
                    </div>
                  <?php endif; ?>
                  <h5>Thread by <?php echo (mb_strlen($post['username']) > 15) ? mb_substr($post['username'], 0, 15) . '...' : $post['username']; ?>・<?php echo (new DateTime($post['date']))->format("Y/m/d - H:i:s"); ?></h5>
                  <h4><?php echo $post['title']; ?></h4>
                  <div>
                  <?php
                    if (!function_exists('getYouTubeVideoId')) {
                      function getYouTubeVideoId($urlComment)
                      {
                        $videoId = '';
                        $pattern = '/(?:https?:\/\/)?(?:www\.)?(?:youtube\.com\/(?:[^\/\n\s]+\/\S+\/|(?:v|e(?:mbed)?)\/|\S*?[?&]v=)|youtu\.be\/)([a-zA-Z0-9_-]{11})/';
                        if (preg_match($pattern, $urlComment, $matches)) {
                          $videoId = $matches[1];
                        }
                        return $videoId;
                      }
                    }

                    $replyText = isset($post['content']) ? $post['content'] : '';

                    if (!empty($replyText)) {
                      // Truncate to 300 characters
                      $truncatedText = mb_strimwidth($replyText, 0, 300, '...');

                      $paragraphs = explode("\n", $truncatedText);

                      foreach ($paragraphs as $index => $paragraph) {
                        $textWithoutTags = strip_tags($paragraph);
                        $pattern = '/\bhttps?:\/\/\S+/i';

                        $formattedText = preg_replace_callback($pattern, function ($matches) {
                          $url = htmlspecialchars($matches[0]);

                          // Check if the URL ends with .png, .jpg, .jpeg, or .webp
                          if (preg_match('/\.(png|jpg|jpeg|webp)$/i', $url)) {
                            return '<a href="' . $url . '" target="_blank"><img height="200px" loading="lazy" src="' . $url . '" alt="Image"></a>';
                          } elseif (strpos($url, 'youtube.com') !== false) {
                            // If the URL is from YouTube, embed it as an iframe with a very low-resolution thumbnail
                            $videoId = getYouTubeVideoId($url);
                            if ($videoId) {
                              return '<div><iframe loading="lazy" allow="accelerometer; autoplay; encrypted-media; gyroscope; picture-in-picture" class="rounded-4 position-absolute top-0 bottom-0 start-0 end-0 w-100 h-100 border-0 shadow" src="https://www.youtube.com/embed/' . $videoId . '" frameborder="0" allowfullscreen></iframe></div>';
                            } else {
                              return '<a href="' . $url . '">' . $url . '</a>';
                            }
                          } else {
                            return '<a href="' . $url . '">' . $url . '</a>';
                          }
                        }, $textWithoutTags);
                    
                        echo "<p>$formattedText</p>";
                      }

                      // Add "Read more" button outside the loop
                      if (mb_strlen($replyText) > 300) {
                        echo '<p><a href="reply.php?id=' . $post['id'] . '">Read more</a></p>';
                      }
                    } else {
                      echo "Sorry, no text...";
                    }
                  ?>
                  </div>
                  <p class="me-auto fw-medium small"><?php echo $post['reply_count']; ?> replies</p>
                  <br>
                  <a class="btn btn-sm link-body-emphasis border-0 fw-medium m-2 position-absolute bottom-0 end-0" href="reply.php?id=<?php echo $post['id']; ?>">Reply this thread</a>
                </div>
              </td>
            </tr>
          </table>
          <br>
        <?php endforeach; ?>
      </div>
    </div>
    <br><br>
    <div align="center">
      <h4>
        <?php
        $start_page = max(1, $page - 2);
        $end_page = min($total_pages, $page + 2);

        for ($i = $start_page; $i <= $end_page; $i++):
        ?>
          <a href="?page=<?php echo $i; ?>"><?php echo $i; ?></a>
        <?php endfor ?>
      </h4>
    </div>
    <br>
  </body>
</html>