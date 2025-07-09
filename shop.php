<?php
session_start();
include 'includes/header.php';
require_once 'includes/db.php';

// Error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Pagination
$per_page = 8;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$start = ($page - 1) * $per_page;

// Search and category filter
$search = isset($_GET['search']) ? mysqli_real_escape_string($conn, $_GET['search']) : '';
$category = isset($_GET['category']) ? (int)$_GET['category'] : 0;

$where = "1=1";
if ($search) {
    $where .= " AND title LIKE '%$search%'";
}
if ($category) {
    $where .= " AND category_id = $category";
}

// Fetch books 
$books_query = mysqli_query($conn, "SELECT * FROM books WHERE $where LIMIT $start, $per_page");
if (!$books_query) {
    die("Database error: " . mysqli_error($conn));
}

$books = [];
while ($book = mysqli_fetch_assoc($books_query)) {
   
    $image_filename = basename($book['image']);
    $image_path = 'assets/img/products/' . $image_filename;
    
   
    if (file_exists($image_path)) {
        $book['image_url'] = $image_path;
    } elseif (file_exists($book['image'])) { 
        $book['image_url'] = $book['image'];
    } else {
        $book['image_url'] = 'assets/img/default-book.jpg';
    }
    
    $books[] = $book;
}

// Total pages
$total_result = mysqli_query($conn, "SELECT COUNT(*) as count FROM books WHERE $where");
if (!$total_result) {
    die("Database error: " . mysqli_error($conn));
}
$total_books = mysqli_fetch_assoc($total_result)['count'];
$total_pages = ceil($total_books / $per_page);
?>

<section class="shop-section">
    <h2>Shop Books</h2>
    <div class="book-grid">
        <?php if (!empty($books)): ?>
            <?php foreach ($books as $book): ?>
                <div class="book-card">
                    <img src="<?= htmlspecialchars($book['image_url']) ?>" 
                         alt="<?= htmlspecialchars($book['title']) ?>"
                         onerror="this.src='assets/img/default-book.jpg';this.onerror=null;">
                    <h3><?= htmlspecialchars($book['title']) ?></h3>
                    <p>RS<?= ($book['price']) ?></p>
                    <div class="button-group">
                        <a href="product.php?id=<?= $book['id'] ?>" class="buy-now">View Details</a>
                        <a href="cart.php?add=<?= $book['id'] ?>" class="add-to-cart">Add to Cart</a>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <p class="no-books">No books found matching your criteria.</p>
        <?php endif; ?>
    </div>
    
    <?php if ($total_pages > 1): ?>
        <div class="pagination">
            <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                <a href="shop.php?page=<?= $i ?><?= $search ? '&search=' . urlencode($search) : '' ?><?= $category ? '&category=' . $category : '' ?>" 
                   class="<?= $i == $page ? 'active' : '' ?>">
                    <?= $i ?>
                </a>
            <?php endfor; ?>
        </div>
    <?php endif; ?>
</section>

<?php include 'includes/footer.php'; ?>