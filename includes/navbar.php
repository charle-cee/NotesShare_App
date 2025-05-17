<!-- navbar.html -->
<header>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link rel="icon" href="includes/logo.png" type="image/png">
  <link href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet" />
  
  <h1 style="margin: 0 !important;">NotesShare</h1>
  
  <nav>
    <?php
    session_start();
    include_once('config.php');

    if (!isset($_SESSION['ocasuid'])) {
      // No session active
      echo '<a href="login.php">Login</a>';
    } else {
      $uid = $_SESSION['ocasuid'];
      $sql = "SELECT * FROM registered_users WHERE id=:uid";
      $query = $dbh->prepare($sql);
      $query->bindParam(':uid', $uid, PDO::PARAM_STR);
      $query->execute();
      $results = $query->fetchAll(PDO::FETCH_OBJ);
      if ($query->rowCount() === 0) {
        // Session set, but user not in DB
        echo '<a href="login.php">Login</a>';
      }
    }
    ?>
    <a href="download.php">Notes</a>
  </nav>
</header>
<style>
  header {
    background-color: #003366;
    color: white;
    padding: 1rem 2rem;
    display: flex;
    justify-content: space-between;
    align-items: center;
    position: sticky;
    top: 0;
    z-index: 1000;
  }

  header h1 {
    font-size: 1.5rem;
  }

  nav a {
    color: #FFD700;
    margin-left: 20px;
    text-decoration: none;
    font-weight: bold;
  }
</style>
