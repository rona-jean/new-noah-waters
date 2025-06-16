// cart functionality
let cart = [];

//function para makapag add ng items/products sa cart
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


//function to load cart
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

// Function para maupdate yung quantity ng items
function updateQuantity(productId, change) {
    const item = cart.find(item => item.product_id == productId);
    if (!item) return;

    const newQuantity = item.quantity + change;
    if (newQuantity <= 0) {
        removeItem(productId); // productid
        return;
    }

    const formData = new FormData();
    formData.append('action', 'update');
    formData.append('product_id', productId); 
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


//function para maremove yung item sa cart
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



//function para maupdate yung bilang ng cart
function updateCartCount() {
    const cartCount = document.getElementById('cart-count');
    if (cartCount) {
        const totalItems = cart.reduce((total, item) => total + parseInt(item.quantity), 0);
        cartCount.textContent = totalItems;
        cartCount.style.display = totalItems > 0 ? 'block' : 'none';
    }
}

//function para mag notif
function showNotification(message) {
    const notification = document.createElement('div');
    notification.className = 'notification';
    notification.textContent = message;
    
    document.body.appendChild(notification);
    
    setTimeout(() => {
        notification.remove();
    }, 2000);
}

//load ng cart kapag nagreload yung page
document.addEventListener('DOMContentLoaded', loadCart); 