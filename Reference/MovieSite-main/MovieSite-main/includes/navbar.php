<?php
// $base_path must be set BEFORE including this file.
// Root pages:            $base_path = "";
// One level deep:        $base_path = "../";
// Two levels deep:       $base_path = "../../";
$base_path = $base_path ?? "";
?>
<header>
    <div class="logo">
        <a href="<?= $base_path ?>index.php" class="logo-link">
            <img src="<?= $base_path ?>logo.png" alt="The Binge Box" />
            <span class="site-name">The Binge Box</span>
        </a>
    </div>
    <nav>
        <ul>
            <li class="dropdown">
                <a href="<?= $base_path ?>search/" class="nav-link">
                    Search <span class="nav-arrow">&#9660;</span>
                </a>
                <ul class="dropdown-menu">
                    <li><a href="<?= $base_path ?>search/">Search Movies</a></li>
                    <li><a href="<?= $base_path ?>search/category/">Browse by Genre</a></li>
                </ul>
            </li>
            <li class="dropdown">
                <a href="<?= $base_path ?>about/" class="nav-link">
                    About <span class="nav-arrow">&#9660;</span>
                </a>
                <ul class="dropdown-menu">
                    <li><a href="<?= $base_path ?>about/">About Us</a></li>
                    <li><a href="<?= $base_path ?>about/movies.html">About Movies</a></li>
                </ul>
            </li>
            <?php if (isset($_SESSION['id'])): ?>
                <?php if (!empty($_SESSION['role']) && $_SESSION['role'] === 'creator'): ?>
                    <li><a href="<?= $base_path ?>creator/" class="nav-link">My Dashboard</a></li>
                <?php elseif (!empty($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?>
                    <li><a href="<?= $base_path ?>admin/dashboard.php" class="nav-link">Admin Panel</a></li>
                <?php endif; ?>
                <li><a href="<?= $base_path ?>account/" class="nav-link">Account</a></li>
                <li><a href="<?= $base_path ?>auth/logout.php" class="nav-link nav-logout">Logout</a></li>
            <?php else: ?>
                <li><a href="<?= $base_path ?>account/" class="nav-link">Account</a></li>
                <li><a href="<?= $base_path ?>auth/login.php" class="nav-link nav-login">Login</a></li>
            <?php endif; ?>
        </ul>
    </nav>
</header>
<script>
(function () {
    document.querySelectorAll('.dropdown').forEach(function (dd) {
        var toggle = dd.querySelector('.nav-link');
        var menu   = dd.querySelector('.dropdown-menu');
        if (!toggle || !menu) return;
        toggle.addEventListener('click', function (e) {
            e.preventDefault();
            var open = menu.classList.contains('open');
            document.querySelectorAll('.dropdown-menu.open').forEach(function (m) { m.classList.remove('open'); });
            if (!open) menu.classList.add('open');
        });
    });
    document.addEventListener('click', function (e) {
        if (!e.target.closest('.dropdown')) {
            document.querySelectorAll('.dropdown-menu.open').forEach(function (m) { m.classList.remove('open'); });
        }
    });
}());
</script>
