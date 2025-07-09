<?php
include 'includes/header.php';
require_once 'includes/db.php';

if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user']['id'];
$orders_query = mysqli_query($conn, "SELECT * FROM orders WHERE user_id = $user_id ORDER BY created_at DESC");
$orders = mysqli_fetch_all($orders_query, MYSQLI_ASSOC);
?>

<section class="summary-section">
    <h2>My Orders</h2>
    <?php if ($orders): ?>
        <?php foreach ($orders as $order): ?>
            <div class="order-details">
                <h3>Order #<?php echo $order['id']; ?> - <?php echo $order['status']; ?></h3>
                <p><strong>Placed on:</strong> <?php echo $order['created_at']; ?></p>
                <p><strong>Total:</strong>RS<?php echo $order['total']; ?></p>
                <?php
                $items_query = mysqli_query($conn, "SELECT oi.*, b.title, b.image FROM order_items oi JOIN books b ON oi.book_id = b.id WHERE oi.order_id = {$order['id']}");
                $items = mysqli_fetch_all($items_query, MYSQLI_ASSOC);
                ?>
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
                                        <img src="<?php echo $item['image']; ?>" alt="<?php echo $item['title']; ?>">
                                        <div class="item-details">
                                            <h3><?php echo $item['title']; ?></h3>
                                        </div>
                                    </div>
                                </td>
                                <td>RS<?php echo $item['price']; ?></td>
                                <td><?php echo $item['quantity']; ?></td>
                                <td>RS<?php echo $item['price'] * $item['quantity']; ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endforeach; ?>
    <?php else: ?>
        <p>No orders found.</p>
    <?php endif; ?>
</section>

<?php include 'includes/footer.php'; ?>
<script src="assets/js/user.js"></script>
</body>
</html>