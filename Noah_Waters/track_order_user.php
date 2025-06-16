<?php
session_start();
require 'config.php';

if (!isset($_SESSION['user_id'])) {
    echo "<h2 style='text-align:center;'>Guest orders are not supported here. Please login to view your orders.</h2>";
    exit;
}

$user_id = $_SESSION['user_id'];

if (isset($_GET['cancel_order_id'])) {
  $cancel_order_id = intval($_GET['cancel_order_id']);
  $stmt = $conn->prepare("UPDATE orders SET status = 'Cancelled' WHERE id = ? AND user_id = ? AND status IN ('Pending', 'Preparing')");
  $stmt->bind_param("ii", $cancel_order_id, $user_id);
  $stmt->execute();
  $stmt->close();
}

$sql = "SELECT o.id AS order_id, o.status, o.created_at, o.payment_status,
               GROUP_CONCAT(CONCAT(p.name, ' (x', oi.quantity, ')') SEPARATOR ', ') AS items,
               SUM(oi.quantity * oi.price) AS total
               FROM orders o 
        JOIN order_items oi ON o.id = oi.order_id
        JOIN products p ON oi.product_id = p.id
        WHERE o.user_id = ?
        GROUP BY o.id
               ORDER BY o.created_at DESC";

// Debug: Log the SQL query and user ID
error_log("Track Orders SQL Query: " . $sql);
error_log("User ID: " . $user_id);
            
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
                
// Debug: Log all orders and their statuses
while ($debug_row = $result->fetch_assoc()) {
    error_log("Order #{$debug_row['order_id']} - Raw status: '{$debug_row['status']}'");
    error_log("Order #{$debug_row['order_id']} - Lowercase status: '" . strtolower($debug_row['status']) . "'");
    error_log("Order #{$debug_row['order_id']} - Status comparison: " . (strtolower($debug_row['status']) === 'pending' ? 'true' : 'false'));
}
// Reset the result pointer
$result->data_seek(0);
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<title>My Orders - Noah Waters</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" />
<link href="https://fonts.googleapis.com/css2?family=Boogaloo&display=swap" rel="stylesheet" />
<script>
    // Function to refresh order status
    function refreshOrderStatus() {
        // Reload the page every 30 seconds to check for status updates
        setTimeout(function() {
            window.location.reload();
        }, 30000); // 30 seconds
    }

    // Start refreshing when the page loads
    document.addEventListener('DOMContentLoaded', function() {
        refreshOrderStatus();
    });
</script>
    <style>
      * {
        box-sizing: border-box;
      }

      body {
        background-color: #79c7ff;
        margin: 0;
        padding: 0;
        font-family: "Boogaloo", sans-serif;
        font-weight: 400;
        font-style: normal;
        background-image: url('back.webp');
        background-size: cover;
        background-position: center;
        background-repeat: no-repeat;
        background-attachment: fixed;
      }

      a {
        text-decoration: none;
        color: inherit;
      }

      .navbar {
        position: sticky;
        top: 0;
        background: #0f65b4;
        display: flex;
        align-items: center;
        padding: 0 2rem;
        height: 60px;
        box-shadow: 0 2px 5px rgb(0 0 0 / 0.1);
        z-index: 1000;
      }

      .navbar .logo {
        color: white;
        font-weight: 700;
        font-size: 1.5rem;
        letter-spacing: 1px;
        user-select: none;
      }

      .navbar nav {
        margin-left: auto;
        display: flex;
        gap: 1.2rem;
      }

      .navbar nav a {
        color: white;
        font-weight: 600;
        padding: 8px 12px;
        border-radius: 4px;
        transition: background-color 0.3s ease;
      }

      .navbar nav a:hover,
      .navbar nav a.active {
        background-color: #094a85;
      }

      .container {
        max-width: 900px;
        margin: 2.5rem auto 3rem;
        padding: 0 1rem;
      }

      h1 {
        color: #0f65b4;
        text-align: center;
        margin-bottom: 2rem;
        font-weight: 700;
      }

      .order-card {
        background: white;
        border-radius: 10px;
        box-shadow: 0 3px 8px rgb(0 0 0 / 0.1);
        margin-bottom: 2rem;
        padding: 1.6rem 2rem;
        transition: box-shadow 0.3s ease;
      }

      .order-card:hover {
        box-shadow: 0 5px 15px rgb(0 0 0 / 0.15);
      }

      .order-header {
        display: flex;
        justify-content: space-between;
        flex-wrap: wrap;
        margin-bottom: 1rem;
      }

      .order-id {
        font-weight: 700;
        font-size: 1.1rem;
        color: #0f65b4;
      }

      .order-date {
        font-size: 0.9rem;
        color: #666;
        margin-top: 4px;
      }

      .order-total {
        font-weight: 700;
        font-size: 1.1rem;
        color: #2a9d8f;
      }

      .order-items {
        font-size: 0.95rem;
        color: #444;
        margin-bottom: 1.5rem;
            }

      .status-badge {
        display: inline-block;
        padding: 0.3rem 0.75rem;
        border-radius: 15px;
        font-size: 0.85rem;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        user-select: none;
      }
      
      .status-pending {
        background: #ffcc00;
        color: #856404;
      }

      .status-preparing {
        background: #17a2b8;
        color: white;
      }

      .status-out-for-delivery {
        background: #007bff;
        color: white;
      }

      .status-picked-up-by-customer {
        background: #6f42c1;
        color: white;
      }

      .status-paid {
        background: #28a745;
        color: white;
      }

      .status-delivered {
        background: #1c7430;
        color: white;
      }

      .status-cancelled {
        background: #dc3545;
        color: white;
      }

      .cancel-btn {
        display: inline-block;
        background: #dc3545;
        color: white;
        padding: 8px 14px;
        border: none;
        border-radius: 5px;
        cursor: pointer;
        font-family: "Boogaloo", sans-serif;
        font-size: 1rem;
        text-decoration: none;
        margin-left: 10px;
        transition: background-color 0.3s ease;
      }

      .cancel-btn:hover {
        background: #a71d2a;
      }

      .tracking-steps {
        display: flex;
        justify-content: space-between;
        margin-top: 1.5rem;
        position: relative;
      }

      .tracking-steps::before {
        content: '';
        position: absolute;
        top: 15px;
        left: 10px;
        right: 10px;
        height: 4px;
        background: #ddd;
        border-radius: 2px;
        z-index: 0;
      }

      .step {
        position: relative;
        z-index: 1;
        flex: 1;
        text-align: center;
        font-size: 0.85rem;
        color: #999;
        font-weight: 600;
      }

      .step:not(:last-child) {
        margin-right: 10px;
      }

      .step.active {
        color: #28a745;
      }

      .step::before {
        content: "●";
        display: block;
        font-size: 20px;
        margin-bottom: 6px;
        color: #ccc;
      }

      .step.active::before {
        color: #28a745;
      }

      @media (max-width: 600px) {
        .order-header {
          flex-direction: column;
          gap: 6px;
        }
        .tracking-steps {
          font-size: 0.75rem;
            }
        .cancel-btn {
          width: 100%;
          margin-top: 12px;
        }
      }

      .payment-status {
        display: inline-block;
        padding: 0.3rem 0.75rem;
        border-radius: 15px;
        font-size: 0.85rem;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        margin-left: 10px;
      }

      .payment-paid {
        background: #28a745;
        color: white;
      }

      .payment-unpaid {
        background: #dc3545;
        color: white;
      }

    .status-container {
        display: flex;
        align-items: center;
        gap: 10px;
        margin-top: 10px;
    }

    .payment-status {
        display: inline-block;
        padding: 0.3rem 0.75rem;
        border-radius: 15px;
        font-size: 0.85rem;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.05em;
    }
    </style>
</head>
<body>
    <?php include 'navbar_loggedin_users.php'; ?>

<main class="container" role="main">
  <h1>My Orders</h1>

  <?php if ($result->num_rows > 0): ?>
    <?php while ($row = $result->fetch_assoc()): ?>
                        <?php
        $status = strtolower($row['status']);
                        $statusClass = match($status) {
                            'pending' => 'status-pending',
                            'preparing' => 'status-preparing',
          'out for delivery' => 'status-out-for-delivery',
          'picked up by customer' => 'status-picked-up-by-customer',
          'paid' => 'status-paid',
                            'delivered' => 'status-delivered',
                            'cancelled' => 'status-cancelled',
          default => 'status-pending',
        };
      ?>
      <article class="order-card" aria-labelledby="order-<?= htmlspecialchars($row['order_id']) ?>">
        <header class="order-header">
          <div>
            <div class="order-id" id="order-<?= htmlspecialchars($row['order_id']) ?>">Order #<?= htmlspecialchars($row['order_id']) ?></div>
            <div class="order-date"><?= date("M d, Y h:i A", strtotime($row['created_at'])) ?></div>
          </div>
          <div class="order-total">₱<?= number_format($row['total'], 2) ?></div>
        </header>

        <p class="order-items" aria-label="Ordered items"><?= htmlspecialchars($row['items']) ?></p>

        <div class="status-container">
            <span class="status-badge <?= $statusClass ?>"><?= htmlspecialchars($row['status']) ?></span>
            <span class="payment-status <?= strtolower($row['payment_status']) === 'paid' ? 'payment-paid' : 'payment-unpaid' ?>">
                <?= htmlspecialchars($row['payment_status']) ?>
            </span>
            <?php if (in_array(strtolower($row['status']), ['pending', 'preparing'])): ?>
                <form method="GET" onsubmit="return confirm('Are you sure you want to cancel this order?');" style="margin: 0;">
                    <input type="hidden" name="cancel_order_id" value="<?= $row['order_id'] ?>">
                    <button type="submit" class="cancel-btn">Cancel</button>
                </form>
            <?php endif; ?>
        </div>

        <section class="tracking-steps" aria-label="Order progress for order <?= htmlspecialchars($row['order_id']) ?>">
          <div class="step <?= ($status == 'pending' || in_array($status, ['preparing','out for delivery','picked up by customer','delivered'])) ? 'active' : '' ?>">Placed</div>
          <div class="step <?= in_array($status, ['preparing','out for delivery','picked up by customer','delivered']) ? 'active' : '' ?>">Preparing</div>
          <div class="step <?= in_array($status, ['out for delivery','picked up by customer','delivered']) ? 'active' : '' ?>">Out for Delivery</div>
          <div class="step <?= in_array($status, ['picked up by customer','delivered']) ? 'active' : '' ?>">Picked Up</div>
          <div class="step <?= $status == 'delivered' ? 'active' : '' ?>">Delivered</div>
        </section>
      </article>
    <?php endwhile; ?>
  <?php else: ?>
    <p style="text-align:center; font-size: 1.1rem; color: #555;">You haven't placed any orders yet.</p>
  <?php endif; ?>
</main>

</body>
</html>

<?php
$stmt->close();
$conn->close();
?>