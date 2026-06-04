<?php
require_once __DIR__ . '/includes/functions.php';

if (isLoggedIn()) {
    redirect('index.php');
}

require_once __DIR__ . '/includes/header.php';
?>

<div class="row justify-content-center">
    <div class="col-md-6 col-lg-5">

        <div class="form-card">
            <h3><i class="bi bi-person-plus"></i> Create Account</h3>

            <div id="registerAlert" class="alert d-none"></div>

            <form id="registerForm">

                <div class="mb-3">
                    <label class="form-label">Username</label>
                    <input type="text" class="form-control" name="username" placeholder="Enter username" required>
                </div>

                <div class="mb-3">
                    <label class="form-label">Email</label>
                    <input type="email" class="form-control" name="email" placeholder="Enter email" required>
                </div>

                <div class="mb-3">
                    <label class="form-label">Password</label>
                    <input type="password" class="form-control" name="password" placeholder="Enter password" required>
                </div>

                <div class="mb-3">
                    <label class="form-label">Confirm Password</label>
                    <input type="password" class="form-control" name="confirm_password" placeholder="Confirm password" required>
                </div>

                <button type="submit" class="btn btn-primary w-100">
                    Register
                </button>

                <p class="text-center mt-3 mb-0">
                    Already have an account?
                    <a href="<?php echo BASE_URL; ?>/login.php">Login here</a>
                </p>

            </form>
        </div>

    </div>
</div>

<script>
document.getElementById('registerForm').addEventListener('submit', async function(e) {
    e.preventDefault();

    const form = e.target;
    const alertBox = document.getElementById('registerAlert');
    const submitBtn = form.querySelector('button[type="submit"]');

    const data = {
        username: form.username.value.trim(),
        email: form.email.value.trim(),
        password: form.password.value,
        confirm_password: form.confirm_password.value
    };

    alertBox.classList.add('d-none');
    submitBtn.disabled = true;
    submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Registering...';

    try {
        const response = await fetch('<?php echo BASE_URL; ?>/api/register.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(data)
        });

        const result = await response.json();

        if (response.ok && result.success) {
            alertBox.classList.remove('d-none', 'alert-danger');
            alertBox.classList.add('alert-success');
            alertBox.textContent = 'Registration successful! Redirecting to login...';

            setTimeout(() => {
                window.location.href = '<?php echo BASE_URL; ?>/login.php';
            }, 1200);
        } else {
            alertBox.classList.remove('d-none', 'alert-success');
            alertBox.classList.add('alert-danger');
            alertBox.textContent = result.error || 'Registration failed';

            submitBtn.disabled = false;
            submitBtn.innerHTML = 'Register';
        }

    } catch (error) {
        alertBox.classList.remove('d-none', 'alert-success');
        alertBox.classList.add('alert-danger');
        alertBox.textContent = 'Network error. Please try again.';

        submitBtn.disabled = false;
        submitBtn.innerHTML = 'Register';
    }
});
</script>

<?php
require_once 'includes/footer.php';
?>