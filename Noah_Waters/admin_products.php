<?php
session_start();
require 'config.php'; 

// chinecheck kung admin or user ba yung nagla login
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['admin'])) {
    header("Location: login.php");
    exit;
}

$errors = [];
$success = "";

// debug logging para saa POST data
error_log("POST data: " . print_r($_POST, true));

// code para sa pag add ng product
if (isset($_POST['add_product'])) {
    $name = trim($_POST['name']);
    $category = $_POST['category'];
    $price = floatval($_POST['price']);
    $image = trim($_POST['image']);
    // kung borrowable ba yung product
    $isBorrowable = isset($_POST['is_borrowable']) && $_POST['is_borrowable'] == '1' ? 1 : 0;
    
    error_log("Add Product - isBorrowable value: " . $isBorrowable);

    // nagva validate ng pag input ng data
    if (empty($name)) $errors[] = "Product name is required.";
    if (!in_array($category, ['container', 'bottle'])) $errors[] = "Invalid category selected.";
    if (!is_numeric($price) || $price < 0) $errors[] = "Price must be a non-negative number.";
    if (!preg_match('/\.(jpg|jpeg|png)$/i', $image)) $errors[] = "Only JPG, JPEG, and PNG images are allowed.";

    if (empty($errors)) {
        $stmt = $conn->prepare("INSERT INTO products (name, category, price, image, is_borrowable) VALUES (?, ?, ?, ?, ?)");
        if ($stmt) {
            $stmt->bind_param("ssdsi", $name, $category, $price, $image, $isBorrowable);
            if ($stmt->execute()) {
                $success = "Product added successfully!";
                error_log("Product added with isBorrowable: " . $isBorrowable);
                header("Location: admin_products.php");
                exit;
            } else {
                $errors[] = "Error adding product: " . $stmt->error;
                error_log("Error adding product: " . $stmt->error);
            }
            $stmt->close();
        } else {
            $errors[] = "Database error: " . $conn->error;
            error_log("Database error: " . $conn->error);
        }
    }
}

// code sa pag edit ng product
if (isset($_POST['edit_product'])) {
    $id = intval($_POST['id']);
    $name = trim($_POST['name']);
    $category = $_POST['category'];
    $price = floatval($_POST['price']);
    $image = trim($_POST['image']);
    // borrowable button ulit
    $isBorrowable = isset($_POST['is_borrowable']) && $_POST['is_borrowable'] == '1' ? 1 : 0;
    
    error_log("Edit Product - isBorrowable value: " . $isBorrowable);

    // validate ulit ng pag input ng product kung tama ba yung nilagay
    if (empty($name)) $errors[] = "Product name is required.";
    if (!in_array($category, ['container', 'bottle'])) $errors[] = "Invalid category selected.";
    if (!is_numeric($price) || $price < 0) $errors[] = "Price must be a non-negative number.";
    if (!preg_match('/\.(jpg|jpeg|png)$/i', $image)) $errors[] = "Only JPG, JPEG, and PNG images are allowed.";

    if (empty($errors)) {
        $stmt = $conn->prepare("UPDATE products SET name = ?, category = ?, price = ?, image = ?, is_borrowable = ? WHERE id = ?");
        if ($stmt) {
            $stmt->bind_param("ssdsii", $name, $category, $price, $image, $isBorrowable, $id);
            if ($stmt->execute()) {
                $success = "Product updated successfully!";
                error_log("Product updated with isBorrowable: " . $isBorrowable);
                header("Location: admin_products.php");
                exit;
            } else {
                $errors[] = "Error updating product: " . $stmt->error;
                error_log("Error updating product: " . $stmt->error);
            }
            $stmt->close();
        } else {
            $errors[] = "Database error: " . $conn->error;
            error_log("Database error: " . $conn->error);
        }
    }
}

// code sa pagdelete ng product
if (isset($_POST['delete'])) {
    $id = intval($_POST['id']);
    if ($id) {
        $stmt = $conn->prepare("DELETE FROM products WHERE id = ?");
        if ($stmt) {
            $stmt->bind_param("i", $id);
            if ($stmt->execute()) {
                $success = "Product deleted successfully!";
            } else {
                $errors[] = "Error deleting product: " . $stmt->error;
            }
            $stmt->close();
        } else {
            $errors[] = "Database error: " . $conn->error;
        }
        header("Location: admin_products.php");
        exit;
    }
}

// eto yung pagdisplay ng mga products
$result = $conn->query("SELECT * FROM products ORDER BY id DESC");
if (!$result) {
    $errors[] = "Error fetching products: " . $conn->error;
}
$products = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <title>Admin Products</title>
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
        .form-label {
            color: white;
            font-size: 1.1em;
            margin-bottom: 8px;
            font-family: "Boogaloo", sans-serif;
        }
        .row {
            max-width: 900px;
            margin-left: auto;
            margin-right: auto;
        }
        .form-select, .form-control {
            background-color: rgba(255, 255, 255, 0.9);
            border: 1px solid #ddd;
            border-radius: 8px;
            padding: 8px 12px;
            font-size: 1.1em;
            font-family: "Boogaloo", sans-serif;
            width: 100%;
            min-width: 200px;
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
        .product-img {
            max-width: 80px;
            height: auto;
            border-radius: 8px;
        }
        .form-check-input.toggle-lg {
            width: 3.5em;
            height: 2em;
            cursor: pointer;
            accent-color: #28a745;
            transition: background-color 0.3s ease;
        }
        .alert {
            font-family: "Boogaloo", sans-serif;
            font-size: 1.1em;
            border-radius: 10px;
            margin-bottom: 18px;
            padding: 12px 18px;
        }
    </style>
</head>
<body>
    <!--navigation bar sa taas para sa admin-->
<?php include 'navbar_admin.php'; ?>

<div class="container container-box">
    <h2 class="mb-4 text-center">Manage Products</h2>

    <!-- product form -->
    <form method="POST" class="mb-5">
        <input type="hidden" name="action" value="add_product">
        <div class="row g-3 align-items-end">
            <div class="col-md-3">
                <label for="name" class="form-label">Name</label>
                <input required type="text" name="name" id="name" class="form-control" placeholder="Product name" />
            </div>
            <div class="col-md-3">
                <label for="category" class="form-label">Category</label>
                <select required name="category" id="category" class="form-select">
                    <option value="">Select category</option>
                    <option value="container">Container</option>
                    <option value="bottle">Bottle</option>
                </select>
            </div>
            <div class="col-md-3">
                <label for="price" class="form-label">Price (₱)</label>
                <input required type="number" step="0.01" min="0" name="price" id="price" class="form-control" placeholder="0.00" />
            </div>
            <div class="col-md-3">
                <label for="image" class="form-label">Image Filename or Path</label>
                <input required type="text" name="image" id="image" class="form-control" placeholder="image.jpg or images/image.jpg" />
            </div>
        </div>

        <!--borrowable toggle at add button-->
        <div class="row mt-4 justify-content-center align-items-center">
            <div class="col-auto d-flex align-items-center">
                <div class="form-check form-switch">
                    <input class="form-check-input toggle-lg" type="checkbox" id="is_borrowable" name="is_borrowable" value="1">
                    <label class="form-check-label text-white fs-5 ms-2 mb-0" for="is_borrowable">Borrowable</label>
                </div>
            </div>
            <div class="col-auto">
                <button type="submit" name="add_product" class="btn btn-success btn-lg px-4">Add Product</button>
            </div>
        </div>
    </form>

    <!--display ng products -->
    <table class="table table-hover text-white align-middle">
        <thead>
            <tr>
                <th>Image</th>
                <th>Name</th>
                <th>Category</th>
                <th>Borrowable</th>
                <th>Price (₱)</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($products)): ?>
                <tr><td colspan="6" class="text-center">No products found.</td></tr>
            <?php else: ?>
                <?php foreach ($products as $product): ?>
                <tr>
                    <td><img src="<?= htmlspecialchars($product['image']) ?>" alt="<?= htmlspecialchars($product['name']) ?>" class="product-img" /></td>
                    <td><?= htmlspecialchars($product['name']) ?></td>
                    <td><?= ucfirst($product['category']) ?></td>
                    <td><?= $product['is_borrowable'] ? 'Yes' : 'No' ?></td>
                    <td>₱<?= number_format($product['price'], 2) ?></td>
                    <td>
                        <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#editModal<?= $product['id'] ?>">Edit</button>
                        <form method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to delete this product?');">
                            <input type="hidden" name="id" value="<?= $product['id'] ?>">
                            <input type="hidden" name="action" value="delete">
                            <button type="submit" name="delete" class="btn btn-danger btn-sm">Delete</button>
                        </form>
                    </td>
                </tr>

                <!--edit button -->
                <div class="modal fade" id="editModal<?= $product['id'] ?>" tabindex="-1">
                  <div class="modal-dialog">
                    <div class="modal-content text-dark">
                      <form method="POST">
                        <input type="hidden" name="id" value="<?= $product['id'] ?>">
                        <input type="hidden" name="action" value="edit_product">
                        <div class="modal-header">
                          <h5 class="modal-title">Edit Product</h5>
                          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            <div class="mb-3">
                              <label for="name<?= $product['id'] ?>" class="form-label">Name</label>
                              <input required type="text" class="form-control" id="name<?= $product['id'] ?>" name="name" value="<?= htmlspecialchars($product['name']) ?>" />
                            </div>
                            <div class="mb-3">
                              <label for="category<?= $product['id'] ?>" class="form-label">Category</label>
                              <select required class="form-select" id="category<?= $product['id'] ?>" name="category">
                                  <option value="container" <?= $product['category'] === 'container' ? 'selected' : '' ?>>Container</option>
                                  <option value="bottle" <?= $product['category'] === 'bottle' ? 'selected' : '' ?>>Bottle</option>
                              </select>
                            </div>
                            <div class="mb-3">
                              <label for="price<?= $product['id'] ?>" class="form-label">Price (₱)</label>
                              <input required type="number" step="0.01" min="0" class="form-control" id="price<?= $product['id'] ?>" name="price" value="<?= number_format($product['price'], 2, '.', '') ?>" />
                            </div>
                            <div class="mb-3">
                              <label for="image<?= $product['id'] ?>" class="form-label">Image URL</label>
                              <input required type="text" name="image" id="image" class="form-control" placeholder="image.jpg or images/image.jpg" />
                            </div>
                            <div class="form-check form-switch">
                                <input class="form-check-input toggle-lg" type="checkbox" name="is_borrowable" id="is_borrowable_edit<?= $product['id'] ?>" value="1" <?= $product['is_borrowable'] ? 'checked' : '' ?> >
                                <label class="form-check-label" for="is_borrowable_edit<?= $product['id'] ?>">Borrowable Container</label>
                            </div>
                        </div>
                        <div class="modal-footer">
                          <button type="submit" name="edit_product" class="btn btn-primary">Save changes</button>
                          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        </div>
                      </form>
                    </div>
                  </div>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
document.querySelector("form").addEventListener("submit", function (e) {
    const imageInput = document.querySelector("#image");
    const url = imageInput.value.toLowerCase();
    if (!url.endsWith(".jpg") && !url.endsWith(".jpeg") && !url.endsWith(".png")) {
        alert("Only .jpg, .jpeg, or .png image URLs are allowed.");
        e.preventDefault();
    }
});
</script>

</body>
</html>