<?php
$host = getenv('DB_HOST') ?: 'mariadb-service';
$port = getenv('DB_PORT') ?: '3610';
$db   = getenv('DB_NAME') ?: 'appdb';
$user = getenv('DB_USER') ?: 'appuser';
$pass = getenv('DB_PASS') ?: 'apppassword';

$dsn = "mysql:host=$host;port=$port;dbname=$db;charset=utf8mb4";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (\PDOException $e) {
    die("<h2>Database Connection Failed</h2><p>" . $e->getMessage() . "</p><p>Please ensure MariaDB is running and accessible on port $port.</p>");
}

// Initialize Database Table if it doesn't exist
$pdo->exec("CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    email VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)");

$message = '';

// Handle CRUD Operations
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['create'])) {
        $name = $_POST['name'] ?? '';
        $email = $_POST['email'] ?? '';
        if ($name && $email) {
            $stmt = $pdo->prepare("INSERT INTO users (name, email) VALUES (?, ?)");
            $stmt->execute([$name, $email]);
            $message = "User created successfully.";
        }
    } elseif (isset($_POST['update'])) {
        $id = $_POST['id'] ?? '';
        $name = $_POST['name'] ?? '';
        $email = $_POST['email'] ?? '';
        if ($id && $name && $email) {
            $stmt = $pdo->prepare("UPDATE users SET name = ?, email = ? WHERE id = ?");
            $stmt->execute([$name, $email, $id]);
            $message = "User updated successfully.";
        }
    } elseif (isset($_POST['delete'])) {
        $id = $_POST['id'] ?? '';
        if ($id) {
            $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
            $stmt->execute([$id]);
            $message = "User deleted successfully.";
        }
    }
}

// Fetch all users
$stmt = $pdo->query("SELECT * FROM users ORDER BY created_at DESC");
$users = $stmt->fetchAll();

// Check if we are editing a user
$editUser = null;
if (isset($_GET['edit'])) {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$_GET['edit']]);
    $editUser = $stmt->fetch();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PHP Kubernetes CRUD App</title>
    <style>
        :root {
            --primary: #4f46e5;
            --primary-hover: #4338ca;
            --danger: #ef4444;
            --danger-hover: #dc2626;
            --bg: #f3f4f6;
            --card: #ffffff;
            --text: #1f2937;
            --border: #e5e7eb;
        }
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: var(--bg);
            color: var(--text);
            margin: 0;
            padding: 2rem;
            display: flex;
            justify-content: center;
        }
        .container {
            max-width: 800px;
            width: 100%;
        }
        .card {
            background: var(--card);
            border-radius: 8px;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
            padding: 2rem;
            margin-bottom: 2rem;
        }
        h1, h2 {
            margin-top: 0;
            color: var(--text);
        }
        .alert {
            padding: 1rem;
            background-color: #d1fae5;
            color: #065f46;
            border-radius: 4px;
            margin-bottom: 1rem;
        }
        form {
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }
        .form-group {
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
        }
        input[type="text"], input[type="email"] {
            padding: 0.75rem;
            border: 1px solid var(--border);
            border-radius: 4px;
            font-size: 1rem;
        }
        .btn {
            padding: 0.75rem 1.5rem;
            border: none;
            border-radius: 4px;
            font-size: 1rem;
            cursor: pointer;
            color: white;
            transition: background-color 0.2s;
        }
        .btn-primary { background-color: var(--primary); }
        .btn-primary:hover { background-color: var(--primary-hover); }
        .btn-danger { background-color: var(--danger); }
        .btn-danger:hover { background-color: var(--danger-hover); }
        
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 1rem;
        }
        th, td {
            text-align: left;
            padding: 1rem;
            border-bottom: 1px solid var(--border);
        }
        th {
            background-color: #f9fafb;
            font-weight: 600;
        }
        .actions {
            display: flex;
            gap: 0.5rem;
        }
        .header-info {
            font-size: 0.9rem;
            color: #6b7280;
            margin-bottom: 1.5rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid var(--border);
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="card">
            <h1>PHP + MariaDB CRUD</h1>
            <div class="header-info">
                Pod Hostname: <strong><?php echo gethostname(); ?></strong> | PHP Version: <strong><?php echo phpversion(); ?></strong>
            </div>

            <?php if ($message): ?>
                <div class="alert"><?php echo htmlspecialchars($message); ?></div>
            <?php endif; ?>

            <h2><?php echo $editUser ? 'Edit User' : 'Add New User'; ?></h2>
            <form method="POST" action="index.php">
                <?php if ($editUser): ?>
                    <input type="hidden" name="id" value="<?php echo $editUser['id']; ?>">
                <?php endif; ?>
                
                <div class="form-group">
                    <label for="name">Name</label>
                    <input type="text" id="name" name="name" required value="<?php echo $editUser ? htmlspecialchars($editUser['name']) : ''; ?>">
                </div>
                
                <div class="form-group">
                    <label for="email">Email</label>
                    <input type="email" id="email" name="email" required value="<?php echo $editUser ? htmlspecialchars($editUser['email']) : ''; ?>">
                </div>
                
                <button type="submit" name="<?php echo $editUser ? 'update' : 'create'; ?>" class="btn btn-primary">
                    <?php echo $editUser ? 'Update User' : 'Create User'; ?>
                </button>
                
                <?php if ($editUser): ?>
                    <a href="index.php" style="margin-top: 0.5rem; text-align: center; color: var(--primary); text-decoration: none;">Cancel</a>
                <?php endif; ?>
            </form>
        </div>

        <div class="card">
            <h2>User List</h2>
            <?php if (count($users) > 0): ?>
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($users as $user): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($user['id']); ?></td>
                                <td><?php echo htmlspecialchars($user['name']); ?></td>
                                <td><?php echo htmlspecialchars($user['email']); ?></td>
                                <td>
                                    <div class="actions">
                                        <a href="?edit=<?php echo $user['id']; ?>" class="btn btn-primary" style="text-decoration: none; padding: 0.4rem 0.8rem; font-size: 0.875rem;">Edit</a>
                                        <form method="POST" action="index.php" style="margin: 0; padding: 0; display: inline;">
                                            <input type="hidden" name="id" value="<?php echo $user['id']; ?>">
                                            <button type="submit" name="delete" class="btn btn-danger" style="padding: 0.4rem 0.8rem; font-size: 0.875rem;" onclick="return confirm('Are you sure?');">Delete</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p style="color: #6b7280; text-align: center; margin-top: 2rem;">No users found. Create one above!</p>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>