<?php
session_start();
require 'config.php';

//check kung admin ba yung naglogged in
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit;
}

//dito yung code kung saan iuupdate mo kung user or admin yung user na napili mo
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['update_role'], $_POST['user_id'])) {
        $userId = intval($_POST['user_id']);
        $newRole = $_POST['new_role'];

        $stmt = $conn->prepare("UPDATE users SET role = ? WHERE id = ?");
        $stmt->bind_param("si", $newRole, $userId);
        $stmt->execute();
        $stmt->close();
    }

    if (isset($_POST['delete_user'], $_POST['user_id'])) {
        $userId = intval($_POST['user_id']);

        $stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $stmt->close();
    }

    // Redirect to avoid form resubmission on refresh
    header("Location: admin_manage_users.php");
    exit;
}

// Fetch all users
$result = $conn->query("SELECT id, fullname, email, role FROM users");
if (!$result) {
    die("Query failed: " . $conn->error);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage Users</title>
    <link href="https://fonts.googleapis.com/css2?family=Boogaloo&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" />
    <style>
        body {
            background-color: #79c7ff;
            margin: 0;
            padding: 0;
            font-family: "Boogaloo", sans-serif;
            background-image: url('back.webp');
            background-size: cover;
            background-position: center;
            background-repeat: no-repeat;
            background-attachment: fixed;
        }
        .container {
            max-width: 1000px;
            margin: 0 auto;
            padding: 0 15px;
        }
        .container-box {
            background: rgba(3, 0, 0, 0.1);
            border-radius: 15px;
            padding: 25px;
            margin-top: 30px;
            box-shadow: 0 0 15px rgba(255, 255, 255, 0.52);
            color: white;
            max-width: 900px;
            margin-left: auto;
            margin-right: auto;
        }
        h2 {
            color: white;
            font-size: 2em;
            margin-bottom: 30px;
            text-shadow: 1px 1px 3px rgba(0, 0, 0, 0.2);
            font-family: "Boogaloo", sans-serif;
        }
        .table {
            background: rgba(255, 255, 255, 0.9);
            border-radius: 12px;
            overflow: hidden;
            margin-bottom: 25px;
        }
        .table th {
            background-color: #0f65b4;
            color: white;
            font-family: "Boogaloo", sans-serif;
            font-size: 1.1em;
        }
        .table td {
            font-family: "Boogaloo", sans-serif;
            font-size: 1.1em;
            color: #333;
        }
        .table tbody tr:hover {
            background-color: rgba(255, 255, 255, 0.15);
        }
        .form-select {
            background-color: rgba(255, 255, 255, 0.9);
            border: 1px solid #ddd;
            border-radius: 8px;
            padding: 8px 12px;
            font-size: 1.1em;
            font-family: "Boogaloo", sans-serif;
            width: auto;
            min-width: 120px;
        }
        .btn-primary, .btn-success, .btn-secondary, .btn-danger {
            font-family: "Boogaloo", sans-serif;
            font-size: 1.1em;
        }
        .btn-primary {
            background-color: #0f65b4;
            border: none;
            padding: 8px 20px;
            border-radius: 8px;
            transition: background-color 0.3s;
        }
        .btn-primary:hover {
            background-color: #0d4d8c;
        }
        .btn-success {
            background-color: #28a745;
            border: none;
            padding: 6px 15px;
            border-radius: 6px;
        }
        .btn-danger {
            background-color: #dc3545;
            border: none;
            padding: 6px 15px;
            border-radius: 6px;
        }
        .btn-outline-success {
            color: #28a745;
            border-color: #28a745;
            background-color: transparent;
        }
        .btn-outline-success:hover {
            background-color: #28a745;
            color: white;
        }
        .btn-outline-danger {
            color: #dc3545;
            border-color: #dc3545;
            background-color: transparent;
        }
        .btn-outline-danger:hover {
            background-color: #dc3545;
            color: white;
        }
        @media (max-width: 768px) {
            .container-box {
                margin: 20px 15px;
                padding: 15px;
            }
            .table td, .table th {
                font-size: 1em;
            }
            .form-select, .btn {
                font-size: 1em;
            }
        }
    </style>
</head>
<body>
<?php include 'navbar_admin.php'; ?>

<div class="container container-box">
    <h2 class="text-center mb-4">Manage Users</h2>

    <table class="table table-hover">
        <thead>
            <tr>
                <th>ID</th>
                <th>Full Name</th>
                <th>Email</th>
                <th>Role</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php while ($user = $result->fetch_assoc()): ?>
                <tr>
                    <td><?= $user['id'] ?></td>
                    <td><?= htmlspecialchars($user['fullname']) ?></td>
                    <td><?= htmlspecialchars($user['email']) ?></td>
                    <td><?= htmlspecialchars($user['role']) ?></td>
                    <td>
                        <form method="POST" class="d-inline">
                            <input type="hidden" name="user_id" value="<?= $user['id'] ?>">
                            <select name="new_role" class="form-select d-inline">
                                <option value="user" <?= $user['role'] === 'user' ? 'selected' : '' ?>>User</option>
                                <option value="admin" <?= $user['role'] === 'admin' ? 'selected' : '' ?>>Admin</option>
                            </select>
                            <button type="submit" name="update_role" class="btn btn-outline-success btn-sm">Update</button>
                        </form>
                        <form method="POST" class="d-inline">
                            <input type="hidden" name="user_id" value="<?= $user['id'] ?>">
                            <button type="submit" name="delete_user" class="btn btn-outline-danger btn-sm" onclick="return confirm('Are you sure you want to delete this user?')">Delete</button>
                        </form>
                    </td>
                </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
