<?php
session_start();
include 'includes/header.php';
require_once 'includes/db.php';

// Handle cart actions
if (isset($_GET['add'])) {
    $book_id = (int)$_GET['add'];
    $book_query = mysqli_query($conn, "SELECT * FROM books WHERE id = $book_id");
    if (mysqli_num_rows($book_query) > 0) {
        $_SESSION['cart'][$book_id] = ($_SESSION['cart'][$book_id] ?? 0) + 1;
        header("Location: cart.php");
        exit;
    }
}

if (isset($_GET['remove'])) {
    $book_id = (int)$_GET['remove'];
    if (isset($_SESSION['cart'][$book_id])) {
        $_SESSION['cart'][$book_id]--;
        if ($_SESSION['cart'][$book_id] <= 0) {
            unset($_SESSION['cart'][$book_id]);
        }
    }
    header("Location: cart.php");
    exit;
}


$total = 0;
$cart_items = [];
if (!empty($_SESSION['cart'])) {
    foreach ($_SESSION['cart'] as $book_id => $quantity) {
        $book_query = mysqli_query($conn, "SELECT * FROM books WHERE id = $book_id");
        if ($book_query && mysqli_num_rows($book_query) > 0) {
            $book = mysqli_fetch_assoc($book_query);
            
            // Image path handling
            $image_path = 'assets/img/products/' . $book['image'];
            $book['image_path'] = file_exists($image_path) ? $image_path : 'assets/img/default-book.jpg';
            
            $cart_items[] = ['book' => $book, 'quantity' => $quantity];
            $total += $book['price'] * $quantity;
        }
    }
}
?>

<section class="cart-section">
    <h2>Your Cart</h2>
    <?php if (!empty($cart_items)): ?>
        <table class="cart-table">
            <thead>
                <tr>
                    <th>Book</th>
                    <th>Price</th>
                    <th>Quantity</th>
                    <th>Total</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($cart_items as $item): ?>
                    <tr>
                        <td>
                            <div class="cart-item">
                                <img src="<?= htmlspecialchars($item['book']['image_path']) ?>" 
                                     alt="<?= htmlspecialchars($item['book']['title']) ?>"
                                     onerror="this.src='assets/img/default-book.jpg'">
                                <div class="item-details">
                                    <h3><?= htmlspecialchars($item['book']['title']) ?></h3>
                                    <p>by <?= htmlspecialchars($item['book']['author'] ?? 'Unknown Author') ?></p>
                                </div>
                            </div>
                        </td>
                        <td>RS<?= ($item['book']['price']) ?></td>
                        <td><?= $item['quantity'] ?></td>
                        <td>RS<?= ($item['book']['price'] * $item['quantity']) ?></td>
                        <td><a href="cart.php?remove=<?= $item['book']['id'] ?>" class="remove-btn">Remove</a></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <div class="cart-summary">
            <h3>Total: RS<?= ($total) ?></h3>
            <a href="checkout.php" class="checkout-button">Proceed to Checkout</a>
        </div>
    <?php else: ?>
        <div class="empty-cart">
            <p>Your cart is empty.</p>
            <a href="shop.php" class="continue-shopping">Continue Shopping</a>
        </div>
    <?php endif; ?>
</section>

<?php include 'includes/footer.php'; ?>