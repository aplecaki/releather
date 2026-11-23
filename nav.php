<?php
// the menu, put new pages here
$navPages = [
    "index.php" => "Home",
    "dashboard.php" => "Dashboard",
    "couches.php" => "Manage couches",
];

// hide the page youre already on
unset($navPages[basename($_SERVER["SCRIPT_NAME"])]);
?>
<nav class="header-links" aria-label="Main navigation">
    <?php foreach ($navPages as $file => $label): ?>
    <a class="nav-link" href="<?= $file ?>"><?= $label ?></a>
    <?php endforeach; ?>
</nav>
