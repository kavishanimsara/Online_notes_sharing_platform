<?php include 'includes/header.php'; ?>
<div class="form-container">
  <h2 class="text-center mb-4">Login</h2>
  <form id="loginForm" onsubmit="return validateLogin()" method="POST">
    <div class="mb-3">
      <label>Email</label>
      <input type="email" class="form-control" id="email" required>
    </div>
    <div class="mb-3">
      <label>Password</label>
      <input type="password" class="form-control" id="password" required>
    </div>
    <button type="submit" class="btn btn-primary w-100">Login</button>
  </form>
  <p class="mt-3 text-center">Don’t have an account? <a href="register.php">Register here</a></p>
</div>
<?php include 'includes/footer.php'; ?>
