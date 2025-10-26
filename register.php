<?php include 'includes/header.php'; ?>
<div class="form-container">
  <h2 class="text-center mb-4">Create Account</h2>
  <form id="registerForm" onsubmit="return validateRegister()" method="POST">
    <div class="mb-3">
      <label>Name</label>
      <input type="text" class="form-control" id="name" required>
    </div>
    <div class="mb-3">
      <label>Email</label>
      <input type="email" class="form-control" id="email" required>
    </div>
    <div class="mb-3">
      <label>Password</label>
      <input type="password" class="form-control" id="password" required>
    </div>
    <div class="mb-3">
      <label>Confirm Password</label>
      <input type="password" class="form-control" id="confirmPassword" required>
    </div>
    <button type="submit" class="btn btn-success w-100">Register</button>
  </form>
</div>
<?php include 'includes/footer.php'; ?>
