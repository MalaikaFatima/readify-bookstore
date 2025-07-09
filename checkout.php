<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once 'includes/db.php';


if (empty($_SESSION['cart'])) {
    header("Location: cart.php");
    exit;
}


$total = 0;
$cart_items = [];
foreach ($_SESSION['cart'] as $book_id => $quantity) {
    $stmt = $conn->prepare("SELECT * FROM books WHERE id = ?");
    $stmt->bind_param("i", $book_id);
    $stmt->execute();
    $book = $stmt->get_result()->fetch_assoc();
    
    if ($book) {
       
        $image_path = '';
        $possible_paths = [
            'assets/img/products/' . $book['image'],
            'admin/assets/img/products/' . $book['image'],
            $book['image']
        ];
        
        foreach ($possible_paths as $path) {
            if (file_exists($path)) {
                $image_path = $path;
                break;
            }
        }
        
        $book['display_image'] = $image_path ?: 'assets/img/default-book.jpg';
        $cart_items[] = [
            'book' => $book,
            'quantity' => (int)$quantity
        ];
        $total += $book['price'] * (int)$quantity;
    }
}


if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $required = ['name', 'email', 'address', 'city', 'contact'];
    $errors = [];
    
    foreach ($required as $field) {
        if (empty(trim($_POST[$field]))) {
            $errors[] = ucfirst($field) . " is required";
        }
    }
    
    if (empty($errors)) {
        $conn->begin_transaction();
        try {
          
            $stmt = $conn->prepare("INSERT INTO orders 
                (user_id, name, email, address, city, contact, total, status) 
                VALUES (?, ?, ?, ?, ?, ?, ?, 'pending')");
            
            $user_id = $_SESSION['user']['id'] ?? null;
            $stmt->bind_param(
                "isssssd", 
                $user_id,
                $_POST['name'],
                $_POST['email'],
                $_POST['address'],
                $_POST['city'],
                $_POST['contact'],
                $total
            );
            
            $stmt->execute();
            $order_id = $conn->insert_id;
            
          
            $stmt = $conn->prepare("INSERT INTO order_items 
                (order_id, book_id, quantity, price) 
                VALUES (?, ?, ?, ?)");
            
            foreach ($cart_items as $item) {
                $stmt->bind_param(
                    "iiid",
                    $order_id,
                    $item['book']['id'],
                    $item['quantity'],
                    $item['book']['price']
                );
                $stmt->execute();
            }
            
            $conn->commit();
            unset($_SESSION['cart']);
            header("Location: summary.php?order_id=$order_id");
            exit;
            
        } catch (Exception $e) {
            $conn->rollback();
            $errors[] = "Order failed: " . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checkout</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <?php include 'includes/header.php'; ?>
    
    <main>
        <section class="checkout-container">
            <div class="checkout-form">
                <h2>Checkout</h2>
                
                <?php if (!empty($errors)): ?>
                    <div class="error-message">
                        <?php foreach ($errors as $error): ?>
                            <p><?= htmlspecialchars($error) ?></p>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
                
                <form method="POST">
                    <div class="contact-info">
                        <h3>Contact Information</h3>
                        <input type="text" name="name" placeholder="Full Name" required
                               value="<?= isset($_POST['name']) ? htmlspecialchars($_POST['name']) : 
                                      (isset($_SESSION['user']) ? htmlspecialchars($_SESSION['user']['name']) : '') ?>">
                        
                        <input type="email" name="email" placeholder="Email" required
                               value="<?= isset($_POST['email']) ? htmlspecialchars($_POST['email']) : 
                                      (isset($_SESSION['user']) ? htmlspecialchars($_SESSION['user']['email']) : '') ?>">
                        
                        <input type="tel" name="contact" placeholder="Phone Number" required
                               value="<?= htmlspecialchars($_POST['contact'] ?? '') ?>">
                    </div>
                    
                    <div class="shipping-info">
                        <h3>Shipping Information</h3>
                        <input type="text" name="address" placeholder="Address" required
                               value="<?= htmlspecialchars($_POST['address'] ?? '') ?>">
                        
                        <input type="text" name="city" placeholder="City" required
                               value="<?= htmlspecialchars($_POST['city'] ?? '') ?>">
                    </div>
                    
                    <h3>Payment Method: Cash on Delivery</h3>
                    <button type="submit" class="place-order">Place Order</button>
                </form>
            </div>
            
            <div class="cart-summary">
                <h3>Order Summary</h3>
                <?php foreach ($cart_items as $item): ?>
                    <div class="cart-item">
                        <img src="<?= htmlspecialchars($item['book']['display_image']) ?>" 
                             alt="<?= htmlspecialchars($item['book']['title']) ?>"
                             onerror="this.src='assets/img/default-book.jpg'">
                        <div class="item-details">
                            <h3><?= htmlspecialchars($item['book']['title']) ?></h3>
                            <p>Quantity: <?= (int)$item['quantity'] ?></p>
                        </div>
                        <div class="item-total">
                            RS<?= ($item['book']['price'] * $item['quantity']) ?>
                        </div>
                    </div>
                <?php endforeach; ?>
                
                <div class="summary-total">
                    <span>Total:</span>
                    <span>RS<?= ($total) ?></span>
                </div>
            </div>
        </section>
    </main>
    
    <?php include 'includes/footer.php'; ?>
</body>
</html>