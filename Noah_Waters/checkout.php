<?php
session_start();

$isLoggedIn = isset($_SESSION['user_id']);
$userId = $isLoggedIn ? $_SESSION['user_id'] : null;

//code para tignan kung empty ba yung cart
$cartEmpty = true;
if ($isLoggedIn) {
    $conn = new mysqli("localhost", "root", "", "noah_waters");
    if ($conn->connect_error) {
        die("Database connection failed: " . $conn->connect_error);
    }
    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM cart WHERE user_id = ?");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $cartEmpty = ($row['count'] == 0);
    $stmt->close();
    $conn->close();
} else {
    $cartEmpty = empty($_SESSION['guest_cart']);
}

if ($cartEmpty) {
    header("Location: new_cart.php");
    exit();
}

// information variables
$fullname = '';
$phone = '';
$deliveryAddress = '';

// Fetch user info from database if logged in
if ($isLoggedIn && isset($_SESSION['user_id'])) {
    $conn = new mysqli("localhost", "root", "", "noah_waters");
    if ($conn->connect_error) {
        die("Database connection failed: " . $conn->connect_error);
    }

    $stmt = $conn->prepare("SELECT fullname, phone, address FROM users WHERE id = ?");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $stmt->bind_result($fullname, $phone, $deliveryAddress);
    $stmt->fetch();
    $stmt->close();
    $conn->close();
}

$cart = [];

if ($isLoggedIn) {
    //connect to database
    $conn = new mysqli("localhost", "root", "", "noah_waters");
    if ($conn->connect_error) {
        die("Database connection failed: " . $conn->connect_error);
    }

    //kunin yung cart items ng user 
    $stmt = $conn->prepare("SELECT c.product_id, c.quantity, p.name, p.price FROM cart c JOIN products p ON c.product_id = p.id WHERE c.user_id = ?");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();

    while ($row = $result->fetch_assoc()) {
        $cart[] = [
            'product' => $row['name'],
            'quantity' => $row['quantity'],
            'price' => $row['price'],
        ];
    }
    $stmt->close();
    $conn->close();

} else {
    //guest cart naman
    if (isset($_SESSION['guest_cart']) && is_array($_SESSION['guest_cart'])) {
        foreach ($_SESSION['guest_cart'] as $item) {
            $cart[] = [
                'product' => $item['name'],//name ng product
                'quantity' => $item['quantity'],
                'price' => $item['price'],
            ];
        }
    }
}

// total 
$subtotal = 0;
foreach ($cart as $item) {
    $subtotal += $item['price'] * $item['quantity'];
}
$total = $subtotal; 
// titignan kung alin sa dalawang php file yung gagamitin pang user ba o pang guest?
$formAction = $isLoggedIn ? 'submit_user_order.php' : 'submit_guest_order.php';

?>


<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Checkout - Noah Waters</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Boogaloo&display=swap" rel="stylesheet">

  <style>
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

    .checkout-container {
        max-width: 1200px;
        margin: 30px auto;
        padding: 20px;
        background: rgba(255, 255, 255, 0.95);
        border-radius: 20px;
        display: flex;
        gap: 20px;
        flex-wrap: wrap;
    }

    .form-box, .summary-box {
        background: rgba(255, 255, 255, 0.95);
        border-radius: 20px;
        padding: 20px;
        flex: 1;
        min-width: 300px;
        box-sizing: border-box;
    }

    .form-box h2, .summary-box h2 {
        margin-top: 0;
        margin-bottom: 20px;
        font-size: 2rem;
        color: #002e6d;
    }

    .form-box label {
        display: block;
        font-size: 1.2rem;
        margin-bottom: 6px;
        color: #002e6d;
    }

    .form-box input[type="text"],
    .form-box textarea,
    .summary-box select {
        width: 100%;
        padding: 8px 10px;
        margin-bottom: 20px;
        border-radius: 8px;
        border: 1px solid #ccc;
        font-size: 1rem;
        font-family: "Boogaloo", sans-serif;
    }

    .summary-box ul {
        list-style-type: none;
        padding-left: 0;
        margin-bottom: 20px;
        font-size: 1.1rem;
        color: #002e6d;
    }

    .summary-box ul li {
        margin-bottom: 8px;
    }

    .summary-box h4,
    .summary-box h3 {
        margin: 10px 0;
        color: #002e6d;
    }

    .summary-box p {
        font-size: 1.1rem;
        margin: 10px 0;
        color: #002e6d;
    }

    /* Shipping method container */
    .shipping-method {
        margin-bottom: 25px;
        font-size: 1.2rem;
        color: #002e6d;
    }

    .shipping-method label {
        margin-right: 25px;
        cursor: pointer;
    }

    .shipping-method input[type="radio"] {
        margin-right: 8px;
        cursor: pointer;
        vertical-align: middle;
    }

    /* Pickup options */
    #pickup-options {
        margin-top: 10px;
    }

    /* Submit button */
    .checkout-container button[type="submit"] {
        background-color: #002e6d;
        color: white;
        border: none;
        padding: 15px 0;
        width: 100%;
        font-size: 1.4rem;
        border-radius: 8px;
        cursor: pointer;
        font-family: "Boogaloo", sans-serif;
        transition: background-color 0.3s ease;
    }

    .checkout-container button[type="submit"]:hover {
        background-color: #0050a4;
    }
  </style>
</head>
<body>
    <!--navigation bar base sa gumagamit-->
    <?php
    if (isset($_SESSION['user_id'])) {
        include 'navbar_loggedin_users.php';
    } else {
        include 'navbar_guest.php';
    }
    ?>
    <form action="<?= htmlspecialchars($formAction) ?>" method="POST" class="checkout-container">

    <!-- info ng guest or user -->
<?php if (!$isLoggedIn): ?>
  <div class="form-box">
    <h2>Guest Information</h2>

    <label for="fullname">Full Name:</label>
    <input type="text" id="fullname" name="fullname" required>

    <label for="phone">Phone:</label>
    <input type="text" id="phone" name="phone" required>

    <label for="address">Address:</label>
    <textarea id="address" name="address" rows="2" required></textarea>

    <label for="notes">Additional Info (notes):</label>
    <textarea id="notes" name="notes" rows="3"></textarea>
  </div>
<?php else: ?>
  <div class="form-box">
    <h2>User Information</h2><!--reqd only lang sa user kase nakasave na yung kanilang info-->

    <label>Full Name:</label>
    <input type="text" value="<?= htmlspecialchars($fullname) ?>" readonly>
    <input type="hidden" name="fullname" value="<?= htmlspecialchars($fullname) ?>">

    <label>Phone:</label>
    <input type="text" value="<?= htmlspecialchars($phone) ?>" readonly>
    <input type="hidden" name="phone" value="<?= htmlspecialchars($phone) ?>">

    <label for="address">Address:</label>
    <textarea id="address" name="delivery_address" rows="2" required><?= htmlspecialchars($deliveryAddress) ?></textarea>

    <label for="notes">Additional Info (notes):</label>
    <textarea id="notes" name="notes" rows="3"></textarea>
  </div>
<?php endif; ?>

    <!--shipping and order summary parng resibo-->
    <div class="summary-box">
        <h2>Shipping Method</h2>
        <hr>

        <div class="shipping-methods">
        <label>
            <input type="radio" name="shipping_method" value="Delivery" id="delivery" checked onclick="togglePickupOptions()">Delivery</label><br>
        <label>
            <input type="radio" name="shipping_method" value="Pickup" id="pickup" onclick="togglePickupOptions()">Pick-up</label>

      <div id="pickup-options" style="display: none; margin-top: 10px;">
          <label for="pickup_time">Pick-up Time:</label>
          <br>
          <select name="pickup_time" id="pickup_time">
              <option value="As soon as possible">As soon as possible</option>
              <option value="In 30 minutes">In 30 minutes</option>
              <option value="In 1 hour">In 1 hour</option>
              <option value="Later today">Later today</option>
          </select>
      </div>
    </div>
    <br>
    <h2>Order Summary</h2>
    <hr>

    <ul class="order-list">
    <?php if (!empty($cart)): ?>
        <?php foreach ($cart as $item): ?>
            <li>
                <span class="order-item-name"><?= htmlspecialchars($item['product']); ?></span> 
                <span class="order-item-qty">× <?= intval($item['quantity']); ?></span>  
                <span class="order-item-total">₱<?= number_format($item['price'] * $item['quantity'], 2); ?></span>
            </li>
        <?php endforeach; ?>
    <?php else: ?>
        <li>Your cart is empty.</li>
    <?php endif; ?>
</ul>


    <div class="summary-totals">
        <div><strong>Subtotal:</strong> ₱<?= number_format($subtotal, 2); ?></div>
        <div><strong>Shipping:</strong> FREE</div>
        <div class="total"><strong>Total:</strong> ₱<?= number_format($total, 2); ?></div>
    </div>
    
    <br>
    <button type="submit">✅ Place Order</button>
</div>

</form>

<script>
function togglePickupOptions() {
  const deliveryRadio = document.getElementById('delivery');
  const pickupOptions = document.getElementById('pickup-options');
  if (deliveryRadio.checked) {
    pickupOptions.style.display = 'none';
  } else {
    pickupOptions.style.display = 'block';
  }
}
</script>

</body>
</html>
