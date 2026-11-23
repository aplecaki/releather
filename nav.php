<?php
// Shared menu: add a page here and it appears on every other page.
$navPages = [
    "index.php" => "Home",
    "dashboard.php" => "Dashboard",
    "couches.php" => "Manage couches",
];

// Don't link to the page you are already on.
unset($navPages[basename($_SERVER["SCRIPT_NAME"])]);
?>
<nav class="header-links" aria-label="Main navigation">
    <?php foreach ($navPages as $file => $label): ?>
    <a class="nav-link" href="<?= $file ?>"><?= $label ?></a>
    <?php endforeach; ?>
</nav>
