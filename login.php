<?php
session_start();
include 'includes/db.php';
include 'includes/header.php';

if (isset($_SESSION['user'])) {
    header("Location: " . ($_SESSION['user']['role'] === 'admin' ? 'admin.php' : 'index.php'));
    exit;
}

$error = "";

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $role = $_POST['role'] ?? 'user'; 

    if (empty($email) || empty($password)) {
        $error = "Both email and password are required";
    } else {
        $stmt = $conn->prepare("SELECT * FROM user_information WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 1) {
            $user = $result->fetch_assoc();
            
           
            if (password_verify($password, $user['password']) && $user['role'] == $role) {
                $_SESSION['user'] = [
                    'id' => $user['id'],
                    'name' => $user['name'],
                    'email' => $user['email'],
                    'role' => $user['role']
                ];
                
                header("Location: " . ($user['role'] === 'admin' ? 'admin.php' : 'index.php'));
                exit;
            } else {
                $error = "Invalid credentials for selected role";
            }
        } else {
            $error = "Invalid email or password";
        }
    }
}
?>

<div class="login-container">
    <div class="login-header">
        <h2>Login</h2>
        <p>Login to your account</p>
    </div>
    
    <?php if (!empty($error)): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>
    
    <?php if (isset($_GET['signup']) && $_GET['signup'] === 'success'): ?>
        <div class="alert alert-success">Registration successful! Please login.</div>
    <?php endif; ?>
    
    <form method="POST" class="login-form">
        <div class="form-group">
            <input type="email" name="email" placeholder="Email" required>
        </div>
        
        <div class="form-group">
            <input type="password" name="password" placeholder="Password" required>
        </div>
        
        <div class="form-group">
            <select name="role" required>
                <option value="user">User</option>
                <option value="admin">Admin</option>
            </select>
        </div>
        
        <button type="submit" class="btn btn-primary">Login</button>
        
        <div class="signup-link">
            Don't have an account? <a href="signup.php">Sign Up</a>
        </div>
    </form>
</div>

<?php include 'includes/footer.php'; ?>