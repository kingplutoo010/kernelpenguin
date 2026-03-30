<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}
$username = htmlspecialchars($_SESSION['username'], ENT_QUOTES, 'UTF-8');
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Dashboard - Feature Hub</title>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap" rel="stylesheet" />
  <script src="https://kit.fontawesome.com/a076d05399.js" crossorigin="anonymous"></script>
  <style>
    * {
      box-sizing: border-box;
    }
    body {
      font-family: 'Poppins', Arial, sans-serif;
      background: linear-gradient(135deg, #1f1b1b, #3a2f97);
      color: #f0f0f0;
      margin: 0;
      padding: 20px;
      min-height: 100vh;
      display: flex;
      flex-direction: column;
      align-items: center;
    }
    header {
      text-align: center;
      margin-bottom: 30px;
      max-width: 600px;
      width: 100%;
    }
    header h1 {
      font-weight: 600;
      font-size: 2.4rem;
      margin-bottom: 5px;
    }
    header p {
      color: #bdbdbd;
      font-size: 1rem;
      margin-top: 0;
    }
    .features-grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
      gap: 24px;
      width: 100%;
      max-width: 960px;
    }
    .feature-card {
      background: #2c255c;
      border-radius: 16px;
      box-shadow: 0 6px 25px rgba(0, 0, 0, 0.6);
      padding: 30px 20px;
      display: flex;
      flex-direction: column;
      align-items: center;
      transition: transform 0.3s ease, box-shadow 0.3s ease;
      cursor: pointer;
    }
    .feature-card:hover {
      transform: translateY(-6px);
      box-shadow: 0 12px 40px rgba(94, 79, 191, 0.8);
    }
    .feature-icon {
      font-size: 48px;
      margin-bottom: 20px;
      color: #9575cd;
    }
    .feature-title {
      font-size: 1.5rem;
      font-weight: 600;
      margin-bottom: 12px;
      text-align: center;
    }
    .feature-desc {
      font-size: 1rem;
      color: #c0b9e8;
      margin-bottom: 25px;
      text-align: center;
    }
    .feature-button {
      background: #7e57c20;
      color: white;
      border: none;
      padding: 12px 28px;
      font-size: 1.1rem;
      border-radius: 30px;
      cursor: pointer;
      box-shadow: 0 5px 15px rgba(126, 87, 194, 0.4);
      transition: background 0.3s ease, box-shadow 0.3s ease;
      user-select: none;
      text-decoration: none;
      display: inline-block;
    }
    .feature-button:hover {
      background: #9575cd;
      box-shadow: 0 8px 20px rgba(149, 117, 205, 0.6);
    }
    .logout-button {
      position: fixed;
      bottom: 20px;
      right: 20px;
      background: #ef5350;
      border: none;
      padding: 14px 24px;
      border-radius: 30px;
      font-weight: 600;
      font-size: 1rem;
      color: white;
      cursor: pointer;
      box-shadow: 0 5px 15px rgba(239, 83, 80, 0.4);
      transition: background 0.3s ease, box-shadow 0.3s ease;
      user-select: none;
    }
    .logout-button:hover {
      background: #ef9a9a;
      box-shadow: 0 8px 20px rgba(239, 154, 154, 0.6);
    }
  </style>
</head>
<body>
  <header>
    <h1>Welcome, <?php echo $username; ?>!</h1>
    <p><?php echo "Today is " . date('l, F j, Y'); ?></p>
  </header>

  <main class="features-grid">
    <!-- Sticky Notes -->
    <div class="feature-card" onclick="location.href='notes.php'">
      <div class="feature-icon"><i class="fas fa-sticky-note"></i></div>
      <div class="feature-title">Sticky Notes</div>
      <div class="feature-desc">Create and manage colorful sticky notes quickly.</div>
      <button class="feature-button" onclick="event.stopPropagation(); window.location.href='notes.php'">Open</button>
    </div>

    <!-- Calendar -->
    <div class="feature-card" onclick="location.href='ai_browser.php'">
        <div class="feature-icon"><i class="fas fa-robot"></i></div> <div class="feature-title">AI Browser</div> <div class="feature-desc">Engage with an AI-powered Browse experience.</div> <button class="feature-button" onclick="event.stopPropagation(); window.location.href='ai_browser.php'">Explore</button>
      </div>

    <!-- Task Manager -->
    <div class="feature-card" onclick="location.href='tasks.php'">
      <div class="feature-icon"><i class="fas fa-tasks"></i></div>
      <div class="feature-title">Task Manager</div>
      <div class="feature-desc">Track tasks with countdowns and stress meters.</div>
      <button class="feature-button" onclick="event.stopPropagation(); window.location.href='tasks.php'">Open</button>
    </div>

    <!-- Analytics -->
    <div class="feature-card" onclick="alert('Feature coming soon!')">
      <div class="feature-icon"><i class="fas fa-chart-line"></i></div>
      <div class="feature-title">Analytics</div>
      <div class="feature-desc">Get insights and reports of your activities.</div>
      <button class="feature-button" onclick="event.stopPropagation(); alert('Feature coming soon!')">Explore</button>
    </div>
  </main>

  <!-- Logout Button -->
  <form action="logout.php" method="POST" style="display:inline;">
    <button class="logout-button" type="submit" aria-label="Logout">Logout</button>
  </form>
</body>
</html>
