<?php
session_start();
header('Content-Type: application/json');

// Database connection
$conn = new mysqli("localhost", "root", "", "noah_waters");
if ($conn->connect_error) {
    echo json_encode(['success' => false, 'message' => 'Database connection failed']);
    exit;
}

$user_id = $_SESSION['user_id'] ?? null;
$action = $_POST['action'] ?? '';

// Initialize guest cart if not set
if (!$user_id && !isset($_SESSION['guest_cart'])) {
    $_SESSION['guest_cart'] = [];
}

// === ADD TO CART ===
if ($action === 'add') {
    $product_id = intval($_POST['product_id'] ?? 0);
    $quantity = intval($_POST['quantity'] ?? 1);

    if ($product_id < 1 || $quantity < 1) {
        echo json_encode(['success' => false, 'message' => 'Invalid product or quantity']);
        exit;
    }

    // Check product existence and get details
    $check = $conn->prepare("SELECT id, name, price, image FROM products WHERE id = ?");
    $check->bind_param("i", $product_id);
    $check->execute();
    $res = $check->get_result();
    if ($res->num_rows === 0) {
        echo json_encode(['success' => false, 'message' => 'Product not found']);
        exit;
    }
    $product = $res->fetch_assoc();

    if ($user_id) {
        // Logged-in user: store in DB cart table
        $stmt = $conn->prepare("SELECT id, quantity FROM cart WHERE user_id = ? AND product_id = ?");
        $stmt->bind_param("ii", $user_id, $product_id);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($row = $result->fetch_assoc()) {
            $newQty = $row['quantity'] + $quantity;
            $update = $conn->prepare("UPDATE cart SET quantity = ? WHERE id = ?");
            $update->bind_param("ii", $newQty, $row['id']);
            $update->execute();
        } else {
            $insert = $conn->prepare("INSERT INTO cart (user_id, product_id, quantity) VALUES (?, ?, ?)");
            $insert->bind_param("iii", $user_id, $product_id, $quantity);
            $insert->execute();
        }
    } else {
        // Guest: store in session guest_cart
        if (isset($_SESSION['guest_cart'][$product_id])) {
            $_SESSION['guest_cart'][$product_id]['quantity'] += $quantity;
        } else {
            $_SESSION['guest_cart'][$product_id] = [
                'product_id' => $product_id,
                'name' => $product['name'],
                'price' => $product['price'],
                'image' => $product['image'],
                'quantity' => $quantity
            ];
        }
    }

    echo json_encode(['success' => true]);
    exit;
}

// === GET CART ITEMS ===
if ($action === 'get') {
    $items = [];

    if ($user_id) {
        $stmt = $conn->prepare("SELECT c.id, c.product_id, c.quantity, p.name, p.price, p.image
                                FROM cart c
                                JOIN products p ON c.product_id = p.id
                                WHERE c.user_id = ?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $res = $stmt->get_result();
        while ($row = $res->fetch_assoc()) {
            $items[] = $row;
        }
    } else {
        foreach ($_SESSION['guest_cart'] as $id => $item) {
            $items[] = [
                'id' => $id, // product_id as id
                'product_id' => $item['product_id'],
                'name' => $item['name'],
                'price' => $item['price'],
                'image' => $item['image'],
                'quantity' => $item['quantity']
            ];
        }
    }

    echo json_encode(['success' => true, 'items' => $items]);
    exit;
}

// === UPDATE ITEM ===
if ($action === 'update') {
    $product_id = intval($_POST['product_id'] ?? 0);
    $quantity = intval($_POST['quantity'] ?? 1);

    if ($product_id < 1 || $quantity < 1) {
        echo json_encode(['success' => false, 'message' => 'Invalid data']);
        exit;
    }

    if ($user_id) {
        $stmt = $conn->prepare("UPDATE cart SET quantity = ? WHERE user_id = ? AND product_id = ?");
        $stmt->bind_param("iii", $quantity, $user_id, $product_id);
        $stmt->execute();
    } else {
        if (isset($_SESSION['guest_cart'][$product_id])) {
            $_SESSION['guest_cart'][$product_id]['quantity'] = $quantity;
        }
    }

    echo json_encode(['success' => true]);
    exit;
}

// === REMOVE ITEM ===
if ($action === 'remove') {
    $product_id = intval($_POST['product_id'] ?? 0);

    if ($product_id < 1) {
        echo json_encode(['success' => false, 'message' => 'Invalid product']);
        exit;
    }

    if ($user_id) {
        $stmt = $conn->prepare("DELETE FROM cart WHERE user_id = ? AND product_id = ?");
        $stmt->bind_param("ii", $user_id, $product_id);
        $stmt->execute();
    } else {
        if (isset($_SESSION['guest_cart'][$product_id])) {
            unset($_SESSION['guest_cart'][$product_id]);
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Item not found in guest cart']);
        }
        exit;
    }

    echo json_encode(['success' => true]);
    exit;
}

// === CLEAR CART ===
if ($action === 'clear') {
    if ($user_id) {
        $stmt = $conn->prepare("DELETE FROM cart WHERE user_id = ?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
    } else {
        $_SESSION['guest_cart'] = [];
    }

    echo json_encode(['success' => true]);
    exit;
}

echo json_encode(['success' => false, 'message' => 'Invalid action']);
