<?php
session_start();
$userLoggedIn = isset($_SESSION['user_id']);

require_once 'config.php'; // Make sure this sets up $conn as your DB connection

// Fetch is_new_user if logged in
$isNewUser = false;
$hasBorrowed = false;
if ($userLoggedIn) {
    $stmt = $conn->prepare("SELECT is_new_user FROM users WHERE id = ?");
    $stmt->bind_param("i", $_SESSION['user_id']);
    $stmt->execute();
    $stmt->bind_result($isNewUserFlag);
    $stmt->fetch();
    $stmt->close();
    $isNewUser = ($isNewUserFlag == 1);

    // Check if user has already ordered a borrowable container
    $sql = "SELECT COUNT(*) FROM order_items oi
            JOIN products p ON oi.product_id = p.id
            JOIN orders o ON oi.order_id = o.id
            WHERE o.user_id = ? AND p.category = 'container' AND p.is_borrowable = 1";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $_SESSION['user_id']);
    $stmt->execute();
    $stmt->bind_result($borrowedCount);
    $stmt->fetch();
    $stmt->close();
    $hasBorrowed = ($borrowedCount > 0);
}

// Fetch products from database (include is_borrowable)
$sql = "SELECT id, name, price, image, category, is_borrowable FROM products ORDER BY category, name";
$result = $conn->query($sql);

if (!$result) {
  die("Database query failed: " . $conn->error);
}

$containers = [];
$bottles = [];

while ($row = $result->fetch_assoc()) {
    $cat = strtolower(trim($row['category']));
    if ($cat === 'container') {
        $containers[] = $row;
    } elseif ($cat === 'bottle') {
        $bottles[] = $row;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Menu - Noah Waters</title>
  <link rel="stylesheet" href="style.css" />
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" />
  <link href="https://fonts.googleapis.com/css2?family=Boogaloo&display=swap" rel="stylesheet" />
  <style>
    /* Simple notification styles */
.notification {
  position: fixed;
  bottom: 20px;
  right: 20px;
  background-color: #112752;
  color: #79c7ff;
  padding: 15px 25px;
  border-radius: 25px;
  font-size: 1rem;
  animation: slideIn 0.3s ease-out;
  z-index: 1000;
}

@keyframes slideIn {
  from {
      transform: translateX(100%);
      opacity: 0;
  }
  to {
      transform: translateX(0);
      opacity: 1;
  }
}

@keyframes slideOut {
  from {
      transform: translateX(0);
      opacity: 1;
  }
  to {
      transform: translateX(100%);
      opacity: 0;
  }
}

.cart-count {
  background: red;
  border-radius: 50%;
  color: white;
  padding: 2px 7px;
  font-size: 0.9rem;
  position: relative;
  top: -10px;
  left: -10px;
  display: none;
}
  </style>
</head>
<body>
  <?php if ($userLoggedIn) {
      include 'navbar_loggedin_users.php';
    } else {
      include 'navbar_guest.php';
    }
  ?>

  <div class="menu-container">
    <div class="menu-header">
      <h1>Our Products</h1>
    </div>

    <div class="menu-section">
      <h2>Water Containers</h2>
      <div class="product-grid">
        <?php foreach ($containers as $p): ?>
          <div class="product-card" style="position:relative;">
            <?php if ($userLoggedIn && $isNewUser && !$hasBorrowed && !empty($p['is_borrowable'])): ?>
              <div style="position:absolute;top:10px;left:10px;right:10px;z-index:2;text-align:center;pointer-events:none;">
                <span style="background:rgba(255,255,255,0.85);color:#b22222;font-size:1.1em;font-weight:bold;padding:4px 10px;border-radius:8px;display:inline-block;">
                  For New Customers <br><span style="font-size:1.3em;">Borrow</span>
                </span>
              </div>
            <?php endif; ?>
            <img src="<?= htmlspecialchars($p['image']) ?>" alt="<?= htmlspecialchars($p['name']) ?>" class="product-image" />
            <h3><?= htmlspecialchars($p['name']) ?></h3>
            <p class="price">₱<?= number_format($p['price'], 2) ?></p>
            <button class="order-now-btn"
                    data-product-id="<?= $p['id'] ?>"
                    data-product-name="<?= htmlspecialchars($p['name']) ?>"
                    data-product-price="<?= $p['price'] ?>"
                    data-product-image="<?= htmlspecialchars($p['image']) ?>">Add to Cart</button>
          </div>
        <?php endforeach; ?>
      </div>
    </div>

    <div class="menu-section">
      <h2>Water Bottles</h2>
      <div class="product-grid">
        <?php foreach ($bottles as $p): ?>
          <div class="product-card">
            <img src="<?= htmlspecialchars($p['image']) ?>" alt="<?= htmlspecialchars($p['name']) ?>" class="product-image" />
            <h3><?= htmlspecialchars($p['name']) ?></h3>
            <p class="price">₱<?= number_format($p['price'], 2) ?></p>
            <button class="order-now-btn"
                    data-product-id="<?= $p['id'] ?>"
                    data-product-name="<?= htmlspecialchars($p['name']) ?>"
                    data-product-price="<?= $p['price'] ?>"
                    data-product-image="<?= htmlspecialchars($p['image']) ?>"
            >
              Add to Cart
            </button>
          </div>
        <?php endforeach; ?>
      </div>
    </div>
  </div>

  <script>
    const isLoggedIn = <?= json_encode($userLoggedIn) ?>;

    function showNotification(message) {
      const notif = document.createElement('div');
      notif.className = 'notification';
      notif.textContent = message;
      document.body.appendChild(notif);
      setTimeout(() => notif.remove(), 2500);
    }

    function updateCartCount(count) {
      const cartCountEl = document.getElementById('cart-count');
      if (!cartCountEl) return;
      if (count > 0) {
        cartCountEl.textContent = count;
        cartCountEl.style.display = 'inline-block';
      } else {
        cartCountEl.style.display = 'none';
      }
    }

    async function addToCart(productId, productName) {
      const formData = new FormData();
      formData.append('action', 'add');
      formData.append('product_id', productId);
      formData.append('quantity', 1);

      try {
        const response = await fetch('cart_operations.php', {
          method: 'POST',
          body: formData,
          credentials: 'same-origin'
        });
        const data = await response.json();

        if (data.success) {
          showNotification(`${productName} added to cart!`);
          loadCartCount();
        } else {
          showNotification(data.message || 'Failed to add to cart.');
        }
      } catch (error) {
        console.error(error);
        showNotification('Error adding to cart.');
      }
    }

    async function loadCartCount() {
      const formData = new FormData();
      formData.append('action', 'get');

      try {
        const response = await fetch('cart_operations.php', {
          method: 'POST',
          body: formData,
          credentials: 'same-origin'
        });
        const data = await response.json();

        if (data.success && Array.isArray(data.items)) {
          const totalQty = data.items.reduce((acc, item) => acc + parseInt(item.quantity, 10), 0);
          updateCartCount(totalQty);
        } else {
          updateCartCount(0);
        }
      } catch (e) {
        console.error(e);
        updateCartCount(0);
      }
    }

    // Attach event listeners after DOM loads
    document.addEventListener('DOMContentLoaded', () => {
      document.querySelectorAll('.order-now-btn').forEach(button => {
        button.addEventListener('click', () => {
          const pid = button.getAttribute('data-product-id');
          const pname = button.getAttribute('data-product-name');
          addToCart(pid, pname);
        });
      });

      loadCartCount();
    });
  </script>
</body>
</html>
