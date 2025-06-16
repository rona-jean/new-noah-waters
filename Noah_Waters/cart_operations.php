<?php
session_start();
header('Content-Type: application/json');

// eto yung database connection
$conn = new mysqli("localhost", "root", "", "noah_waters");
if ($conn->connect_error) {
    echo json_encode(['success' => false, 'message' => 'Database connection failed']);
    exit;
}

$user_id = $_SESSION['user_id'] ?? null;
$action = $_POST['action'] ?? '';

// pag initialize ng cart ng guest kung hindi pa
if (!$user_id && !isset($_SESSION['guest_cart'])) {
    $_SESSION['guest_cart'] = [];
}

//add to cart
if ($action === 'add') {
    $product_id = intval($_POST['product_id'] ?? 0);
    $quantity = intval($_POST['quantity'] ?? 1);

    if ($product_id < 1 || $quantity < 1) {
        echo json_encode(['success' => false, 'message' => 'Invalid product or quantity']);
        exit;
    }

    // check kung nageexist ba yung product and pagkuha ng details
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
        //kapag logged-in user iistore sa cart table kapag may laman yung cart nila
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
        // code para pwede din makapag add to cart yung guest
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


//kinukuha yung laman ng cart items
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
                'id' => $id, // productid as id
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

// update items
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

// pag remove ng item
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

