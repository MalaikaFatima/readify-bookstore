<?php
require_once 'db.php';

$cart_count = 0;
if (isset($_SESSION['cart'])) {
    $cart_count = count($_SESSION['cart']);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Readify - Online Bookstore</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <div class="marquee">
        <marquee>Free Shipping on Orders Over RS:5000!</marquee>
    </div>
    <header class="header">
        <div class="search-bar">
            <form action="shop.php" method="GET" class="search-container">
                <input type="text" name="search" placeholder="Search books...">
                <button type="submit"><i class="fas fa-search"></i></button>
            </form>
        </div>
        <div class="logo">
            <a href="index.php"><img src="assets/img/logo.png" alt="Readify Logo"></a>
        </div>
        <nav class="nav">
            <a href="index.php">Home</a>
            <a href="shop.php">Shop</a>
            <a href="cart.php">Cart <span class="cart-count"><?php echo $cart_count; ?></span></a>
            <div class="login-dropdown">
                <a href="#" class="login-link">
                    <i class="fas fa-user"></i> <?php echo isset($_SESSION['user']) ? htmlspecialchars($_SESSION['user']['name']) : 'Account'; ?>
                </a>
                <div class="dropdown-content">
                    <?php if (isset($_SESSION['user'])): ?>
                        <?php if ($_SESSION['user']['role'] == 'admin'): ?>
                            <a href="admin.php">Admin Panel</a>
                            <a href="index.php">User Panel</a>
                        <?php endif; ?>
                        <a href="logout.php">Logout</a>
                    <?php else: ?>
                        <a href="login.php">Login</a>
                        <a href="signup.php">Sign Up</a>
                    <?php endif; ?>
                </div> 
            </div>
            <a href="https://wa.me/1234567890" class="whatsapp-icon"><i class="fab fa-whatsapp"></i></a>
        </nav>
    </header>
    <main>