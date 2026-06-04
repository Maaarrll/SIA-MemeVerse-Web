        </div>
    </div>
</div>

<footer class="footer">
    <div class="container">
        <div class="row">

            <div class="col-12 text-center">
                <a href="#">About</a> &nbsp;|&nbsp;
                <a href="#">Privacy</a> &nbsp;|&nbsp;
                <a href="#">Terms</a> &nbsp;|&nbsp;
                <a href="#">Contact</a>
            </div>

            <div class="col-12 text-center mt-2">
                <small>&copy; 2025 Memeverse – share the laughter</small>
            </div>

        </div>
    </div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<script>
document.getElementById('logoutLink')?.addEventListener('click', async function(e) {
    e.preventDefault();

    if (confirm('Are you sure you want to logout?')) {
        const response = await fetch('<?php echo BASE_URL; ?>/api/logout.php', {
            method: 'POST'
        });

        const result = await response.json();

        if (result.success) {
            window.location.href = '<?php echo BASE_URL; ?>/index.php';
        }
    }
});
</script>

</body>
</html>