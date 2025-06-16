<?php
session_start();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Shopping Cart - Noah Waters</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" />
  <link href="https://fonts.googleapis.com/css2?family=Boogaloo&display=swap" rel="stylesheet" />
  <link rel="stylesheet" href="style.css">
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

    .cart-container {
      padding: 20px;
      max-width: 1200px;
      margin: auto;
    }

    .header {
      text-align: center;
      margin-bottom: 30px;
    }

    .header img {
      width: 150px;
    }

    .header h1 {
      font-size: 2.5rem;
      margin: 10px 0;
      color: #112752;
    }

    .cart-layout {
      display: flex;
      justify-content: space-between;
      gap: 20px;
      flex-wrap: wrap;
    }

    .cart-items {
      flex: 2;
      background: rgba(255, 255, 255, 0.95);
      border-radius: 20px;
      padding: 20px;
      min-height: 300px;
    }

    .cart-summary {
      flex: 1;
      background: rgba(255, 255, 255, 0.95);
      border-radius: 20px;
      padding: 20px;
    }

    .summary-line {
      display: flex;
      justify-content: space-between;
      font-size: 1.2rem;
      margin-bottom: 10px;
    }

    .total-line {
      font-size: 1.5rem;
      font-weight: bold;
      margin-top: 15px;
    }

    .checkout-btn {
      display: block;
      width: 90%;
      background-color: #002e6d;
      color: white;
      text-align: center;
      padding: 15px;
      border-radius: 2px;
      font-size: 1.2rem;
      margin: 20px 0 10px;
      text-decoration: none;
    }

    .continue-link {
      text-align: center;
      display: block;
      font-size: 1rem;
      color: #002e6d;
      text-decoration: underline;
    }

        .cart-item {
      display: flex;
      align-items: center;
      justify-content: space-between;
      border-bottom: 1px solid #ccc;
      padding: 12px 0;
      gap: 10px;
      flex-wrap: wrap;
    }

    .cart-item span, .cart-item strong {
      flex: 1;
      font-size: 1.1rem;
    }

    .item-name {
      flex: 2;
    }

    .qty-controls {
      display: flex;
      align-items: center;
      gap: 6px;
    }

    .qty-controls input {
      width: 35px;
      text-align: center;
    }


    .qty-btn {
        padding: 2px 6px;
        margin: 0 4px;
        font-size: 1rem;
        background-color: #002e6d;
        color: white;
        border: none;
        border-radius: 5px;
        cursor: pointer;
    }

    .remove-btn {
        margin-left: 10px;
        background: red;
        color: white;
        border: none;
        padding: 4px 6px;
        border-radius: 5px;
        cursor: pointer;
    }

  </style>
</head>
<body>
  <?php
  if (isset($_SESSION['user_id'])) {
      include 'navbar_loggedin_users.php';
  } else {
      include 'navbar_guest.php';
  }
  ?>

  <div class="cart-container">
    <div class="header">
      
      <h1>Your Cart</h1>
    </div>

    <div class="cart-layout">
      <div class="cart-items" id="cart-items">
        <!-- Cart items will be loaded here -->
      </div>

      <div class="cart-summary">
        <div class="summary-line">
          <span>Subtotal:</span>
          <span id="subtotal">₱0.00</span>
        </div>
        <div class="summary-line">
          <span>Delivery Fee:</span>
          <span>FREE</span>
        </div>
        <div class="summary-line total-line">
          <span>Total:</span>
          <span id="total">₱0.00</span>
        </div>

        <a href="checkout.php" class="checkout-btn">Proceed to Checkout</a>
        <a href="menu.html" class="continue-link">Continue Shopping</a>
      </div>
    </div>
  </div>

  <script>
  async function loadCart() {
    const formData = new FormData();
    formData.append('action', 'get');

    try {
      const response = await fetch('cart_operations.php', {
        method: 'POST',
        body: formData,
        credentials: 'same-origin'
      });
      const data = await response.json();

      const cartItemsEl = document.getElementById('cart-items');
      const subtotalEl = document.getElementById('subtotal');
      const totalEl = document.getElementById('total');

      if (data.success && data.items.length > 0) {
        let html = '';
        let subtotal = 0;

        data.items.forEach(item => {
          html += `
            <div class="cart-item" data-cart-id="${item.product_id}">
              <strong class="item-name">${item.name}</strong>
              <span>₱${parseFloat(item.price).toFixed(2)}</span>
              <div class="qty-controls">
                <button class="qty-btn" onclick="changeQuantity(${item.product_id}, -1)">-</button>
                <input type="text" value="${item.quantity}" id="qty-${item.product_id}" readonly />
                <button class="qty-btn" onclick="changeQuantity(${item.product_id}, 1)">+</button>
              </div>
              <button class="remove-btn" onclick="removeItem(${item.product_id})">🗑️</button>
            </div>`;
        });

        data.items.forEach(item => {
          console.log(item.name, item.price, item.quantity);
          subtotal += parseFloat(item.price) * parseInt(item.quantity);
        });



        cartItemsEl.innerHTML = html;
        subtotalEl.textContent = `₱${subtotal.toFixed(2)}`;
        totalEl.textContent = `₱${subtotal.toFixed(2)}`;
      } else {
        cartItemsEl.innerHTML = "<p>Your cart is empty.</p>";
        subtotalEl.textContent = '₱0.00';
        totalEl.textContent = '₱0.00';
      }
    } catch (e) {
      console.error('Error loading cart:', e);
    }
  }

  async function changeQuantity(productId, delta) {
  const qtyInput = document.getElementById('qty-' + productId);
  let currentQty = parseInt(qtyInput.value);
  let newQty = currentQty + delta;
  if (newQty < 1) return;

  const formData = new FormData();
  formData.append('action', 'update');
  formData.append('product_id', productId); // ✅ Use product_id
  formData.append('quantity', newQty);

  await fetch('cart_operations.php', {
    method: 'POST',
    body: formData,
  });

  loadCart();
}

  async function removeItem(productId) {
  const formData = new FormData();
  formData.append('action', 'remove');
  formData.append('product_id', productId);

  await fetch('cart_operations.php', {
    method: 'POST',
    body: formData,
  });

  loadCart(); // Refresh cart
}




  document.addEventListener('DOMContentLoaded', loadCart);
</script>


</body>
</html>
