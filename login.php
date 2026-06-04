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
            <h3><i class="bi bi-box-arrow-in-right"></i> Login</h3>

            <div id="loginAlert" class="alert d-none"></div>

            <form id="loginForm">

                <div class="mb-3">
                    <label class="form-label">Username or Email</label>
                    <input type="text" class="form-control" name="login" placeholder="Enter username or email" required>
                </div>

                <div class="mb-3">
                    <label class="form-label">Password</label>
                    <input type="password" class="form-control" name="password" placeholder="Enter password" required>
                </div>

                <button type="submit" class="btn btn-primary w-100">
                    Login
                </button>

                <p class="text-center mt-3 mb-0">
                    No account yet?
                    <a href="<?php echo BASE_URL; ?>/register.php">Register here</a>
                </p>

            </form>
        </div>

    </div>
</div>

<script>
document.getElementById('loginForm').addEventListener('submit', async function(e) {
    e.preventDefault();

    const form = e.target;
    const alertBox = document.getElementById('loginAlert');
    const submitBtn = form.querySelector('button[type="submit"]');

    const data = {
        login: form.login.value.trim(),
        password: form.password.value
    };

    alertBox.classList.add('d-none');
    submitBtn.disabled = true;
    submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Logging in...';

    try {
        const response = await fetch('<?php echo BASE_URL; ?>/api/login.php', {
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
            alertBox.textContent = 'Login successful! Redirecting...';

            setTimeout(() => {
                window.location.href = '<?php echo BASE_URL; ?>/index.php';
            }, 800);
        } else {
            alertBox.classList.remove('d-none', 'alert-success');
            alertBox.classList.add('alert-danger');
            alertBox.textContent = result.error || 'Login failed';

            submitBtn.disabled = false;
            submitBtn.innerHTML = 'Login';
        }

    } catch (error) {
        alertBox.classList.remove('d-none', 'alert-success');
        alertBox.classList.add('alert-danger');
        alertBox.textContent = 'Network error. Please try again.';

        submitBtn.disabled = false;
        submitBtn.innerHTML = 'Login';
    }
});
</script>

<?php
require_once 'includes/footer.php';
?>