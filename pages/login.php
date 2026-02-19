<?php
require_once __DIR__ . '/../includes/functions.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = sanitize_input($_POST['username']);
    $password = $_POST['password'];
    
    try {
        $stmt = $pdo->prepare("SELECT id, username, password, role FROM users WHERE username = ?");
        $stmt->execute([$username]);
        $user = $stmt->fetch();
        
        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role'] = $user['role'];
            redirect('?page=dashboard');
        } else {
            $error = "Invalid username or password";
        }
    } catch (PDOException $e) {
        error_log('Login error: ' . $e->getMessage());
        $error = "A system error occurred. Please try again.";
    }
}
?>

<div class="login-container">
    <div class="login-card">
        <div class="login-header">
            <div style="width:52px;height:52px;background:linear-gradient(135deg,var(--navy-600),var(--navy-500));border-radius:14px;display:flex;align-items:center;justify-content:center;margin:0 auto 14px;font-size:22px;color:#fff;">
                <i class="fas fa-road"></i>
            </div>
            <h2>RoadRunner</h2>
            <p class="mb-0">Command Center Login</p>
        </div>
            <div class="p-4">
                <?php if (isset($_GET['timeout'])): ?>
                    <div class="alert alert-warning">Your session expired due to inactivity. Please log in again.</div>
                <?php endif; ?>
                <?php if (isset($error)): ?>
                    <div class="alert alert-danger"><?php echo $error; ?></div>
                <?php endif; ?>
                
                <form method="POST">
                    <div class="mb-3">
                        <label for="username" class="form-label">Username</label>
                        <input type="text" class="form-control" id="username" name="username" required>
                    </div>
                    <div class="mb-3">
                        <label for="password" class="form-label">Password</label>
                        <input type="password" class="form-control" id="password" name="password" required>
                    </div>
                    <button type="submit" class="btn btn-primary w-100">Login</button>
                </form>
                

            </div>
        </div>
    </div>