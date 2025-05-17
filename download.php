<?php
session_start();
include('config.php');

if (isset($_GET['getFileSize'])) {
    $file = urldecode($_GET['getFileSize']);
    $filePath = __DIR__ . '/notes/' . basename($file); // adjust path

    if (file_exists($filePath)) {
        echo json_encode([
            'success' => true,
            'size' => filesize($filePath)
        ]);
    } else {
        echo json_encode(['success' => false]);
    }
    exit;
}
// Like handler
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['like_note_id'])) {
    $noteId = intval($_POST['like_note_id']);
    $update = $dbh->prepare("UPDATE tblnotes SET Likes = Likes + 1 WHERE ID = :noteId");
    $update->execute([':noteId' => $noteId]);
    echo json_encode(['status' => 'success']);
    exit;
}

// Comment handler
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['comment_note_id'])) {
    if (!isset($_SESSION['ocasuid'])) {
        echo json_encode(['status' => 'error', 'message' => 'You must be logged in to comment.']);
        exit;
    }

    $noteId = intval($_POST['comment_note_id']);
    $userId = $_SESSION['ocasuid'];
    $commentText = trim($_POST['comment_text']);

    if ($commentText == '') {
        echo json_encode(['status' => 'error', 'message' => 'Comment cannot be empty.']);
        exit;
    }

    $stmt = $dbh->prepare("INSERT INTO tblcomments (NoteID, UserID, CommentText) VALUES (:noteId, :userId, :comment)");
    $stmt->execute([
        ':noteId' => $noteId,
        ':userId' => $userId,
        ':comment' => $commentText
    ]);

    echo json_encode(['status' => 'success']);
    exit;
}

// Download handler
if (isset($_GET['download']) && isset($_GET['name'])) {
    $hashedFile = basename($_GET['download']);
    $cleanTitle = preg_replace('/[^a-zA-Z0-9_\- ]/', '', $_GET['name']);
    $filePath = __DIR__ . "/notes/" . $hashedFile;

    if (file_exists($filePath)) {
        $extension = pathinfo($hashedFile, PATHINFO_EXTENSION);
        $finalName = $cleanTitle . '.' . $extension;

        header('Content-Description: File Transfer');
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="' . $finalName . '"');
        header('Expires: 0');
        header('Cache-Control: must-revalidate');
        header('Pragma: public');
        header('Content-Length: ' . filesize($filePath));
        readfile($filePath);
        exit;
    } else {
        echo "<script>alert('File not found');</script>";
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>NotesShare</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" href="includes/logo.png" type="image/png">
    <link rel="manifest" href="manifest.php?manifest">
    <link href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet" />
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.2.0/css/all.min.css" />
    <style>
        body { background: #f4f4f4; font-family: Arial; }
        .container { max-width: 900px; margin: auto; background: #fff; padding: 20px; border-radius: 10px; box-shadow: 0 4px 10px rgba(0,0,0,0.1); margin-top: 40px; }
        .header { background: #003366; color: gold; padding: 15px; text-align: center; border-radius: 8px 8px 0 0; }
        .search-bar { width: 100%; padding: 12px; margin: 20px 0; background: #003366; color: gold; border: 1px solid #003366; border-radius: 5px; }
        ::placeholder { color: white; }
        .note-item { background: #fff; border: 1px solid #ddd; margin-bottom: 20px; padding: 15px; border-radius: 6px; }
        .note-title { font-weight: bold; font-size: 18px; }
        .meta { font-size: 14px; color: #666; margin-top: 5px; }
        .actions { display: flex; justify-content: space-between; align-items: center; gap: 20px; }
        .interaction-buttons { display: flex; gap: 10px; }
        .download-btn { background: #003366; color: white; padding: 8px 14px; border-radius: 4px; border: none; display: inline-flex; align-items: center; gap: 5px; }
        .like-btn, .comment-btn { background: #003366; color: white; padding: 8px 14px; border-radius: 4px; border: none; display: inline-flex; align-items: center; gap: 5px; }
        .like-btn:hover, .comment-btn:hover { background: #FFD700; cursor: pointer; }
        .like-count, .comment-count { background-color: #FFD700; color: #003366; padding: 5px 10px; border-radius: 3px; margin-left: 10px; font-weight: bold; }
        .actions .like-count, .actions .comment-count { background-color: #003366; color: #FFD700; }
        .comment-box { margin-top: 15px; }
        .comment-box textarea { width: 100%; padding: 8px; border: 1px solid #ccc; border-radius: 5px; resize: none; }
        .comment-box button { margin-top: 8px; background: gold; color: rgb(93, 102, 112); padding: 6px 12px; border: none; border-radius: 5px; }
        .comments-list { margin-top: 10px; padding-left: 15px; border-left: 3px solid #003366; }
        .comment { margin-bottom: 10px; }
        .comment small { color: #888; font-size: 12px; }
        .initial-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background-color: #003366;
            color: #FFD700;
            font-weight: bold;
            font-size: 18px;
            display: flex;
            align-items: center;
            justify-content: center;
            text-transform: uppercase;
            margin-right: 10px;
        }
        .comment-header {
            display: flex;
            align-items: center;
            margin-bottom: 5px;
        }
        .comment-bubble {
    background: #f0f4ff;
    padding: 12px 15px;
    border-radius: 10px;
    position: relative;
    box-shadow: 0 2px 5px rgba(0,0,0,0.1);
    margin-left: 55px;
    margin-top: 5px;
    margin-bottom: 15px;
}

.comment-bubble::before {
    content: "";
    position: absolute;
    left: -10px;
    top: 15px;
    border-width: 10px;
    border-style: solid;
    border-color: transparent #f0f4ff transparent transparent;
}

.comment-text {
    font-size: 15px;
    color: #333;
    margin-bottom: 8px;
}

.comment-meta {
    font-size: 12px;
    color: #888;
    display: flex;
    align-items: center;
    gap: 6px;
}

    .swal2-html-container strong {
        font-size: 1.2rem;
        color: #003366 !important;
    }
/* Style the SweetAlert to match your brand */
.swal2-popup {
  border: 1px solid gold !important;
}

.swal2-title, .swal2-content {
  color: gold !important;
}

.swal2-checkbox label {
  color: gold !important;
  cursor: pointer;
}

.swal2-checkbox input {
  margin-right: 8px;
}

    </style>
</head>
<script>
  let deferredPrompt = null;
  let installPromptShown = false;
  let apkFallbackShown = false;
  const PROMPT_COOLDOWN = 24 * 60 * 60 * 1000; // 24 hours cooldown

  // Check if prompt is in cooldown
  function isPromptInCooldown() {
    const lastPromptTime = localStorage.getItem('lastPromptTime');
    if (!lastPromptTime) return false;
    return (Date.now() - parseInt(lastPromptTime)) < PROMPT_COOLDOWN;
  }

  // Set cooldown timestamp
  function setPromptCooldown() {
    localStorage.setItem('lastPromptTime', Date.now().toString());
  }

  // Listen for PWA install prompt event
  window.addEventListener('beforeinstallprompt', (e) => {
    if (isPromptInCooldown()) {
      console.log('Prompt in cooldown - skipping');
      return;
    }
    
    console.log('PWA install prompt available');
    e.preventDefault();
    deferredPrompt = e;
    installPromptShown = true;
    
    showPWAInstallPrompt();
  });

  // Show PWA install prompt
  function showPWAInstallPrompt() {
    if (window.matchMedia('(display-mode: standalone)').matches) {
      console.log('Already installed - skipping prompt');
      return;
    }

    if (!deferredPrompt || isPromptInCooldown()) {
      console.log('No install prompt available or in cooldown');
      return;
    }

    console.log('Showing install prompt dialog');
    Swal.fire({
      title: 'Install NotesShare App',
      html: `Add NotesShare to your home screen for better experience`,
      icon: 'info',
      showCancelButton: true,
      confirmButtonText: 'Install',
      cancelButtonText: 'Not Now',
      confirmButtonColor: '#FFD700',
      cancelButtonColor: '#6c757d',
      background: '#003366',
      color: '#fff'
    }).then((result) => {
      if (result.isConfirmed) {
        console.log('User accepted install - showing browser prompt');
        deferredPrompt.prompt();
        
        deferredPrompt.userChoice.then((choiceResult) => {
          setPromptCooldown(); // Set cooldown regardless of choice
          if (choiceResult.outcome === 'accepted') {
            console.log('User completed installation');
          } else {
            console.log('User declined installation');
          }
          deferredPrompt = null;
        });
      } else {
        console.log('User postponed installation');
        setPromptCooldown();
      }
    });
  }

  // Show prompt after page loads if not shown automatically
  window.addEventListener('load', () => {
    if (isPromptInCooldown()) {
      console.log('Prompt in cooldown - not showing');
      return;
    }

    setTimeout(() => {
      if (!installPromptShown && !window.matchMedia('(display-mode: standalone)').matches) {
        console.log('Showing delayed install prompt');
        showPWAInstallPrompt();
      }
    }, 5000); // 5 second delay
  });

  // Rest of your existing code...
  // (keep all your other functions like showDelayedAPKFallback, showAPKDownloadOption, etc.)
</script>
<body>

<?php include('includes/navbar.php'); ?>

<div class="container">
<?php
if (!isset($_SESSION['ocasuid'])) {
    // No session active
    ?>
    <div class="header">
        <h2 style="color: gold; font-weight: bold;">Available Notes</h2>
    </div>
    <?php
} else {
    $uid = $_SESSION['ocasuid'];
    $sql = "SELECT * FROM registered_users WHERE id = :uid";
    $query = $dbh->prepare($sql);
    $query->bindParam(':uid', $uid, PDO::PARAM_STR);
    $query->execute();
    $user = $query->fetch(PDO::FETCH_OBJ);

    if (!$user) {
        // Session set, but user not in DB
        ?>
        <div class="header">
            <h2 style="color: #003366; font-weight: bold;">Notes</h2>
        </div>
        <?php
    } else {
        // Valid user, show link
        ?>
        <div style="text-align: center; margin-top: 20px;">
            <a href="dashboard.php" 
               style="
                   background-color: #FFD700;
                   color: #003366;
                   padding: 12px 25px;
                   text-decoration: none;
                   font-weight: bold;
                   border-radius: 30px;
                   font-size: 16px;
                   transition: 0.3s ease-in-out;
                   box-shadow: 0 4px 6px rgba(0,0,0,0.1);
               "
               onmouseover="this.style.backgroundColor='#003366'; this.style.color='white';"
               onmouseout="this.style.backgroundColor='#FFD700'; this.style.color='#003366';">
                My Notes
            </a>
        </div>
        <?php
    }
}
?>
<form method="post" id="filterForm" style="display: flex; align-items: center; gap: 8px; width: 100%;">
  <input type="text" id="myInput" class="search-bar" placeholder="Search Notes..." style="flex-grow: 1; padding: 12px;">
  <button type="button" id="filterButton" style="background: none; border: none; cursor: pointer; display: flex; align-items: center;">
    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="gold" stroke-width="2">
      <path stroke-linecap="round" stroke-linejoin="round" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"/>
    </svg>
  </button>
  <input type="hidden" name="filter_types" id="selectedFilters" value="book,notes,papers,other">
</form>

    <?php
// Get selected filters
$selectedTypes = isset($_POST['filter_types']) 
    ? explode(',', $_POST['filter_types']) 
    : ['book', 'notes', 'papers', 'other']; // Default to all

// Get search term if exists
$searchTerm = $_POST['search_term'] ?? '';

// Build query
$sql = "SELECT n.*, u.name FROM tblnotes n 
        LEFT JOIN registered_users u ON n.UserID = u.ID 
        WHERE n.Status = 'Approved'";

// Add filters
if (count($selectedTypes) > 0) {
    $placeholders = implode(',', array_fill(0, count($selectedTypes), '?'));
    $sql .= " AND n.NotesType IN ($placeholders)";
}

// Add search if exists
if (!empty($searchTerm)) {
    $sql .= " AND (n.Title LIKE ? OR n.Description LIKE ?)";
}

$sql .= " ORDER BY n.CreationDate DESC";

// Prepare and execute
$query = $dbh->prepare($sql);
$paramIndex = 1;

// Bind filter parameters
if (count($selectedTypes) > 0) {
    foreach ($selectedTypes as $type) {
        $query->bindValue($paramIndex++, $type, PDO::PARAM_STR);
    }
}

// Bind search parameters
if (!empty($searchTerm)) {
    $searchParam = "%$searchTerm%";
    $query->bindValue($paramIndex++, $searchParam, PDO::PARAM_STR);
    $query->bindValue($paramIndex++, $searchParam, PDO::PARAM_STR);
}

$query->execute();
$notes = $query->fetchAll(PDO::FETCH_ASSOC);
   foreach ($notes as $note):
        $noteId = $note['ID'];
        $comments = $dbh->prepare("SELECT c.CommentText, c.CommentDate, ru.name FROM tblcomments c 
                                   LEFT JOIN registered_users ru ON ru.ID = c.UserID 
                                   WHERE c.NoteID = :nid ORDER BY c.CommentDate DESC");
        $comments->execute([':nid' => $noteId]);
        $commentsList = $comments->fetchAll(PDO::FETCH_ASSOC);
    ?>
    <div class="note-item"  data-note-id="<?= $noteId ?>">
    <!-- Metadata Line -->
<div style="
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  gap: 0.5rem;
  font-size: 0.875rem;
  color: #003366;
  font-weight: 500;
">
  <span><?= htmlspecialchars($note['name']) ?></span>
  <span>posted</span>
  <span><?= htmlspecialchars($note['Subject']) ?> materials</span>
  <span>•</span>
  <span class="time-ago" data-time="<?= date('Y-m-d H:i:s', strtotime($note['CreationDate'])) ?>">
    <?= date('F d, Y', strtotime($note['CreationDate'])) ?>
  </span>
</div>

       <div class="note-title" style="color: #FFD700; font-weight: bold;">
    <?= htmlspecialchars($note['NotesTitle']) ?>
</div>
<div style="
  display: flex !important;
  flex-direction: column !important;
  gap: 0.5rem !important;
  margin-bottom: 1.5rem !important;
  font-family: Arial, sans-serif !important;
">
  <!-- Notes Description -->
  <?php if (!empty($note['NotesDecription'])): ?>
  <div style="
    color: #003366 !important;
    line-height: 1.5 !important;
    font-size: 1rem !important;
  ">
    <?= nl2br(htmlspecialchars($note['NotesDecription'])) ?>
  </div>
  <?php endif; ?>
</div>


        <div class="actions">
            <a class="download-btn" href="#" onclick="triggerDownload('<?= htmlspecialchars($note['File1']) ?>', '<?= htmlspecialchars($note['NotesTitle']) ?>')">
                <i class="fa fa-download"></i> Download
            </a>

            <div class="interaction-buttons">
                <button class="like-btn" data-id="<?= $noteId ?>">
                    <i class="fa fa-heart"></i> <?= $note['Likes'] ?>
                </button>
                <button class="comment-btn" data-id="<?= $noteId ?>">
                    <i class="fa fa-comment"></i> <?= count($commentsList) ?>
                </button>
            </div>
        </div>

        <div class="comment-box mt-4">
            <h5 class="mb-3">Top Comments</h5>

            <div class="comments-list">
                <?php if (count($commentsList) > 0): ?>
                    <?php foreach ($commentsList as $c): 
                        $name = htmlspecialchars($c['name']);
                        $nameParts = preg_split('/\s+/', trim($name));
                        $initials = '';
                        foreach ($nameParts as $part) {
                            $initials .= strtoupper($part[0]);
                        }
                    ?>
                    <div class="mb-3">
                       
                        <div class="comment-bubble">
                        <div class="comment-header">
                            <div class="initial-avatar"><?= $initials ?></div>
                            <strong><?= $name ?></strong>
                        </div>
    <p class="comment-text"><?= htmlspecialchars($c['CommentText']) ?></p>
    <div class="comment-meta">
        <i class="fa fa-clock"></i>
      <span class="time-ago" data-time="<?= date("Y-m-d H:i:s", strtotime($c['CommentDate'])) ?>">
  <?= date("M d, Y • H:i", strtotime($c['CommentDate'])) ?>
</span>

    </div>
</div>

                    </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p class="text-muted"><i>No comments yet. Be the first to comment!</i></p>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<!-- Include SweetAlert2 CSS/JS -->
<link href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
document.getElementById('filterButton').addEventListener('click', function() {
  // Get current selections from hidden input
  const currentFilters = document.getElementById('selectedFilters').value.split(',');
  
  Swal.fire({
    title: 'Filter Notes',
    html: `
      <div style="text-align:left; margin:15px 0">
        <div style="margin-bottom:10px">
          <input type="checkbox" id="swal-book" ${currentFilters.includes('book') ? 'checked' : ''}>
          <label for="swal-book" style="margin-left:8px">Books</label>
        </div>
        <div style="margin-bottom:10px">
          <input type="checkbox" id="swal-notes" ${currentFilters.includes('notes') ? 'checked' : ''}>
          <label for="swal-notes" style="margin-left:8px">Notes</label>
        </div>
        <div style="margin-bottom:10px">
          <input type="checkbox" id="swal-papers" ${currentFilters.includes('papers') ? 'checked' : ''}>
          <label for="swal-papers" style="margin-left:8px">Past Papers</label>
        </div>
        <div>
          <input type="checkbox" id="swal-other" ${currentFilters.includes('other') ? 'checked' : ''}>
          <label for="swal-other" style="margin-left:8px">Others</label>
        </div>
      </div>
    `,
    background: '#003366',
    color: 'gold',
    confirmButtonColor: 'gold',
    confirmButtonText: 'Apply Filters',
    showCancelButton: true,
    cancelButtonColor: '#666',
    focusConfirm: false,
    preConfirm: () => {
      return [
        document.getElementById('swal-book').checked ? 'book' : null,
        document.getElementById('swal-notes').checked ? 'notes' : null,
        document.getElementById('swal-papers').checked ? 'papers' : null,
        document.getElementById('swal-other').checked ? 'other' : null
      ].filter(Boolean); // Remove null values
    }
  }).then((result) => {
    if (result.isConfirmed && result.value) {
      // Update hidden input with selections
      document.getElementById('selectedFilters').value = result.value.join(',');
      
      // Create a temporary form element to submit
      const form = document.createElement('form');
      form.method = 'post';
      form.action = '';
      
      // Add the filter types input
      const input = document.createElement('input');
      input.type = 'hidden';
      input.name = 'filter_types';
      input.value = result.value.join(',');
      form.appendChild(input);
      
      // Add any existing search term
      const searchInput = document.getElementById('myInput');
      if (searchInput.value) {
        const searchTerm = document.createElement('input');
        searchTerm.type = 'hidden';
        searchTerm.name = 'search_term';
        searchTerm.value = searchInput.value;
        form.appendChild(searchTerm);
      }
      
      // Submit the form
      document.body.appendChild(form);
      form.submit();
    }
  });
});
</script>
<script>
function triggerDownload(file, title) {
    fetch(`?getFileSize=${encodeURIComponent(file)}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const fileSize = formatFileSize(data.size);

                Swal.fire({
                    title: "Thanks for Downloading!",
                    html: `<strong>${title}</strong><br>File size: <span style="color: #003366;">${fileSize}</span><br>Your download will start shortly.`,
                    icon: "success",
                    confirmButtonColor: "#003366",
                    confirmButtonText: "Download Now"
                }).then((result) => {
                    if (result.isConfirmed) {
                        const downloadUrl = `?download=${encodeURIComponent(file)}&name=${encodeURIComponent(title)}`;
                        window.location.href = downloadUrl;
                    }
                });

            } else {
                Swal.fire({
                    title: "Error!",
                    text: "Could not retrieve file size.",
                    icon: "error",
                    confirmButtonColor: "#003366"
                });
            }
        });
}

function formatFileSize(bytes) {
    const sizes = ['Bytes', 'KB', 'MB', 'GB'];
    if (bytes === 0) return '0 Byte';
    const i = parseInt(Math.floor(Math.log(bytes) / Math.log(1024)));
    return (bytes / Math.pow(1024, i)).toFixed(2) + ' ' + sizes[i];
}


// Search filter
$("#myInput").on("keyup", function () {
    let value = $(this).val().toLowerCase();
    $(".note-item").filter(function () {
        $(this).toggle($(this).text().toLowerCase().includes(value));
    });
});

// Like button
$(".like-btn").click(function () {
    const btn = $(this);
    const noteId = btn.data("id");

    $.post("", { like_note_id: noteId }, function (data) {
        if (data.status === "success") {
            Swal.fire({
                icon: "success",
                title: "Liked!",
                text: "You liked this note.",
                confirmButtonColor: "#003366"
            }).then(() => {
                location.reload(); // reload to update like count
            });
        } else {
            Swal.fire({
                icon: "error",
                title: "Error!",
                text: "Unable to like the note.",
                confirmButtonColor: "#003366"
            });
        }
    }, 'json');
});

// Comment button
$(".comment-btn").click(function () {
    const noteId = $(this).data("id");

    Swal.fire({
        title: 'Leave a comment',
        input: 'textarea',
        inputPlaceholder: 'Type your comment here...',
        showCancelButton: true,
        confirmButtonColor: '#003366',
        cancelButtonColor: '#888',
        confirmButtonText: 'Comment',
        preConfirm: (text) => {
            if (!text.trim()) {
                Swal.showValidationMessage('Comment cannot be empty');
            }
        }
    }).then((result) => {
        if (result.isConfirmed && result.value.trim()) {
            $.post("", {
                comment_note_id: noteId,
                comment_text: result.value.trim()
            }, function (data) {
                if (data.status === "success") {
                    Swal.fire({
                        icon: "success",
                        title: "Commented!",
                        text: "Your comment was submitted.",
                        confirmButtonColor: "#003366"
                    }).then(() => location.reload());
                } else {
                    Swal.fire({
                        icon: "error",
                        title: "Oops!",
                        text: data.message || "Failed to submit comment.",
                        confirmButtonColor: "#003366"
                    });
                }
            }, "json");
        }
    });
});
</script>
<script>
function timeAgo(dateString) {
  const now = new Date();
  const date = new Date(dateString);
  const seconds = Math.floor((now - date) / 1000);

  const intervals = [
    { label: 'year', seconds: 31536000 },
    { label: 'month', seconds: 2592000 },
    { label: 'week', seconds: 604800 },
    { label: 'day', seconds: 86400 },
    { label: 'hour', seconds: 3600 },
    { label: 'minute', seconds: 60 },
    { label: 'second', seconds: 1 }
  ];

  for (const interval of intervals) {
    const count = Math.floor(seconds / interval.seconds);
    if (count >= 1) {
      return count === 1
        ? `1 ${interval.label} ago`
        : `${count} ${interval.label}s ago`;
    }
  }
  return 'just now';
}

document.addEventListener('DOMContentLoaded', () => {
  document.querySelectorAll('.time-ago').forEach(span => {
    const timeString = span.getAttribute('data-time');
    span.textContent = timeAgo(timeString);
  });
});
</script>


</body>
</html>
