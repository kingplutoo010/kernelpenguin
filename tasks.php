<?php
// Temporary debugging lines (remove in production)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
session_regenerate_id(true);

// Set timezone to prevent warnings
date_default_timezone_set('UTC'); // Adjust as needed, e.g., 'Asia/Kolkata'

if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

$username = htmlspecialchars($_SESSION['username'], ENT_QUOTES, 'UTF-8');

// Check if pdo_sqlite extension is available
if (!extension_loaded('pdo_sqlite')) {
    error_log("pdo_sqlite extension is not loaded.");
    die("Server configuration error: SQLite support is missing. Please contact support.");
}

// Initialize SQLite database
try {
    $db_path = __DIR__ . '/database.db';
    // Check if the directory is writable
    if (!is_writable(__DIR__)) {
        error_log("Directory is not writable: " . __DIR__);
        die("Server configuration error: Directory permissions issue. Please contact support.");
    }
    // Check if the database file exists and is writable, or if it can be created
    if (file_exists($db_path) && !is_writable($db_path)) {
        error_log("Database file is not writable: $db_path");
        die("Server configuration error: Database file permissions issue. Please contact support.");
    }
    $db = new PDO('sqlite:' . $db_path);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    error_log("Database connection failed: " . $e->getMessage());
    die("A database connection error occurred. Please contact support.");
}

// Create tasks table and index
try {
    $db->exec("
        CREATE TABLE IF NOT EXISTS tasks (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            user_id INTEGER NOT NULL,
            task_name TEXT NOT NULL,
            task_description TEXT,
            due_date TEXT NOT NULL
        )
    ");
    $db->exec("CREATE INDEX IF NOT EXISTS idx_user_due ON tasks (user_id, due_date)");
} catch (PDOException $e) {
    error_log("Failed to create tasks table: " . $e->getMessage());
    die("A database setup error occurred. Please contact support.");
}

// Handle task deletion
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    try {
        $task_id = $_GET['delete'];
        $stmt = $db->prepare("DELETE FROM tasks WHERE id = :id AND user_id = :user_id");
        $stmt->execute([':id' => $task_id, ':user_id' => $_SESSION['user_id']]);
        header("Location: tasks.php");
        exit();
    } catch (PDOException $e) {
        error_log("Failed to delete task: " . $e->getMessage());
        die("An error occurred while deleting the task. Please try again.");
    }
}

// Handle form submission
$error = '';
$success = '';
$show_add_task_modal = false;
$task_name_val = '';
$task_description_val = '';
$due_date_val = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $task_name_val = trim($_POST['task_name']);
    $task_description_val = trim($_POST['task_description']);
    $due_date_val = trim($_POST['due_date']);

    $today = new DateTime();
    $today->setTime(0, 0, 0);
    $input_date = DateTime::createFromFormat('Y-m-d', $due_date_val, new DateTimeZone('UTC'));

    if (!$input_date || $input_date->format('Y-m-d') !== $due_date_val) {
        $error = "Invalid date format.";
        $show_add_task_modal = true;
    } elseif ($input_date < $today) {
        $error = "Please select a valid future date.";
        $show_add_task_modal = true;
    } elseif (empty($task_name_val)) {
        $error = "Task name is required.";
        $show_add_task_modal = true;
    } else {
        try {
            $stmt = $db->prepare("INSERT INTO tasks (user_id, task_name, task_description, due_date) VALUES (:user_id, :task_name, :task_description, :due_date)");
            $stmt->execute([
                ':user_id' => $_SESSION['user_id'],
                ':task_name' => $task_name_val,
                ':task_description' => $task_description_val,
                ':due_date' => $due_date_val
            ]);
            header("Location: tasks.php?success=1");
            exit();
        } catch (PDOException $e) {
            $error = "Failed to add task. Please try again.";
            error_log("Failed to add task for user " . $_SESSION['user_id'] . ": " . $e->getMessage());
            $show_add_task_modal = true;
        }
    }
}

if (isset($_GET['success']) && $_GET['success'] == 1) {
    $success = "Task added successfully!";
}

// Fetch tasks with pagination
$per_page = 20; // Reduced for better scalability
$page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $per_page;

try {
    $stmt = $db->prepare("SELECT * FROM tasks WHERE user_id = :user_id ORDER BY due_date ASC LIMIT :limit OFFSET :offset");
    $stmt->bindValue(':user_id', $_SESSION['user_id'], PDO::PARAM_INT);
    $stmt->bindValue(':limit', $per_page, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    $tasks = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Failed to fetch tasks: " . $e->getMessage());
    die("An error occurred while fetching tasks. Please try again.");
}

try {
    $stmt = $db->prepare("SELECT COUNT(*) FROM tasks WHERE user_id = :user_id");
    $stmt->execute([':user_id' => $_SESSION['user_id']]);
    $total_tasks = $stmt->fetchColumn();
    $total_pages = ceil($total_tasks / $per_page);
} catch (PDOException $e) {
    error_log("Failed to count tasks: " . $e->getMessage());
    die("An error occurred while counting tasks. Please try again.");
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Focus Flow - Task Manager</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700&display=swap" rel="stylesheet" />
    <script src="https://kit.fontawesome.com/a076d05399.js" crossorigin="anonymous"></script>
    <style>
        :root {
            --bg-dark: #121212;
            --bg-card: #1f1f1f;
            --bg-input: #2a2a2a;
            --text-light: #e0e0e0;
            --text-mid: #b0b0b0;
            --text-dark: #a0a0a0; /* Improved contrast */
            --accent-teal: #00bcd4;
            --accent-teal-dark: #00acc1;
            --status-overdue: #ef5350;
            --status-soon: #ffb300;
            --status-far: #66bb6a;
            --stress-low: #66bb6a;
            --stress-medium: #ffc107;
            --stress-high: #ef5350;
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }
        body {
            font-family: 'Inter', sans-serif;
            background-color: var(--bg-dark);
            color: var(--text-light);
            min-height: 100vh;
            padding: 20px;
            display: flex;
            flex-direction: column;
            align-items: center;
            overflow-x: hidden;
        }

        header {
            position: absolute;
            top: 20px;
            left: 20px;
            text-align: left;
            animation: fadeIn 1s ease-out;
            z-index: 10;
        }
        header h1 {
            font-weight: 300;
            font-size: 1.6rem;
            margin-bottom: 5px;
            color: var(--text-light);
        }
        header p {
            color: var(--text-mid);
            font-size: 0.85rem;
        }

        main {
            width: 100%;
            max-width: 1200px;
            margin-top: 80px;
            padding: 0 35px;
            display: flex;
            flex-direction: column;
            align-items: center;
        }

        .message {
            text-align: center;
            font-size: 0.9rem;
            padding: 12px 18px;
            margin: 20px auto;
            border-radius: 10px;
            border: 1px solid transparent;
            max-width: 600px;
            animation: fadeIn 0.5s ease-out;
        }
        .message.error {
            color: var(--status-overdue);
            background-color: rgba(239, 83, 80, 0.15);
            border-color: var(--status-overdue);
        }
        .message.success {
            color: var(--accent-teal);
            background-color: rgba(0, 188, 212, 0.15);
            border-color: var(--accent-teal);
        }

        .tasks-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
            gap: 30px;
            width: 100%;
            justify-content: center;
            margin-top: 0;
        }
        .tasks-grid + p {
            grid-column: 1 / -1;
            text-align: center;
            color: var(--text-dark);
            margin-top: 50px;
        }

        .task-card {
            background-color: var(--bg-card);
            border-radius: 12px;
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.4);
            padding: 30px;
            display: flex;
            flex-direction: column;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            animation: cardFadeIn 0.5s ease-out;
            border: none;
            position: relative;
            min-height: 260px;
        }
        .task-card:hover {
            transform: translateY(-7px);
            box-shadow: 0 12px 35px rgba(0, 0, 0, 0.5);
        }

        .urgency-dot {
            position: absolute;
            top: 20px;
            right: 20px;
            width: 15px;
            height: 15px;
            border-radius: 50%;
            background-color: var(--text-dark);
            box-shadow: 0 0 6px rgba(0,0,0,0.3);
        }
        .task-card.overdue .urgency-dot { background-color: var(--status-overdue); box-shadow: 0 0 10px var(--status-overdue); }
        .task-card.soon .urgency-dot { background-color: var(--status-soon); box-shadow: 0 0 10px var(--status-soon); }
        .task-card.far .urgency-dot { background-color: var(--status-far); box-shadow: 0 0 10px var(--status-far); }

        .task-card .task-title {
            font-size: 1.3rem;
            font-weight: 600;
            margin-bottom: 10px;
            color: var(--text-light);
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .task-card .task-description {
            font-size: 0.95rem;
            color: var(--text-mid);
            margin-bottom: 15px;
            line-height: 1.6;
            max-height: 9.6em;
            overflow: hidden;
            display: -webkit-box;
            -webkit-line-clamp: 6;
            -webkit-box-orient: vertical;
        }
        .task-card .due-date-label {
            font-size: 0.9rem;
            color: var(--text-dark);
            margin-bottom: 5px;
        }
        .task-card .due-date-display {
            font-size: 1rem;
            font-weight: 400;
            color: var(--text-light);
            margin-bottom: 15px;
        }
        .task-card .countdown {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--accent-teal);
            margin-bottom: 25px;
            text-shadow: 0 0 10px rgba(0, 188, 212, 0.5);
        }
        .task-card.overdue .countdown { color: var(--status-overdue); text-shadow: 0 0 10px rgba(239, 83, 80, 0.5); }
        .task-card.soon .countdown { color: var(--status-soon); text-shadow: 0 0 10px rgba(255, 179, 0, 0.5); }

        .stress-meter {
            width: 100%;
            height: 10px;
            border-radius: 5px;
            margin-bottom: 15px;
            background-color: var(--bg-input);
            overflow: hidden;
            position: relative;
        }
        .stress-meter-fill {
            height: 100%;
            background: linear-gradient(to right, var(--stress-low), var(--stress-medium), var(--stress-high));
            transition: width 0.5s ease-out;
            border-radius: 5px;
            position: absolute;
            left: 0;
            top: 0;
        }

        .task-card .delete-button {
            background-color: var(--bg-input);
            color: var(--text-mid);
            border: none;
            width: 50px;
            height: 50px;
            border-radius: 50%;
            cursor: pointer;
            font-size: 1.1rem;
            transition: background-color 0.3s ease, color 0.3s ease, transform 0.2s ease;
            align-self: flex-end;
            margin-top: auto;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 0;
        }
        .task-card .delete-button:hover {
            background-color: var(--status-overdue);
            color: var(--bg-dark);
            transform: translateY(-3px);
        }

        .pagination {
            text-align: center;
            margin: 50px 0 30px;
            <?php if ($total_pages <= 1) echo 'display: none;'; ?>
        }
        .pagination a {
            background-color: var(--bg-input);
            color: var(--text-light);
            padding: 12px 25px;
            margin: 0 10px;
            border-radius: 30px;
            text-decoration: none;
            font-size: 1rem;
            transition: background-color 0.3s ease, transform 0.2s ease;
        }
        .pagination a:hover {
            background-color: var(--accent-teal);
            transform: translateY(-3px);
            color: var(--bg-dark);
        }

        .fab {
            position: fixed;
            bottom: 25px;
            right: 25px;
            background-color: var(--accent-teal);
            color: var(--bg-dark);
            width: 60px;
            height: 60px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 10px 30px rgba(0, 188, 212, 0.6);
            transition: background-color 0.3s ease, transform 0.2s ease;
            text-decoration: none;
            font-size: 1.4rem;
            z-index: 100;
            cursor: pointer;
        }
        .fab:hover {
            background-color: var(--accent-teal-dark);
            transform: scale(1.15);
        }

        .modal-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.75);
            display: flex;
            justify-content: center;
            align-items: center;
            z-index: 1000;
            opacity: 0;
            visibility: hidden;
            transition: opacity 0.3s ease, visibility 0.3s ease;
        }
        .modal-overlay.show {
            opacity: 1;
            visibility: visible;
        }
        .modal-content .modal-content {
        background: var(--bg-card); /* Using var(--bg-card) for consistency */
        padding: 3rem;
        border-radius: 1.5rem;
        width: 60%; /* Smaller width */
        max-width: 700px; /* Smaller max-width */
        height: 60vh; /* Shorter height */
        box-shadow: none; /* Removed the shadow */
        display: flex;
        flex-direction: column;
        gap: 2rem;
        color: var(--text-light); /* Using var(--text-light) for consistency */
        overflow: auto;
        }
        .modal-overlay.show .modal-content {
            transform: scale(1);
        }
        .modal-close-button {
            position: absolute;
            top: 15px;
            right: 20px;
            background: none;
            border: none;
            font-size: 2rem;
            color: var(--text-dark);
            cursor: pointer;
            transition: color 0.3s ease, transform 0.2s ease;
            line-height: 1;
            padding: 5px;
            border-radius: 50%;
        }
        .modal-close-button:hover {
            color: var(--status-overdue);
            transform: rotate(90deg);
        }

        .task-form-background-element {
            position: absolute;
            top: 50%;
            left: 50%;
            width: 80%;
            height: 80%;
            background: radial-gradient(circle at center, rgba(0, 188, 212, 0.2) 0%, transparent 70%);
            filter: blur(80px);
            transform: translate(-50%, -50%);
            z-index: 1;
            pointer-events: none;
            opacity: 0.7;
        }
        .task-form-section {
            position: relative;
            z-index: 2;
            padding: 0;
            background-color: transparent;
            box-shadow: none;
        }
        .task-form-section h2 {
            font-size: 1.8rem;
            font-weight: 700;
            margin-bottom: 25px;
            text-align: center;
            color: var(--accent-teal);
            text-shadow: 0 0 10px rgba(0, 188, 212, 0.4);
        }
        .task-form-section .input-group {
            margin-bottom: 20px;
            position: relative;
        }
        .task-form-section label {
            display: block;
            font-size: 0.95rem;
            color: var(--text-mid);
            margin-bottom: 8px;
            transition: all 0.2s ease;
            font-weight: 600;
        }
        .task-form-section input,
        .task-form-section textarea {
            width: 100%;
            padding: 14px 18px;
            border: 1px solid var(--bg-input);
            border-radius: 10px;
            background-color: var(--bg-input);
            color: var(--text-light);
            font-size: 1rem;
            transition: border-color 0.3s ease, box-shadow 0.3s ease, background-color 0.3s ease;
        }
        .task-form-section input:focus,
        .task-form-section textarea:focus {
            outline: none;
            border-color: var(--accent-teal);
            box-shadow: 0 0 0 4px rgba(0, 188, 212, 0.3);
            background-color: #333333;
        }
        .task-form-section textarea {
            resize: vertical;
            min-height: 120px;
        }
        .task-form-section input[type="date"] {
            cursor: pointer;
        }
        .task-form-section button[type="submit"] {
            background-color: var(--accent-teal);
            color: var(--bg-dark);
            border: none;
            padding: 16px 35px;
            font-size: 1.1rem;
            font-weight: 700;
            border-radius: 10px;
            cursor: pointer;
            transition: background-color 0.3s ease, transform 0.2s ease, box-shadow 0.3s ease;
            display: block;
            margin: 30px auto 0;
            box-shadow: 0 5px 15px rgba(0, 188, 212, 0.4);
        }
        .task-form-section button[type="submit"]:hover {
            background-color: var(--accent-teal-dark);
            transform: translateY(-3px);
            box-shadow: 0 10px 25px rgba(0, 188, 212, 0.6);
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(-20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        @keyframes cardFadeIn {
            from { opacity: 0; transform: scale(0.98); }
            to { opacity: 1; transform: scale(1); }
        }

        @media (max-width: 1024px) {
            main {
                padding: 0 25px;
            }
            .tasks-grid {
                grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
                gap: 25px;
            }
            .modal-content {
                max-width: 500px;
            }
        }
        @media (max-width: 768px) {
            body {
                padding: 15px;
            }
            main {
                margin-top: 80px;
                padding: 0 15px;
            }
            header {
                position: static;
                text-align: center;
                margin-bottom: 25px;
                padding-top: 15px;
            }
            .tasks-grid {
                grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
                gap: 20px;
            }
            .task-card {
                padding: 25px;
                min-height: 250px;
            }
            .task-card .task-title {
                font-size: 1.2rem;
            }
            .task-card .task-description {
                font-size: 0.9rem;
            }
            .modal-content {
                padding: 30px;
            }
            .modal-close-button {
                font-size: 1.8rem;
                top: 10px;
                right: 15px;
            }
        }
        @media (max-width: 600px) {
            main {
                margin-top: 70px;
                padding: 0 10px;
            }
            .fab {
                bottom: 15px;
                right: 15px;
                width: 55px;
                height: 55px;
                font-size: 1.2rem;
            }
            .task-card {
                min-height: 240px;
                padding: 20px;
            }
            .task-card .task-title {
                font-size: 1.1rem;
            }
            .task-card .task-description {
                font-size: 0.85rem;
                -webkit-line-clamp: 4;
            }
            .task-card .countdown {
                font-size: 1.3rem;
            }
            .task-card .delete-button {
                width: 40px;
                height: 40px;
                font-size: 0.9rem;
            }
            .modal-content {
                padding: 25px;
            }
            .task-form-section input,
            .task-form-section textarea {
                padding: 12px 15px;
                font-size: 0.9rem;
            }
            .task-form-section button[type="submit"] {
                padding: 14px 25px;
                font-size: 1rem;
            }
        }
    </style>
</head>
<body>
    <header>
        <h3>Organize your tasks and crush your deadlines.</h3>
    </header>

    <main>
        <?php if (!empty($success)): ?>
            <p class="message success"><?php echo $success; ?></p>
        <?php endif; ?>

        <section class="tasks-grid">
            <?php if (empty($tasks)): ?>
                <p style="text-align: center; width: 100%; grid-column: 1 / -1; color: var(--text-dark); margin-top: 50px;">
                    No tasks yet! Click the <i class="fas fa-plus"></i> button to add your first task.
                </p>
            <?php endif; ?>

            <?php foreach ($tasks as $task): ?>
                <?php
                $due_date = new DateTime($task['due_date']);
                $today = new DateTime();
                $today->setTime(0, 0, 0);
                $interval = $today->diff($due_date);
                $days_left = $interval->days;

                $urgency_class = '';
                $countdown_text = '';

                if ($today > $due_date) {
                    $days_left_abs = $interval->days;
                    $countdown_text = $days_left_abs == 0 ? "Due today!" : "Overdue by $days_left_abs day" . ($days_left_abs > 1 ? "s" : "");
                    $urgency_class = 'overdue';
                } elseif ($days_left == 0) {
                    $countdown_text = "Due today!";
                    $urgency_class = 'soon';
                } elseif ($days_left <= 3) {
                    $countdown_text = "$days_left day" . ($days_left > 1 ? "s" : "") . " left";
                    $urgency_class = 'soon';
                } else {
                    $countdown_text = "$days_left days left";
                    $urgency_class = 'far';
                }

                $max_stress_days = 30;
                $stress_level_raw = max(0, $days_left);
                $stress_percent = 100 - (($stress_level_raw / $max_stress_days) * 100);
                $stress_percent = max(0, min(100, $stress_percent));
                if ($today > $due_date) {
                    $stress_percent = 100;
                }
                ?>
                <div class="task-card <?php echo $urgency_class; ?>" role="region" aria-label="Task: <?php echo htmlspecialchars($task['task_name']); ?>">
                    <div class="urgency-dot"></div>
                    <h3 class="task-title"><?php echo htmlspecialchars($task['task_name'], ENT_QUOTES, 'UTF-8'); ?></h3>
                    <p class="task-description"><?php echo htmlspecialchars($task['task_description'] ?: 'No description provided.', ENT_QUOTES, 'UTF-8'); ?></p>
                    <div class="stress-meter">
                        <div class="stress-meter-fill" style="width: <?php echo $stress_percent; ?>%;"></div>
                    </div>
                    <p class="due-date-label">Due Date:</p>
                    <p class="due-date-display"><?php echo $due_date->format('F j, Y'); ?></p>
                    <p class="countdown"><?php echo $countdown_text; ?></p>
                    <a href="tasks.php?delete=<?php echo $task['id']; ?>" class="delete-button" title="Delete Task" onclick="return confirm('Are you sure you want to delete this task?')">
                        <i class="fas fa-trash-alt"></i>
                    </a>
                </div>
            <?php endforeach; ?>
        </section>

        <div class="pagination">
            <?php if ($page > 1): ?>
                <a href="tasks.php?page=<?php echo $page - 1; ?>">Previous</a>
            <?php endif; ?>
            <?php if ($page < $total_pages): ?>
                <a href="tasks.php?page=<?php echo $page + 1; ?>">Next</a>
            <?php endif; ?>
        </div>
    </main>

    <button id="addTaskFab" class="fab" aria-label="Add New Task">
        <i class="fas fa-plus"></i>
    </button>

    <div id="addTaskModal" class="modal-overlay <?php echo $show_add_task_modal ? 'show' : ''; ?>">
        <div class="task-form-background-element"></div>
        <div class="modal-content">
            <button class="modal-close-button" aria-label="Close">×</button>
            <div class="task-form-section">
                <h2>Add a New Task</h2>
                <?php if (!empty($error)): ?>
                    <p class="message error"><?php echo $error; ?></p>
                <?php endif; ?>
                <form method="POST" action="tasks.php" aria-label="Add new task">
                    <div class="input-group">
                        <label for="task_name">Task Name</label>
                        <input type="text" id="task_name" name="task_name" required placeholder="e.g., Finish project report" value="<?php echo htmlspecialchars($task_name_val, ENT_QUOTES, 'UTF-8'); ?>" />
                    </div>
                    <div class="input-group">
                        <label for="task_description">Description (Optional)</label>
                        <textarea id="task_description" name="task_description" placeholder="Add more details about the task..."><?php echo htmlspecialchars($task_description_val, ENT_QUOTES, 'UTF-8'); ?></textarea>
                    </div>
                    <div class="input-group">
                        <label for="due_date">Due Date</label>
                        <input type="date" id="due_date" name="due_date" required min="<?php echo date('Y-m-d'); ?>" value="<?php echo htmlspecialchars($due_date_val, ENT_QUOTES, 'UTF-8'); ?>" />
                    </div>
                    <button type="submit">Add Task <i class="fas fa-plus"></i></button>
                </form>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const addTaskFab = document.getElementById('addTaskFab');
            const addTaskModal = document.getElementById('addTaskModal');
            const closeModalButton = addTaskModal.querySelector('.modal-close-button');
            const form = addTaskModal.querySelector('form');

            function showModal() {
                addTaskModal.classList.add('show');
                document.getElementById('task_name').focus();
            }

            function hideModal() {
                addTaskModal.classList.remove('show');
                form.reset();
                const errorMessage = form.querySelector('.message.error');
                if (errorMessage) errorMessage.remove();
            }

            addTaskFab.addEventListener('click', showModal);
            closeModalButton.addEventListener('click', hideModal);
            addTaskModal.addEventListener('click', (event) => {
                if (event.target === addTaskModal) hideModal();
            });
            document.addEventListener('keydown', (event) => {
                if (event.key === 'Escape' && addTaskModal.classList.contains('show')) {
                    hideModal();
                }
            });

            // Client-side validation
            form.addEventListener('submit', (e) => {
                const taskName = document.getElementById('task_name').value.trim();
                const dueDate = document.getElementById('due_date').value;
                const today = new Date().toISOString().split('T')[0];
                if (!taskName) {
                    e.preventDefault();
                    alert('Task name is required.');
                } else if (dueDate < today) {
                    e.preventDefault();
                    alert('Please select a future date.');
                }
            });

            <?php if ($show_add_task_modal): ?>
                addTaskModal.classList.add('show');
            <?php endif; ?>
        });
    </script>
</body>
</html>