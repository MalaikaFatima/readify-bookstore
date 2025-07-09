<?php
session_start();
include 'includes/db.php';
include 'includes/header.php';

$error = '';
$name = $email = $phone = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $phone = trim($_POST['phone'] ?? '');
    $role = $_POST['role'] ?? 'user'; // 

    if (empty($name) || empty($email) || empty($password)) {
        $error = "Name, email and password are required";
    } else {
        // Check  email 
        $stmt = $conn->prepare("SELECT id FROM user_information WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $stmt->store_result();
        
        if ($stmt->num_rows > 0) {
            $error = "Email already registered";
        } else {
           
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            
          
            $stmt = $conn->prepare("INSERT INTO user_information (name, email, password, phone, role) VALUES (?, ?, ?, ?, ?)");
            $stmt->bind_param("sssss", $name, $email, $hashed_password, $phone, $role);
            
            if ($stmt->execute()) {
                $_SESSION['success'] = "Account created successfully as ".htmlspecialchars($role)."!";
                header("Location: login.php?signup=success");
                exit;
            } else {
                $error = "Registration failed: " . $conn->error;
            }
        }
    }
}
?>

<div class="login-container">
    <div class="login-header">
        <h2>Sign Up</h2>
        <p>Create your account</p>
    </div>
    
    <?php if (!empty($error)): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>
    
    <form method="POST" class="login-form">
        <div class="form-group">
            <input type="text" name="name" placeholder="Full Name" required 
                   value="<?= htmlspecialchars($name) ?>">
        </div>
        
        <div class="form-group">
            <input type="email" name="email" placeholder="Email" required
                   value="<?= htmlspecialchars($email) ?>">
        </div>
        
        <div class="form-group">
            <input type="password" name="password" placeholder="Password" required>
        </div>
        
        <div class="form-group">
            <input type="text" name="phone" placeholder="Phone Number (optional)"
                   value="<?= htmlspecialchars($phone) ?>">
        </div>
        
        <div class="form-group">
            <label>Register as:</label>
            <select name="role" required>
                <option value="user">Regular User</option>
                <option value="admin">Administrator</option>
            </select>
        </div>
        
        <button type="submit" class="btn btn-primary">Sign Up</button>
        
        <div class="login-link">
            Already have an account? <a href="login.php">Login here</a>
        </div>
    </form>
</div>

<?php include 'includes/footer.php'; ?>