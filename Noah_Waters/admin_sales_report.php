<?php
session_start();
require 'config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit;
}

// filter
$filter = $_GET['filter'] ?? 'day';
$whereClause = '';

switch ($filter) {
    case 'week':
        $whereClause = "WHERE o.created_at >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)";
        break;
    case 'month':
        $whereClause = "WHERE o.created_at >= DATE_SUB(CURDATE(), INTERVAL 1 MONTH)";
        break;
    default:
        $whereClause = "WHERE DATE(o.created_at) = CURDATE()";
}

$query = "SELECT o.id, o.user_id, o.total_amount, o.payment_status, o.created_at,
                 u.fullname
          FROM orders o
          LEFT JOIN users u ON o.user_id = u.id
          $whereClause
          ORDER BY o.created_at DESC";


$result = $conn->query($query);
if (!$result) {
    die("Query failed: " . $conn->error);
}

function exportCSV($orders) {
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="sales_report.csv"');
    $output = fopen('php://output', 'w');
    fputcsv($output, ['Order ID', 'Customer', 'Total', 'Status', 'Date']);
    foreach ($orders as $row) {
        fputcsv($output, [
            $row['id'],
            $row['fullname'] ?? 'Guest',
            $row['total_amount'],
            $row['payment_status'],
            $row['created_at']
        ]);
    }
    fclose($output);
    exit;
}

// pagexport ng csv excel file
if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    $orders = [];
    while ($row = $result->fetch_assoc()) {
        $orders[] = $row;
    }
    exportCSV($orders);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Sales Report</title>
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
        .btn-primary {
            background-color: #0f65b4;
            border: none;
            padding: 8px 20px;
            border-radius: 8px;
            transition: background-color 0.3s;
            font-family: "Boogaloo", sans-serif;
            font-size: 1.1em;
        }
        .btn-primary:hover {
            background-color: #0d4d8c;
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
        .filter-form {
            display: flex;
            gap: 20px;
            align-items: flex-end;
            margin-bottom: 25px;
            flex-wrap: wrap;
        }
        .filter-group {
            flex: 1;
            min-width: 200px;
        }
        .filter-group label {
            color: white;
            font-size: 1.1em;
            margin-bottom: 8px;
            display: block;
        }
        .filter-button {
            min-width: 150px;
            height: 42px;
        }
        .total-amount {
            color: white;
            font-size: 1.3em;
            margin: 20px 0;
            text-align: right;
            font-weight: bold;
            text-shadow: 1px 1px 3px rgba(0, 0, 0, 0.2);
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
            .filter-form {
                flex-direction: column;
                gap: 15px;
            }
            .filter-group {
                width: 100%;
            }
            .filter-button {
                width: 100%;
            }
            .total-amount {
                font-size: 1.1em;
                text-align: center;
            }
        }
    </style>
</head>
<body>
<?php include 'navbar_admin.php'; ?>

<div class="container container-box">
    <h2 class="text-center mb-4">Sales Report</h2>

    <!--filter ulit-->
    <form method="GET" class="filter-form">
        <div class="filter-group">
            <label>Time Period</label>
            <select name="filter" class="form-select">
                <option value="day" <?= $filter === 'day' ? 'selected' : '' ?>>Today</option>
                <option value="week" <?= $filter === 'week' ? 'selected' : '' ?>>Last 7 Days</option>
                <option value="month" <?= $filter === 'month' ? 'selected' : '' ?>>Last 30 Days</option>
            </select>
        </div>
        <div class="filter-group">
            <button type="submit" class="btn btn-primary filter-button">Apply Filter</button>
            <a href="?filter=<?= $filter ?>&export=csv" class="btn btn-primary filter-button">Export CSV</a>
        </div>
    </form>

    <!--pagdisplay ng mga nagorders-->
    <div class="table-responsive">
        <table class="table table-hover">
            <thead>
                <tr>
                    <th>Order ID</th>
                    <th>Customer</th>
                    <th>Total</th>
                    <th>Status</th>
                    <th>Date</th>
                </tr>
            </thead>
            <tbody>
                <?php 
                $total = 0;
                while ($row = $result->fetch_assoc()): 
                    $total += $row['total_amount'];
                ?>
                    <tr>
                        <td><?= $row['id'] ?></td>
                        <td><?= htmlspecialchars($row['fullname'] ?? 'Guest') ?></td>
                        <td>₱<?= number_format($row['total_amount'], 2) ?></td>
                        <td><?= ucfirst($row['payment_status']) ?></td>
                        <td><?= date('M d, Y h:i A', strtotime($row['created_at'])) ?></td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>

    <div class="total-amount">
        Total Sales: ₱<?= number_format($total, 2) ?>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
