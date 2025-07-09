<?php
include 'includes/header.php';
require_once 'includes/db.php';

// Fetch categories 
$categories_query = mysqli_query($conn, "SELECT * FROM categories");
$categories = [];
while ($category = mysqli_fetch_assoc($categories_query)) {
    $image_path = 'assets/img/categories/' . $category['image'];
    $category['image_exists'] = file_exists($image_path);
    $categories[] = $category;
}

// Fetch new arrivals 
$new_arrivals_query = mysqli_query($conn, "SELECT * FROM books ORDER BY id DESC LIMIT 4");
$new_arrivals = [];
while ($book = mysqli_fetch_assoc($new_arrivals_query)) {
    $image_path = 'assets/img/products/' . $book['image'];
    $book['image_exists'] = file_exists($image_path);
    $new_arrivals[] = $book;
}
?>

<section class="banner">
    <img id="banner-img" src="assets/img/banner1.png" alt="Banner">
    <div class="arrow left-arrow" onclick="changeBanner(-1)">&#10094;</div>
    <div class="arrow right-arrow" onclick="changeBanner(1)">&#10095;</div>
</section>

<section class="categories">
    <h2>Explore Categories</h2>
    <div class="category-grid">
        <?php foreach ($categories as $category): ?>
            <div class="category-card">
                <a href="shop.php?category=<?= $category['id'] ?>">
                    <?php if ($category['image_exists']): ?>
                        <img src="assets/img/categories/<?= htmlspecialchars($category['image']) ?>" 
                             alt="<?= htmlspecialchars($category['name']) ?>"
                             onerror="this.src='assets/img/default-category.jpg'">
                    <?php else: ?>
                        <img src="assets/img/default-category.jpg" 
                             alt="<?= htmlspecialchars($category['name']) ?>">
                    <?php endif; ?>
                    <h3><?= htmlspecialchars($category['name']) ?></h3>
                </a>
            </div>
        <?php endforeach; ?>
    </div>
</section>

<section class="new-arrivals">
    <h2>New Arrivals</h2>
    <div class="book-grid">
        <?php foreach ($new_arrivals as $book): ?>
            <div class="book-card">
                <?php if ($book['image_exists']): ?>
                    <img src="assets/img/products/<?= htmlspecialchars($book['image']) ?>" 
                         alt="<?= htmlspecialchars($book['title']) ?>"
                         onerror="this.src='assets/img/default-book.jpg'">
                <?php else: ?>
                    <img src="assets/img/default-book.jpg" 
                         alt="<?= htmlspecialchars($book['title']) ?>">
                <?php endif; ?>
                <h3><?= htmlspecialchars($book['title']) ?></h3>
                <p>RS<?= number_format($book['price'], 2) ?></p>
                <div class="button-group">
                    <a href="product.php?id=<?= $book['id'] ?>" class="buy-now">View Details</a>
                    <a href="cart.php?add=<?= $book['id'] ?>" class="add-to-cart">Add to Cart</a>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</section>

<?php include 'includes/footer.php'; ?>
<script src="assets/js/user.js"></script>
</body>
</html>