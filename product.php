<?php
include 'includes/header.php';
require_once 'includes/db.php';

if (!isset($_GET['id'])) {
    header("Location: shop.php");
    exit;
}

$book_id = (int)$_GET['id'];
$book_query = mysqli_query($conn, "SELECT * FROM books WHERE id = $book_id");
$book = mysqli_fetch_assoc($book_query);

if (!$book) {
    header("Location: shop.php");
    exit;
}


$image_path = 'assets/img/products/' . $book['image'];
$image_exists = file_exists($image_path);
?>

<section class="product-detail">
    <div class="product-container">
        <div class="product-image">
            <?php if ($image_exists): ?>
                <img src="assets/img/products/<?= htmlspecialchars($book['image']) ?>" 
                     alt="<?= htmlspecialchars($book['title']) ?>"
                     onerror="this.src='assets/img/default-book.jpg'">
            <?php else: ?>
                <img src="assets/img/default-book.jpg" 
                     alt="<?= htmlspecialchars($book['title']) ?>">
            <?php endif; ?>
        </div>
        <div class="product-info">
            <h2><?= htmlspecialchars($book['title']) ?></h2>
            <p><strong>Price:</strong><?= number_format($book['price'], 2) ?></p>
            <p><strong>Description:</strong> <?= htmlspecialchars($book['description']) ?></p>
            <div class="button-group">
                <a href="cart.php?add=<?= $book['id'] ?>" class="add-to-cart">Add to Cart</a>
            </div>
        </div>
    </div>
</section>

<?php include 'includes/footer.php'; ?>
<script src="assets/js/user.js"></script>
</body>
</html>