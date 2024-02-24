<?php
session_start();

$db = new PDO('sqlite:forum.db');

// Check if the user is logged in
$user = null;
$id = isset($_GET['id']) ? $_GET['id'] : null;

if (isset($_SESSION['user_id'])) {
  $user_id = $_SESSION['user_id'];

  // Fetch user information
  $query = "SELECT * FROM users WHERE id='$user_id'";
  $user = $db->query($query)->fetch();

  // Get the 'id' parameter from the URL
  if (isset($_GET['id'])) {
    $id = $_GET['id'];
  }

  // Handle comment creation
  if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['comment'])) {
    $username = $user['username'];
    $comment = nl2br(filter_input(INPUT_POST, 'comment', FILTER_SANITIZE_FULL_SPECIAL_CHARS, FILTER_FLAG_STRIP_LOW));

    // Check if the comment is not empty
    if (!empty(trim($comment))) {
      // Insert the comment with the associated post_id
      $stmt = $db->prepare('INSERT INTO comments (username, comment, date, post_id) VALUES (?, ?, ?, ?)');
      $stmt->execute([$username, $comment, date("Y-m-d H:i:s"), $id]);

      // Redirect to prevent form resubmission
      header("Location: reply.php?id=$id");
      exit();
    } else {
      // Handle the case where the comment is empty
      echo "<script>alert('Reply cannot be empty.');</script>";
    }
  }

  // Handle comment deletion
  if (
    $_SERVER['REQUEST_METHOD'] === 'GET' &&
    isset($_GET['action']) &&
    $_GET['action'] === 'delete' &&
    isset($_GET['commentId']) && // Use commentId instead of id
    isset($id) &&
    isset($user)
  ) {
    // Delete the comment based on ID and username
    $stmt = $db->prepare('DELETE FROM comments WHERE id = ? AND username = ?');
    $stmt->execute([$_GET['commentId'], $user['username']]);

    // Redirect to prevent form resubmission
    header("Location: reply.php?id=$id");
    exit();
  }
}

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

// Fetch post information
$query = "SELECT posts.id, posts.title, posts.content, posts.user_id, posts.date, users.username, users.id AS userid FROM posts JOIN users ON posts.user_id = users.id WHERE posts.id = '$id'";
$post = $db->query($query)->fetch();

// Get comments for the current page, ordered by id in descending order
$query = "SELECT comments.id, comments.username, comments.comment, comments.date, comments.post_id, users.username AS commenter_username, users.id AS userid FROM comments JOIN users ON comments.username = users.username WHERE comments.post_id='$id' ORDER BY comments.id ASC";
$comments = $db->query($query)->fetchAll();
?>

<!DOCTYPE html>
<html lang="en" data-bs-theme="dark">
  <head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reply to <?php echo $post['title']; ?></title>
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
      <div>
        <div align="left">
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
                  <h5 class="fw-bold mb-3"><?php echo $post['title']; ?></h5>
                  <?php
                    if (!function_exists('getYouTubeVideoId')) {
                      function getYouTubeVideoId($urlCommentThread)
                      {
                        $videoIdThread = '';
                        $patternThread = '/(?:https?:\/\/)?(?:www\.)?(?:youtube\.com\/(?:[^\/\n\s]+\/\S+\/|(?:v|e(?:mbed)?)\/|\S*?[?&]v=)|youtu\.be\/)([a-zA-Z0-9_-]{11})/';
                        if (preg_match($patternThread, $urlCommentThread, $matchesThread)) {
                          $videoIdThread = $matchesThread[1];
                        }
                        return $videoIdThread;
                      }
                    }
        
                    $mainTextThread = isset($post['content']) ? $post['content'] : '';
        
                    if (!empty($mainTextThread)) {
                      $paragraphsThread = explode("\n", $mainTextThread);
        
                      foreach ($paragraphsThread as $indexThread => $paragraphThread) {
                        $textWithoutTagsThread = strip_tags($paragraphThread);
                        $patternThread = '/\bhttps?:\/\/\S+/i';
        
                        $formattedTextThread = preg_replace_callback($patternThread, function ($matchesThread) {
                          $urlThread = htmlspecialchars($matchesThread[0]);
        
                          // Check if the URL ends with .png, .jpg, .jpeg, or .webp
                          if (preg_match('/\.(png|jpg|jpeg|webp)$/i', $urlThread)) {
                            return '<a href="' . $urlThread . '" target="_blank"><img height="200px" loading="lazy" src="' . $urlThread . '" alt="Image"></a>';
                          } elseif (strpos($urlThread, 'youtube.com') !== false) {
                            // If the URL is from YouTube, embed it as an iframe with a very low-resolution thumbnail
                            $videoIdThread = getYouTubeVideoId($urlThread);
                            if ($videoIdThread) {
                              $thumbnailUrlThread = 'https://img.youtube.com/vi/' . $videoIdThread . '/default.jpg';
                              return '<div><iframe loading="lazy" allow="accelerometer; autoplay; encrypted-media; gyroscope; picture-in-picture" class="rounded-4 position-absolute top-0 bottom-0 start-0 end-0 w-100 h-100 border-0 shadow" src="https://www.youtube.com/embed/' . $videoIdThread . '" frameborder="0" allowfullscreen></iframe></div>';
                            } else {
                              return '<a href="' . $urlThread . '">' . $urlThread . '</a>';
                            }
                          } else {
                            return '<a href="' . $urlThread . '">' . $urlThread . '</a>';
                          }
                        }, $textWithoutTagsThread);
        
                        echo "<p>$formattedTextThread</p>";
                      }
                    } else {
                      echo "Sorry, no text...";
                    }
                  ?>
                </div>
                <br>
              </div>
            </div>
          </table>
        </div>
        <br><br>
        <!-- Comment form, show only if the user is logged in -->
        <?php if ($user): ?>
          <form method="post" align="center" action="reply.php?id=<?php echo $id; ?>">
            <div>
              <textarea style="width: 90%;" id="comment" name="comment" class="form-control border-2 rounded-4 focus-ring focus-ring-dark rounded-bottom-0 border-bottom-0" rows="6" onkeydown="if(event.keyCode == 13) { document.execCommand('insertHTML', false, '<br><br>'); return false; }"></textarea>
            </div>
            <br>
            <div>
              <button type="submit" class="btn w-100 btn-primary rounded-top-0 rounded-4 fw-medium">Submit</button>
            </div>
          </form>
        <?php else: ?>
          <h5 class="text-center">You must <a href="session.php">login</a> or <a href="session.php">register</a> to reply this thread!</h5>
        <?php endif; ?>
        <br><br>
        <?php foreach ($comments as $comment): ?>
          <table border="1" cellspacing="0" cellpadding="5" width="100%">
            <tr>
              <td>
                <div>
                  <?php if ($user && $comment['username'] == $user['username']): ?>
                    <div align="right">
                      <a href="reply.php?action=delete&commentId=<?php echo $comment['id']; ?>&id=<?php echo $id; ?>" style="max-height: 30px;" onclick="return confirm('Are you sure?');">delete this reply</a>
                    </div>
                  <?php endif; ?>
                  <h5>Thread by <?php echo (mb_strlen($comment['username']) > 15) ? mb_substr($comment['username'], 0, 15) . '...' : $comment['username']; ?>・<?php echo (new DateTime($comment['date']))->format("Y/m/d - H:i:s"); ?></h5>
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

                    $replyText = isset($comment['comment']) ? $comment['comment'] : '';

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

                    } else {
                      echo "Sorry, no text...";
                    }
                  ?>
                  </div>
                </div>
              </td>
            </tr>
          </table>
          <br>
        <?php endforeach; ?>
      </div>
    </div>
  </body>
</html>