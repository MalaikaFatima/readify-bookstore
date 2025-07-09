<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once 'includes/db.php';

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// CSRF Protection
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Admin check
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] != 'admin') {
    header("Location: login.php");
    exit;
}

// Image upload function
function handleImageUpload($fileInput, $targetDir) {
    $targetDir = rtrim($targetDir, '/') . '/';
    
    if (!file_exists($targetDir)) {
        mkdir($targetDir, 0755, true);
    }

    if ($fileInput['error'] === UPLOAD_ERR_OK) {
        if ($fileInput['size'] > 5000000) {
            $_SESSION['error'] = "File is too large. Maximum 5MB allowed.";
            return false;
        }
        
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime = finfo_file($finfo, $fileInput['tmp_name']);
        finfo_close($finfo);
        
        $allowed = ['image/jpeg', 'image/png', 'image/gif'];
        if (!in_array($mime, $allowed)) {
            $_SESSION['error'] = "Invalid image type. Only JPG, PNG, GIF allowed.";
            return false;
        }

        $extension = strtolower(pathinfo($fileInput['name'], PATHINFO_EXTENSION));
        $filename = uniqid() . '.' . $extension;
        $targetPath = $targetDir . $filename;
        
        if (move_uploaded_file($fileInput['tmp_name'], $targetPath)) {
            chmod($targetPath, 0644);
            return $filename;
        }
    }
    $_SESSION['error'] = "Image upload failed (Error: {$fileInput['error']})";
    return false;
}

// Add Category
if (isset($_POST['add_category'])) {
    if ($_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $_SESSION['error'] = "Invalid CSRF token.";
        header("Location: admin.php");
        exit;
    }
    
    $name = trim($_POST['category_name']);
    $image = handleImageUpload($_FILES['category_image'], 'assets/img/categories/');
    
    if ($name && $image) {
        $stmt = $conn->prepare("INSERT INTO categories (name, image) VALUES (?, ?)");
        $stmt->bind_param("ss", $name, $image);
        if ($stmt->execute()) {
            $_SESSION['success'] = "Category added successfully";
        } else {
            $_SESSION['error'] = "Failed to add category: " . $conn->error;
        }
    }
    header("Location: admin.php#categories");
    exit;
}


if (isset($_POST['add_product'])) {
    if ($_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $_SESSION['error'] = "Invalid CSRF token.";
        header("Location: admin.php");
        exit;
    }
    
    $title = trim($_POST['title']);
    $description = trim($_POST['description']);
    $price = (float)$_POST['price'];
    $category_id = (int)$_POST['category_id'];
    $is_new_arrival = isset($_POST['is_new_arrival']) ? 1 : 0; 
    
    $image = handleImageUpload($_FILES['product_image'], 'assets/img/products/');
    
    if ($title && $description && $price > 0 && $category_id > 0 && $image) {
        $stmt = $conn->prepare("INSERT INTO books (title, description, price, category_id, image, is_new_arrival) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("ssdiss", $title, $description, $price, $category_id, $image, $is_new_arrival);
        if ($stmt->execute()) {
            $_SESSION['success'] = "Product added successfully" . ($is_new_arrival ? " and marked as New Arrival" : "");
        } else {
            $_SESSION['error'] = "Failed to add product: " . $conn->error;
        }
    } else {
        $_SESSION['error'] = "All fields are required and must be valid";
    }
    header("Location: admin.php#products");
    exit;
}

// Delete Category
if (isset($_GET['delete_category'])) {
    if (!isset($_GET['csrf_token']) || $_GET['csrf_token'] !== $_SESSION['csrf_token']) {
        $_SESSION['error'] = "Invalid CSRF token.";
        header("Location: admin.php");
        exit;
    }
    
    $category_id = (int)$_GET['delete_category'];
    
    $check_products = $conn->prepare("SELECT COUNT(*) as product_count FROM books WHERE category_id = ?");
    $check_products->bind_param("i", $category_id);
    $check_products->execute();
    $result = $check_products->get_result()->fetch_assoc();
    
    if ($result['product_count'] > 0) {
        $_SESSION['error'] = "Cannot delete category - it contains products";
    } else {
        $get_image = $conn->prepare("SELECT image FROM categories WHERE id = ?");
        $get_image->bind_param("i", $category_id);
        $get_image->execute();
        $image_result = $get_image->get_result()->fetch_assoc();
        
        $stmt = $conn->prepare("DELETE FROM categories WHERE id = ?");
        $stmt->bind_param("i", $category_id);
        
        if ($stmt->execute()) {
            if (!empty($image_result['image'])) {
                $image_path = 'assets/img/categories/' . $image_result['image'];
                if (file_exists($image_path)) {
                    unlink($image_path);
                }
            }
            $_SESSION['success'] = "Category deleted successfully";
        } else {
            $_SESSION['error'] = "Failed to delete category: " . $conn->error;
        }
    }
    header("Location: admin.php#categories");
    exit;
}

// Delete Product
if (isset($_GET['delete_product'])) {
    if (!isset($_GET['csrf_token']) || $_GET['csrf_token'] !== $_SESSION['csrf_token']) {
        $_SESSION['error'] = "Invalid CSRF token.";
        header("Location: admin.php");
        exit;
    }
    
    $product_id = (int)$_GET['delete_product'];
    
    $get_image = $conn->prepare("SELECT image FROM books WHERE id = ?");
    $get_image->bind_param("i", $product_id);
    $get_image->execute();
    $image_result = $get_image->get_result()->fetch_assoc();
    
    $stmt = $conn->prepare("DELETE FROM books WHERE id = ?");
    $stmt->bind_param("i", $product_id);
    
    if ($stmt->execute()) {
        if (!empty($image_result['image'])) {
            $image_path = 'assets/img/products/' . $image_result['image'];
            if (file_exists($image_path)) {
                unlink($image_path);
            }
        }
        $_SESSION['success'] = "Product deleted successfully";
    } else {
        $_SESSION['error'] = "Failed to delete product: " . $conn->error;
    }
    header("Location: admin.php#products");
    exit;
}

// Toggle New Arrival Status
if (isset($_GET['toggle_new_arrival'])) {
    if (!isset($_GET['csrf_token']) || $_GET['csrf_token'] !== $_SESSION['csrf_token']) {
        $_SESSION['error'] = "Invalid CSRF token.";
        header("Location: admin.php");
        exit;
    }
    
    $product_id = (int)$_GET['toggle_new_arrival'];
    
    $stmt = $conn->prepare("UPDATE books SET is_new_arrival = NOT is_new_arrival WHERE id = ?");
    $stmt->bind_param("i", $product_id);
    
    if ($stmt->execute()) {
        $_SESSION['success'] = "New arrival status updated";
    } else {
        $_SESSION['error'] = "Failed to update status: " . $conn->error;
    }
    header("Location: admin.php#products");
    exit;
}

// Update Order Status
if (isset($_POST['update_status'])) {
    if ($_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $_SESSION['error'] = "Invalid CSRF token.";
        header("Location: admin.php#orders");
        exit;
    }
    
    $order_id = (int)$_POST['order_id'];
    $status = $_POST['status'];
    
    $stmt = $conn->prepare("UPDATE orders SET status = ? WHERE id = ?");
    $stmt->bind_param("si", $status, $order_id);
    if ($stmt->execute()) {
        $_SESSION['success'] = "Order status updated successfully";
    } else {
        $_SESSION['error'] = "Failed to update order status: " . $conn->error;
    }
    header("Location: admin.php#orders");
    exit;
}

// Fetch data
$categories = $conn->query("SELECT * FROM categories ORDER BY name")->fetch_all(MYSQLI_ASSOC);
$products = $conn->query("SELECT b.*, c.name as category_name FROM books b LEFT JOIN categories c ON b.category_id = c.id ORDER BY b.created_at DESC")->fetch_all(MYSQLI_ASSOC);
$new_arrivals = $conn->query("SELECT * FROM books WHERE is_new_arrival = 1 ORDER BY created_at DESC LIMIT 4")->fetch_all(MYSQLI_ASSOC);
$orders = $conn->query("SELECT o.*, u.name as user_name FROM orders o LEFT JOIN user_information u ON o.user_id = u.id ORDER BY o.created_at DESC")->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Panel</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="assets/css/admin.css">
    <style>
        .category-image, .product-image { max-width: 100px; max-height: 100px; }
        .image-missing { color: red; font-style: italic; }
        .new-arrival-badge { background: #ff5722; color: white; padding: 2px 5px; border-radius: 3px; font-size: 12px; }
        .tab-button { padding: 10px 15px; cursor: pointer; border: 1px solid #ddd; background: #f5f5f5; }
        .tab-button.active { background: #333; color: white; border-color: #333; }
        .tab-content { display: none; padding: 20px 0; border-top: 1px solid #ddd; }
        .tab-content.active { display: block; }
        .admin-form { background: #f9f9f9; padding: 20px; margin-bottom: 20px; border-radius: 5px; }
        .admin-form input, .admin-form select, .admin-form textarea { 
            width: 100%; padding: 8px; margin-bottom: 10px; border: 1px solid #ddd; 
        }
        .admin-form button { padding: 8px 15px; background: #4CAF50; color: white; border: none; cursor: pointer; }
        .admin-form button:hover { background: #45a049; }
        .cart-table { width: 100%; border-collapse: collapse; }
        .cart-table th, .cart-table td { padding: 10px; border: 1px solid #ddd; text-align: left; }
        .cart-table th { background: #f5f5f5; }
        .status-pending { color: orange; }
        .status-shipped { color: blue; }
        .status-completed { color: green; }
        .status-cancelled { color: red; }
        .error { color: red; padding: 10px; background: #ffeeee; margin-bottom: 15px; }
        .success { color: green; padding: 10px; background: #eeffee; margin-bottom: 15px; }
    </style>
</head>
<body>
    <?php include 'includes/header.php'; ?>
    
    <main>
        <section class="admin-section">
            <h2>Admin Panel</h2>
            
            <?php if (isset($_SESSION['error'])): ?>
                <div class="error"><?= htmlspecialchars($_SESSION['error']) ?></div>
                <?php unset($_SESSION['error']); ?>
            <?php endif; ?>
            
            <?php if (isset($_SESSION['success'])): ?>
                <div class="success"><?= htmlspecialchars($_SESSION['success']) ?></div>
                <?php unset($_SESSION['success']); ?>
            <?php endif; ?>
            
            <div class="admin-tabs">
                <button class="tab-button active" onclick="showTab('categories')">Categories</button>
                <button class="tab-button" onclick="showTab('products')">Products</button>
                <button class="tab-button" onclick="showTab('new_arrivals')">New Arrivals</button>
                <button class="tab-button" onclick="showTab('orders')">Orders</button>
            </div>
            
            <!-- Categories Tab -->
            <div id="categories" class="tab-content active">
                <div class="admin-form">
                    <h3>Add New Category</h3>
                    <form method="POST" enctype="multipart/form-data">
                        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                        <input type="text" name="category_name" placeholder="Category Name" required>
                        <input type="file" name="category_image" accept="image/*" required>
                        <button type="submit" name="add_category">Add Category</button>
                    </form>
                </div>
                
                <h3>Existing Categories</h3>
                <table class="cart-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Name</th>
                            <th>Image</th>
                            <th>Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($categories as $cat): ?>
                            <?php $image_exists = file_exists('assets/img/categories/' . $cat['image']); ?>
                            <tr>
                                <td><?= $cat['id'] ?></td>
                                <td><?= htmlspecialchars($cat['name']) ?></td>
                                <td>
                                    <?php if ($image_exists): ?>
                                        <img src="assets/img/categories/<?= $cat['image'] ?>" 
                                             class="category-image"
                                             alt="<?= htmlspecialchars($cat['name']) ?>">
                                    <?php else: ?>
                                        <span class="image-missing">Image missing</span>
                                    <?php endif; ?>
                                </td>
                                <td><?= $image_exists ? 'OK' : 'Missing' ?></td>
                                <td>
                                    <a href="?delete_category=<?= $cat['id'] ?>&csrf_token=<?= $_SESSION['csrf_token'] ?>" 
                                       onclick="return confirm('Are you sure you want to delete this category?')">
                                        Delete
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            
            <!-- Products Tab -->
            <div id="products" class="tab-content">
                <div class="admin-form">
                    <h3>Add New Product</h3>
                    <form method="POST" enctype="multipart/form-data">
                        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                        <input type="text" name="title" placeholder="Product Title" required>
                        <textarea name="description" placeholder="Description" required rows="3"></textarea>
                        <input type="number" step="0.01" name="price" placeholder="Price" required min="0">
                        <select name="category_id" required>
                            <option value="">Select Category</option>
                            <?php foreach ($categories as $cat): ?>
                                <option value="<?= $cat['id'] ?>"><?= htmlspecialchars($cat['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <div>
                            <input type="checkbox" name="is_new_arrival" id="is_new_arrival">
                            <label for="is_new_arrival">Mark as New Arrival</label>
                        </div>
                        <input type="file" name="product_image" accept="image/*" required>
                        <button type="submit" name="add_product">Add Product</button>
                    </form>
                </div>
                
                <h3>All Products</h3>
                <table class="cart-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Title</th>
                            <th>Category</th>
                            <th>Price</th>
                            <th>Image</th>
                            <th>New Arrival</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($products as $product): ?>
                            <?php 
                            $image_exists = file_exists('assets/img/products/' . $product['image']);
                            $is_new = $product['is_new_arrival'] == 1;
                            ?>
                            <tr>
                                <td><?= $product['id'] ?></td>
                                <td><?= htmlspecialchars($product['title']) ?></td>
                                <td><?= htmlspecialchars($product['category_name'] ?? 'Uncategorized') ?></td>
                                <td>$<?= number_format($product['price'], 2) ?></td>
                                <td>
                                    <?php if ($image_exists): ?>
                                        <img src="assets/img/products/<?= $product['image'] ?>" 
                                             class="product-image"
                                             alt="<?= htmlspecialchars($product['title']) ?>">
                                    <?php else: ?>
                                        <span class="image-missing">Image missing</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($is_new): ?>
                                        <span class="new-arrival-badge">Yes</span>
                                    <?php else: ?>
                                        No
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <a href="?toggle_new_arrival=<?= $product['id'] ?>&csrf_token=<?= $_SESSION['csrf_token'] ?>">
                                        <?= $is_new ? 'Remove from' : 'Add to' ?> New Arrivals
                                    </a>
                                    <br>
                                    <a href="?delete_product=<?= $product['id'] ?>&csrf_token=<?= $_SESSION['csrf_token'] ?>" 
                                       onclick="return confirm('Are you sure you want to delete this product?')">
                                        Delete
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            
            <!-- New Arrivals Tab -->
            <div id="new_arrivals" class="tab-content">
                <h3>Current New Arrivals (Featured on Homepage)</h3>
                <table class="cart-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Title</th>
                            <th>Category</th>
                            <th>Price</th>
                            <th>Image</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($new_arrivals as $product): ?>
                            <?php $image_exists = file_exists('assets/img/products/' . $product['image']); ?>
                            <tr>
                                <td><?= $product['id'] ?></td>
                                <td><?= htmlspecialchars($product['title']) ?></td>
                                <td>
                                    <?php 
                                    $cat_name = 'Uncategorized';
                                    foreach ($categories as $cat) {
                                        if ($cat['id'] == $product['category_id']) {
                                            $cat_name = $cat['name'];
                                            break;
                                        }
                                    }
                                    echo htmlspecialchars($cat_name);
                                    ?>
                                </td>
                                <td>$<?= number_format($product['price'], 2) ?></td>
                                <td>
                                    <?php if ($image_exists): ?>
                                        <img src="assets/img/products/<?= $product['image'] ?>" 
                                             class="product-image"
                                             alt="<?= htmlspecialchars($product['title']) ?>">
                                    <?php else: ?>
                                        <span class="image-missing">Image missing</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <a href="?toggle_new_arrival=<?= $product['id'] ?>&csrf_token=<?= $_SESSION['csrf_token'] ?>">
                                        Remove from New Arrivals
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            
            <!-- Orders Tab -->
            <div id="orders" class="tab-content">
                <h3>Order Management</h3>
                <table class="cart-table">
                    <thead>
                        <tr>
                            <th>Order ID</th>
                            <th>Customer</th>
                            <th>Date</th>
                            <th>Total</th>
                            <th>Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($orders as $order): ?>
                            <tr>
                                <td>#<?= $order['id'] ?></td>
                                <td><?= htmlspecialchars($order['user_name'] ?? 'Guest') ?></td>
                                <td><?= date('d M Y', strtotime($order['created_at'])) ?></td>
                                <td>$<?= number_format($order['total'], 2) ?></td>
                                <td class="status-<?= $order['status'] ?>">
                                    <?= ucfirst($order['status']) ?>
                                </td>
                                <td>
                                    <form method="POST" class="status-form">
                                        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                                        <input type="hidden" name="order_id" value="<?= $order['id'] ?>">
                                        <select name="status">
                                            <option value="pending" <?= $order['status'] == 'pending' ? 'selected' : '' ?>>Pending</option>
                                            <option value="shipped" <?= $order['status'] == 'shipped' ? 'selected' : '' ?>>Shipped</option>
                                            <option value="completed" <?= $order['status'] == 'completed' ? 'selected' : '' ?>>Completed</option>
                                            <option value="cancelled" <?= $order['status'] == 'cancelled' ? 'selected' : '' ?>>Cancelled</option>
                                        </select>
                                        <button type="submit" name="update_status">Update</button>
                                    </form>
                                    <a href="#" onclick="toggleOrderDetails(<?= $order['id'] ?>)">View Details</a>
                                </td>
                            </tr>
                            <tr id="order-details-<?= $order['id'] ?>" style="display:none;">
                                <td colspan="6">
                                    <?php
                                    $items = $conn->query("
                                        SELECT oi.*, b.title, b.image 
                                        FROM order_items oi 
                                        JOIN books b ON oi.book_id = b.id 
                                        WHERE oi.order_id = {$order['id']}
                                    ")->fetch_all(MYSQLI_ASSOC);
                                    ?>
                                    <div class="order-details">
                                        <h4>Order Items:</h4>
                                        <table style="width:100%">
                                            <thead>
                                                <tr>
                                                    <th>Product</th>
                                                    <th>Price</th>
                                                    <th>Quantity</th>
                                                    <th>Total</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($items as $item): ?>
                                                    <tr>
                                                        <td>
                                                            <?php 
                                                            $imgPath = 'assets/img/products/' . $item['image'];
                                                            $imgPath = file_exists($imgPath) ? $imgPath : 'assets/img/fallback.jpeg';
                                                            ?>
                                                            <img src="<?= $imgPath ?>" width="30" style="vertical-align:middle">
                                                            <?= htmlspecialchars($item['title']) ?>
                                                        </td>
                                                        <td>$<?= number_format($item['price'], 2) ?></td>
                                                        <td><?= $item['quantity'] ?></td>
                                                        <td>$<?= number_format($item['price'] * $item['quantity'], 2) ?></td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                        <p><strong>Shipping Address:</strong> <?= htmlspecialchars($order['address']) ?>, <?= htmlspecialchars($order['city']) ?></p>
                                        <p><strong>Contact:</strong> <?= htmlspecialchars($order['contact']) ?></p>
                                        <p><strong>Email:</strong> <?= htmlspecialchars($order['email']) ?></p>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </section>
    </main>
    
   
    <script>
    function showTab(tabId) {
        // Hide all tab contents
        document.querySelectorAll('.tab-content').forEach(tab => {
            tab.classList.remove('active');
        });
        
        // Deactivate all tab buttons
        document.querySelectorAll('.tab-button').forEach(button => {
            button.classList.remove('active');
        });
        
        // Show selected tab and activate its button
        document.getElementById(tabId).classList.add('active');
        event.currentTarget.classList.add('active');
    }
    
    function toggleOrderDetails(orderId) {
        const detailsRow = document.getElementById('order-details-' + orderId);
        detailsRow.style.display = detailsRow.style.display === 'none' ? 'table-row' : 'none';
        return false;
    }
    </script>
</body>
</html>


    
   
    
    <?php include 'includes/footer.php'; ?>
    