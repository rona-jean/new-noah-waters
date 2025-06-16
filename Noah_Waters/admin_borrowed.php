<?php
session_start();
require 'config.php';


if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['admin'])) {
    header("Location: login.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['borrow_id'], $_POST['returned'])) {
    $borrowId = intval($_POST['borrow_id']);
    $returned = $_POST['returned'] === '1' ? 1 : 0;
    $returnedAt = $returned ? date('Y-m-d H:i:s') : null;

    $stmt = $conn->prepare("UPDATE borrowed_containers SET returned = ?, returned_at = ? WHERE id = ?");
    $stmt->bind_param("isi", $returned, $returnedAt, $borrowId);
    $stmt->execute();
    $stmt->close();

    header("Location: admin_borrowed.php");
    exit;
}

$sql = "SELECT
    bc.id,
    bc.order_id,
    bc.container_id,
    bc.borrowed_at,
    bc.returned,
    bc.returned_at,
    p.name AS container_name,
    o.fullname AS borrower_name
FROM borrowed_containers bc
LEFT JOIN products p ON bc.container_id = p.id
LEFT JOIN orders o ON bc.order_id = o.id
ORDER BY bc.borrowed_at DESC";

$result = $conn->query($sql);

if (!$result) {
    die("SQL Error: " . $conn->error);
}

$borrowedContainers = $result->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Borrowed Containers</title>
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
        .btn-primary, .btn-success, .btn-secondary {
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
        .btn-secondary {
            background-color: #6c757d;
            border: none;
            padding: 6px 15px;
            border-radius: 6px;
        }
        .status-badge {
            padding: 6px 12px;
            border-radius: 6px;
            font-size: 0.9em;
            font-weight: bold;
        }
        .status-returned {
            background-color: #28a745;
            color: white;
        }
        .status-borrowed {
            background-color: #dc3545;
            color: white;
        }
        .form-select {
            background-color: rgba(255, 255, 255, 0.9);
            border: 1px solid #ddd;
            border-radius: 8px;
            padding: 8px 12px;
            font-size: 1.1em;
            font-family: "Boogaloo", sans-serif;
            width: auto;
            min-width: 150px;
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
    <h2 class="text-center mb-4">Borrowed Containers</h2>

    <div class="table-responsive">
        <table class="table table-hover">
            <thead>
                <tr>
                    <th>Container</th>
                    <th>Borrower</th>
                    <th>Order ID</th>
                    <th>Borrowed Date</th>
                    <th>Status</th>
                    <th>Returned Date</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($borrowedContainers as $container): ?>
                    <tr>
                        <td><?= htmlspecialchars($container['container_name']) ?></td>
                        <td><?= htmlspecialchars($container['borrower_name']) ?></td>
                        <td>#<?= $container['order_id'] ?></td>
                        <td><?= date('M d, Y h:i A', strtotime($container['borrowed_at'])) ?></td>
                        <td>
                            <span class="status-badge <?= $container['returned'] ? 'status-returned' : 'status-borrowed' ?>">
                                <?= $container['returned'] ? 'Returned' : 'Borrowed' ?>
                            </span>
                        </td>
                        <td>
                            <?= $container['returned_at'] ? date('M d, Y h:i A', strtotime($container['returned_at'])) : '-' ?>
                        </td>
                        <td>
                            <?php if (!$container['returned']): ?>
                                <form method="POST" class="d-inline">
                                    <input type="hidden" name="borrow_id" value="<?= $container['id'] ?>">
                                    <input type="hidden" name="returned" value="1">
                                    <button type="submit" class="btn btn-success btn-sm">Mark as Returned</button>
                                </form>
                            <?php else: ?>
                                <form method="POST" class="d-inline">
                                    <input type="hidden" name="borrow_id" value="<?= $container['id'] ?>">
                                    <input type="hidden" name="returned" value="0">
                                    <button type="submit" class="btn btn-secondary btn-sm">Mark as Borrowed</button>
                                </form>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
