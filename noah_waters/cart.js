// Cart functionality
let cart = [];

// Function to add item to cart
function addToCart(productId, productName, price, image) {
    const formData = new FormData();
    formData.append('action', 'add');
    formData.append('product_id', productId);
    formData.append('quantity', 1);

    fetch('cart_operations.php', {
        method: 'POST',
        credentials: 'same-origin',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showNotification(`${productName} added to cart!`);
            loadCart();
        } else {
            showNotification(data.message || 'Please log in to add items to cart');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showNotification('Error adding item to cart');
    });
}


// Function to load cart
function loadCart() {
    const formData = new FormData();
    formData.append('action', 'get');

    fetch('cart_operations.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            cart = data.items;
            updateCartCount();
            if (typeof displayCart === 'function') {
                displayCart();
            }
        }
    })
    .catch(error => {
        console.error('Error:', error);
    });
}

// Function to update item quantity
function updateQuantity(productId, change) {
    const item = cart.find(item => item.product_id == productId);
    if (!item) return;

    const newQuantity = item.quantity + change;
    if (newQuantity <= 0) {
        removeItem(productId); // ✅ Uses product_id
        return;
    }

    const formData = new FormData();
    formData.append('action', 'update');
    formData.append('product_id', productId); // ✅ Not cart_id
    formData.append('quantity', newQuantity);

    fetch('cart_operations.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            loadCart();
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showNotification('Error updating quantity');
    });
}


// Function to remove item from cart
async function removeItem(productId) {
  const formData = new FormData();
  formData.append('action', 'remove');
  formData.append('product_id', cartId);


  const response = await fetch('cart_operations.php', {
    method: 'POST',
    body: formData
  });
  const result = await response.json();
  console.log('Remove response:', result);

  if (result.success) {
    loadCart();
  } else {
    alert('Failed to remove item: ' + result.message);
  }
}



// Function to update cart count
function updateCartCount() {
    const cartCount = document.getElementById('cart-count');
    if (cartCount) {
        const totalItems = cart.reduce((total, item) => total + parseInt(item.quantity), 0);
        cartCount.textContent = totalItems;
        cartCount.style.display = totalItems > 0 ? 'block' : 'none';
    }
}

// Function to show notification
function showNotification(message) {
    const notification = document.createElement('div');
    notification.className = 'notification';
    notification.textContent = message;
    
    document.body.appendChild(notification);
    
    // Remove notification after 2 seconds
    setTimeout(() => {
        notification.remove();
    }, 2000);
}

// Load cart when page loads
document.addEventListener('DOMContentLoaded', loadCart); 