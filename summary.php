<?php
include 'includes/header.php';
require_once 'includes/db.php';

if (!isset($_GET['order_id'])) {
    header("Location: shop.php");
    exit;
}

$order_id = (int)$_GET['order_id'];
$order_query = mysqli_query($conn, "SELECT * FROM orders WHERE id = $order_id");
$order = mysqli_fetch_assoc($order_query);

if (!$order) {
    header("Location: shop.php");
    exit;
}

$items_query = mysqli_query($conn, "SELECT oi.*, b.title, b.image FROM order_items oi JOIN books b ON oi.book_id = b.id WHERE oi.order_id = $order_id");
$items = mysqli_fetch_all($items_query, MYSQLI_ASSOC);


function getImagePath($image_name) {
    $possible_paths = [
        'assets/img/products/' . $image_name,
        'admin/assets/img/products/' . $image_name,
        $image_name 
    ];
    
    foreach ($possible_paths as $path) {
        if (file_exists($path)) {
            return $path;
        }
    }
    
    return 'assets/img/default-book.jpg'; 
}
?>

<section class="summary-section">
    <h2>Order Confirmation</h2>
    <div class="summary-details">
        <h3>Order #<?php echo htmlspecialchars($order['id']); ?></h3>
        <p><strong>Name:</strong> <?php echo htmlspecialchars($order['name']); ?></p>
        <p><strong>Email:</strong> <?php echo htmlspecialchars($order['email']); ?></p>
        <p><strong>Address:</strong> <?php echo htmlspecialchars($order['address'] . ', ' . $order['city']); ?></p>
        <p><strong>Total:</strong> RS<?php echo ($order['total']); ?></p>
        <p><strong>Status:</strong> <?php echo htmlspecialchars($order['status']); ?></p>
    </div>
    <div class="order-details">
        <h3>Order Items</h3>
        <table class="cart-table">
            <thead>
                <tr>
                    <th>Book</th>
                    <th>Price</th>
                    <th>Quantity</th>
                    <th>Total</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($items as $item): ?>
                    <tr>
                        <td>
                            <div class="cart-item">
                                <img src="<?php echo htmlspecialchars(getImagePath($item['image'])); ?>" 
                                     alt="<?php echo htmlspecialchars($item['title']); ?>"
                                     onerror="this.src='assets/img/default-book.jpg'">
                                <div class="item-details">
                                    <h3><?php echo htmlspecialchars($item['title']); ?></h3>
                                </div>
                            </div>
                        </td>
                        <td>RS<?php echo ($item['price']); ?></td>
                        <td><?php echo htmlspecialchars($item['quantity']); ?></td>
                        <td>RS<?php echo ($item['price'] * $item['quantity']); ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>
<div class="summary-actions" style="margin-top: 20px; text-align: center;">
    <a href="shop.php" class="btn btn-primary">Continue Shopping</a>
</div>

<?php include 'includes/footer.php'; ?>
<script src="assets/js/user.js"></script>
</body>
</html>